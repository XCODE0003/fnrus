<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class SendController extends Controller
{
    private $telegram;
    private $chat_ids = [1000502497,
        1000580041,
        1655299685,
        1001234263,
        1001405832,
        1002439585,
        1003237231,
        1003529382,
        1003779709,
        1004074681,
        1004848152,
        1005023581,
        1005059825,
        1005258506,
        1005270163,
        1005470586,
        1005855435,
        1006070954,
        1006282699,
        1006539611,
        1006728417,
        1006770344,
        1007415853,
        1007489202,
        1008060291,
        1008104215,
        1008979457,
        1009076490,
        1009150675,
        1009221183,
        1009231193,
        1009585299,
        1009603523,
        1009713542,
        1009820886,
        1655299685,
        1009945093,
        1010103815,
        1010820973,
        1010828632,
        1010909192,
        1011250148,
        1011913314,
        1012713452,
        1013679200,
        1013755547,
        1014410631,
        1015158208,
        1015363554,
        1015596035,
        1015719407,
        1015859839,
        1015873760,
        1016001652,
        1016201227,
        1016434896,
        1017956040,
        1018048824,
        1018819682,
        1018858904,
        1018868385,
        1019089008,
        1655299685
    ];

    public function __construct()
    {
        $this->telegram = new Api('5559834435:AAH8lsgdPElnE1gt2awNXKHR-n4zXj4HeKc');
    }

    public function sendMessages()
    {
        // Guzzle клиент
        $client = new Client();

        // Массив промисов.
        $promises = [];

        foreach ($this->chat_ids as $index => $chat_id) {
            // Создайте запрос.
            $request = new Request(
                'POST',
                'https://api.telegram.org/bot' . $this->telegram->getAccessToken() . '/sendMessage?chat_id='.$chat_id.'&text=Чего молчишь? /start'
            );

            // Отправьте запрос асинхронно и добавьте его в массив промисов.
            $promises[] = $client->sendAsync($request)->then(
                function (ResponseInterface $res) use ($chat_id) {
                    Log::info('Message sent to ' . $chat_id . '. Response status: ' . $res->getStatusCode());
                },
                function (\Exception $e) use ($chat_id) {
                    Log::error('Error sending message to ' . $chat_id . '. ' . $e->getMessage());
                }
            );

            if (count($promises) >= 30) {
                // Создаем команду для обработки каждого обещания
                $eachPromise = new Promise\EachPromise($promises, [
                    'concurrency' => 30,
                    'rejected' => function ($reason) {
                    },
                ]);

                // Ожидание выполнения всех обещаний
                $eachPromise->promise()->wait();

                // Очищаем массив промисов.
                $promises = [];

                // Пауза на 1 секунду.
                sleep(1);
            }
        }

        // Разрешаем остальные промисы.
        if (count($promises) > 0) {
            $eachPromise = new Promise\EachPromise($promises, [
                'concurrency' => 30,
                'rejected' => function ($reason) {
                    // Обработка ошибок
                },
            ]);

            $eachPromise->promise()->wait();
        }
    }
}
