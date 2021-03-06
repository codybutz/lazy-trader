<?php namespace App\Services\Exchanges\Implementations\Sockets;

use App\Models\Exchange;
use App\Services\Coins\CoinMarketCapService;
use App\Services\Coins\CoinPriceService;
use App\Services\Exchanges\Implementations\Sockets\Traits\ExchangeCache;
use App\Services\Exchanges\Implementations\Sockets\Traits\MarketCache;
use Carbon\Carbon;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;

/**
 * TODO: Unknown why, but it seems to stop the socket after about a minute.
 */
class CoinbaseSocket extends BaseExchangeSocket
{
    use MarketCache, ExchangeCache;

    public $exchangeInternalName = 'Coinbase';

    public function handleMessage(MessageInterface $msg)
    {
        $data = json_decode($msg);

        if($data->type === 'match' || $data->type === 'last_match') {
            $market = $this->findMarketBySymbol(str_replace('-', '', $data->product_id));
            $time = $this->nearestMinute(Carbon::parse($data->time)->getTimestamp());

            $this->importTrade(
                $market,
                $time,
                floatval($data->price),
                floatval($data->size)
            );
        }
    }

    public function handleConnectionOpened()
    {
        $exchange = $this->getExchange();

        foreach($exchange->markets as $m) {
            $message = [
                'type' => 'subscribe',
                'channels' => [
                    [
                        'name' => 'matches',
                        'product_ids' => [$m->coinPair->name_seperated_coinbase]
                    ],
                    [
                        'name' => 'heartbeat',
                        'product_ids' => [$m->coinPair->name_seperated_coinbase]
                    ]
                ]
            ];

            $this->addToMarketCache($m);
            $this->sendMessage(json_encode($message));
        }
    }

    public function handleConnectionClosed()
    {
        \Log::info('Connection closed');
    }

    public function getConnectionUrl()
    {
        return 'wss://ws-feed.pro.coinbase.com';
    }

    public function handleConnectionException()
    {
        \Log::info('exception');
    }
}
