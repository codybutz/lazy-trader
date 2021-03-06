<?php

namespace App\Jobs\CryptoCompare;

use App\Integrations\CryptoCompareApi;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\Coins\CoinMarketCapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CryptoCompareFetchPricingForMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private static $HISTORY_LIMIT = 7 * 24 * 60 * 60;

    /**
     * @var Market
     */
    private $market;

    /**
     * @var bool
     */
    private $historical;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Market $market, $historical = false)
    {
        $this->market = $market;
        $this->historical = $historical;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $api = app()->make(CryptoCompareApi::class);

        $this->market->load(['coinPair', 'coinPair.quote', 'coinPair.base', 'exchange']);

        $oldestPriceData = MarketPrice::whereMarketId($this->market->id)->orderBy('timestamp')->limit(1)->first();
        $toTs = now()->timestamp;
        if ($oldestPriceData && $this->historical) {
            $toTs = $oldestPriceData->timestamp;
        }

        if ($toTs <= now()->timestamp - static::$HISTORY_LIMIT) {
            \Log::info("We have hit the limit of historical data for this market.");
            return;
        }

        try {

            $prices = $api->histominute(
                $this->market->coinPair->base->symbol,
                $this->market->coinPair->quote->symbol,
                $this->market->exchange->internal_name,
                $toTs
            );

            foreach ($prices->Data->Data as $price) {
                $this->market->prices()->updateOrCreate([
                    'timestamp' => $price->time
                ], [
                    'open' => $price->open,
                    'close' => $price->close,
                    'high' => $price->high,
                    'low' => $price->low,
                    'volume' => $price->volumeto
                ]);
            }

//            // Refresh the market cap for coin.
//            if($this->market->coinPair->quote->is_fiat_currency && !$this->historical) {
//                CoinMarketCapService::wipeMarketCap($this->market->coinPair->base);
//                CoinMarketCapService::marketCap($this->market->coinPair->base);
//            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
