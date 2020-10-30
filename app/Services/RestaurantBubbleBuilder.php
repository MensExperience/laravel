<?php

namespace App\Services;

use Illuminate\Support\Arr;

use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder;

// ContainerBuilderは、SDKに用意されたインターフェース
// interface ContainerBuilder
// {
//     public function build();
// }

class RestaurantBubbleBuilder implements ContainerBuilder
{
    private const GOOGLE_MAP_URL = 'https://www.google.com/maps';

    // ぐるなびAPIの飲食店検索結果を代入するためのプロパティの宣言
    // プロパティは、クラスのインスタンスが持つ変数のこと
    private $imageUrl;
    private $name;
    private $closestStation;
    private $minutesByFoot;
    private $category;
    private $budget;
    private $latitude;
    private $longitude;
    private $phoneNumber;
    private $restaurantUrl;
    // setContensメソッドでは、ぐるなびAPIの検索結果をプロパティに代入する
    // builderメソッドでは、それらプロパティをもとにバブルコンテナの連想配列を返す

    // : RestaurantBubbleBuilderと記述することで、builderメソッドの戻り値がRestaurantBubbleBuilderクラスのインスタンスであることを型宣言しています。
    public static function builder(): RestaurantBubbleBuilder
    {
        return new self();
        // 自クラスのインスタンスを生成し、メソッド呼び出し元に返しています。
    }

    public function setContents(array $restaurant): void
    {
        $this->imageUrl = Arr::get($restaurant, 'image_url.shop_image1', null);
        $this->name = Arr::get($restaurant, 'name', null);
        $this->closestStation = Arr::get($restaurant, 'access.station', null);
        $this->minutesByFoot = Arr::get($restaurant, 'access.walk', null);
        $this->category = Arr::get($restaurant, 'category', null);
        $this->budget = Arr::get($restaurant, 'budget', null);
        $this->latitude = Arr::get($restaurant, 'latitude', null);
        $this->longitude = Arr::get($restaurant, 'longitude', null);
        $this->phoneNumber = Arr::get($restaurant, 'tel', null);
        $this->restaurantUrl = Arr::get($restaurant, 'url', null);
    }
    // $this->imageUrlとありますが、この$thisは、RestaurantBubbleBuilderクラスのインスタンス自身を指しています。$this->プロパティ名とすることで、インスタンスが持つプロパティを参照します。

    // Arr::get関数
    // 第一引数に連想配列、第二引数にキーを受け取り、そのキーの値を返します。もし、そのキーが連想配列に存在しない時は、第三引数の値を返します。
    // また、第二引数のキーは、深い階層を.区切りで指定します。
    // Arr::get関数は、連想配列にそのキーが存在しなかったとしても例外が発生しません。
    // 想定外のレスポンスであった場合でも処理が中断しないようにしました。

    public function build(): array
    {
        $array = [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url' => $this->imageUrl,
                'size' => 'full',
                'aspectRatio' => '20:13',
                'aspectMode' => 'cover',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $this->name,
                        'wrap' => true,
                        'weight' => 'bold',
                        'size' => 'md',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'xs',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'エリア',
                                        'color' => '#aaaaaa',
                                        'size' => 'xs',
                                        'flex' => 4
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $this->closestStation . '徒歩' . $this->minutesByFoot . '分',
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'xs',
                                        'flex' => 12
                                    ]
                                ]
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'xs',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'ジャンル',
                                        'color' => '#aaaaaa',
                                        'size' => 'xs',
                                        'flex' => 4
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $this->category,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'xs',
                                        'flex' => 12
                                    ]
                                ]
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'xs',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '予算',
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 4
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => is_numeric($this->budget) ? '¥' . number_format($this->budget) . '円' : '不明',
                                        'wrap' => true,
                                        'maxLines' => 1,
                                        'color' => '#666666',
                                        'size' => 'xs',
                                        'flex' => 12
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'xs',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '地図を見る',
                            'uri' => self::GOOGLE_MAP_URL . '?q=' . $this->latitude . ',' . $this->longitude,
                        ]
                    ],
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '電話する',
                            'uri' => 'tel:' . $this->phoneNumber,
                        ]
                    ],
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '詳しく見る',
                            'uri' => $this->restaurantUrl,
                        ]
                    ],
                    [
                        'type' => 'spacer',
                        'size' => 'xs'
                    ]
                ],
                'flex' => 0
            ]
        ];

        if ($this->imageUrl == '') {
            Arr::forget($array, 'hero');
        }

        return $array;
    }
    // 三項演算子は、式1 ? 式2 : 式3という形式で記述し、以下の結果となります。
    // 式1がtrueの場合は、式2が値となる
    // 式1がfalseの場合は、式3が値となる
    // ここでは、is_numeric($this->budget)が式1になります。

    // Arr::forget関数
    // 第一引数に連想配列、第二引数にキーを受け取り、連想配列からそのキーを削除します。

}
