<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

use App\Services\Gurunavi;
use App\Services\RestaurantBubbleBuilder;

use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;

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

            // $replyText = '';
            // foreach($gurunaviResponse['rest'] as $restaurant) {
            //     $replyText .=
            //         $restaurant['name'] . "\n" .
            //         $restaurant['url'] . "\n" .
            //         "\n";
            // }
            // // "\n"は改行です(なお、PHPでは、'\n'のようにシングルクォーテーションで囲むと改行と認識されないので注意してください)。

            // $replyToken = $event->getReplyToken();
            // // $replyText = $event->getText(); /* オウム返し用 */
            // $lineBot->replyText($replyToken, $replyText);

            // これまでは単純なテキストの返信を行うためにLineBotクラスのreplyTextメソッドを使ってきましたが、Flex Messageのような、テキスト以外のタイプのメッセージでの返信を行う場合はreplyMessageメソッドを使う必要があります。
            $bubbles = [];
            foreach ($gurunaviResponse['rest'] as $restaurant) {
                $bubble = RestaurantBubbleBuilder::builder();
                $bubble->setContents($restaurant);
                $bubbles[] = $bubble;
            }

            $carousel = CarouselContainerBuilder::builder();
            $carousel->setContents($bubbles);

            // インスタンス生成　FlexMessageBuilderのstaticメソッド
            $flex = FlexMessageBuilder::builder();
            // new FlexMessageBuilder()でインスタンスを生成しようとすると必須の引数があるので、このbuilderメソッドの方を使用しています。

            $flex->setAltText('飲食店検索結果');
            $flex->setContents($carousel);

            // 第2引数は、FlexMessageBuilderのインスタンス
            $lineBot->replyMessage($event->getReplyToken(), $flex);
        }
    }
}
