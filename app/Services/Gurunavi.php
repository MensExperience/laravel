<?php
namespace App\Services;
use GuzzleHttp\Client;

class Gurunavi
{
    // URLをGurunaviクラスの定数RESTAURANTS_SEARCH_API_URLとして定義
    private const RESTAURANTS_SEARCH_API_URL = 'https://api.gnavi.co.jp/RestSearchAPI/v3/';

    // public function searchRestaurants($word)
    // 型定義をしていない。
    // 配列やnullが渡されたら、TypeErrorという例外が発生して処理は中断します。
    // public function searchRestaurants(string $word)
    // 下記は更に強い型付け_PHP7から。戻り値にも指定ができる。: array
    public function searchRestaurants(string $word): array
    {
        $client = new Client();
        $response = $client
            // ->get('https://api.gnavi.co.jp/RestSearchAPI/v3/', [
                // 上では分かりづらく、getでそのまま受け取っていたので改修
                ->get(self::RESTAURANTS_SEARCH_API_URL, [
                'query' => [
                    'keyid' => env('GURUNAVI_ACCESS_KEY'),
                    'freeword' => str_replace(' ', ',', $word),
                ],
                'http_errors' => false,
            ]);
        return json_decode($response->getBody()->getContents(), true);
    }
}
