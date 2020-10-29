<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

use App\Services\Gurunavi;

class LineBotController extends Controller
{
    public function index()
    {
        return view('linebot.index');
    }

    // オウム返し用
    // public function parrot(Request $request)

    // ぐるなびAPI
    public function restaurants(Request $request)
    {
        Log::debug($request->header());
        Log::debug($request->input());

        $httpClient = new CurlHTTPClient(env('LINE_ACCESS_TOKEN'));
        $lineBot = new LINEBot($httpClient, ['channelSecret' => env('LINE_CHANNEL_SECRET')]);

        $signature = $request->header('x-line-signature');

        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            abort(400, 'Invalid signature');
        }

        $events = $lineBot->parseEventRequest($request->getContent(), $signature);

        Log::debug($events);

        foreach ($events as $event) {
            if (!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }

            // インスタンス生成
            $gurunavi = new Gurunavi();
            // 検索結果の連想配列が$gurunaviResponseに代入されている
            $gurunaviResponse = $gurunavi->searchRestaurants($event->getText());

            // レスポンスエラー処理
            if (array_key_exists('error', $gurunaviResponse)) {
                    $replyText = $gurunaviResponse['error'][0]['message'];
                    $replyToken = $event->getReplyToken();
                    $lineBot->replyText($replyToken, $replyText);
                    continue;
                }

            $replyText = '';
            foreach($gurunaviResponse['rest'] as $restaurant) {
                $replyText .=
                    $restaurant['name'] . "\n" .
                    $restaurant['url'] . "\n" .
                    "\n";
            }
            // "\n"は改行です(なお、PHPでは、'\n'のようにシングルクォーテーションで囲むと改行と認識されないので注意してください)。

            $replyToken = $event->getReplyToken();
            // $replyText = $event->getText(); /* オウム返し用 */

            $lineBot->replyText($replyToken, $replyText);
        }
    }
}
