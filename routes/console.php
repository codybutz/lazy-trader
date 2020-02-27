<?php

use App\Models\Market;
use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('cryptocompare:fetch-coins', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchCoins());
})->describe('Fetches coins from CryptoCompare');

Artisan::command('cryptocompare:fetch-exchanges', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchExchanges());
})->describe('Fetches exchanges from CryptoCompare');

Artisan::command('cryptocompare:fetch-latest-news', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchNewsArticles());
})->describe('Fetches latest news from CryptoCompare');

Artisan::command('cryptocompare:fetch-news-sources', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchNewsSources());
})->describe('Fetches news sources from CryptoCompare');

Artisan::command('cryptocompare:fetch-news', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchNewsArticles(false));
})->describe('Fetches news from CryptoCompare');

Artisan::command('cryptocompare:fetch-news-by-source', function() {
    foreach(\App\Models\NewsSource::all() as $source) {
        dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchNewsArticles(false, $source->internal_name));
    }
})->describe('Fetches news from all sources from CryptoCompare');

Artisan::command('cryptocompare:fetch-markets', function() {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchMarkets());
})->describe('Fetches markets from CryptoCompare');

Artisan::command('cryptocompare:fetch-historical-prices {exchange?} {--all}', function($exchange = null) {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchHistoricalPricing(
        \App\Models\Exchange::whereInternalName($exchange)->firstOrFail(),
        null,
        false
    ));
})->describe('Fetches historical prices from CryptoCompare');

Artisan::command('cryptocompare:fetch-historical-prices-market {market}', function($market) {
    dispatch(new \App\Jobs\CryptoCompare\CryptoCompareFetchHistoricalPricingForMarket(
        \App\Models\Market::findOrFail($market)
    ));
})->describe('Fetches historical prices from CryptoCompare');

Artisan::command('lazy-trader:import-pricing-from-csv {file} {--delete}', function($file) {
    $files = glob($file);
    foreach($files as $f) {
        dispatch(new \App\Jobs\ImportPricingFromCsv(realpath($f), $this->hasOption('delete')));
    }
})->describe('Imports Pricing from CSV file template.');

Artisan::command('lazy-trader:market-gap-analysis {market}', function($market) {
    dispatch(new \App\Jobs\MarketPriceGapAnalysis(\App\Models\Market::findOrFail($market)));
})->describe('Finds missing sequences in market pricing.');

Artisan::command('lazy-trader:market-gap-analysis-all', function() {
    foreach(Market::whereActive(true)->get() as $market) {
        dispatch(new \App\Jobs\MarketPriceGapAnalysis($market));
    }
})->describe('Finds missing sequences in market pricing.');

Artisan::command('lazy-trader:market-gap-recovery {market}', function($market) {
    dispatch(new \App\Jobs\MarketPriceGapRecovery(
        Market::findOrFail($market)
    ));
})->describe('Attempts to recover missing sequences in market pricing.');

Artisan::command('lazy-trader:seed-coin-tags', function() {
    dispatch(new \App\Jobs\SeedCoinTagsFromName());
})->describe('Creates coin tag data from name & symbol.');
