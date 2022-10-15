<?php

declare(strict_types=1);

use ProductTrap\Browser\Browser;
use ProductTrap\Contracts\BrowserFactory;
use ProductTrap\Facades\ProductTrapBrowser as FacadesProductTrapBrowser;
use ProductTrap\ProductTrapBrowser;

it('can add the Browser driver to ProductTrap', function () {
    /** @var ProductTrapBrowser $client */
    $client = $this->app->make(BrowserFactory::class);

    $client->extend('Browser_other', fn () => new Browser());

    expect($client)->driver(Browser::IDENTIFIER)->toBeInstanceOf(Browser::class)
        ->and($client)->driver('Browser_other')->toBeInstanceOf(Browser::class);
});

it('can call the ProductTrap Browser facade', function () {
    expect(FacadesProductTrapBrowser::driver(Browser::IDENTIFIER)->getName())->toBe('Basic Chromium');
});

it('can retrieve theBrowser driver from ProductTrap', function () {
    expect($this->app->make(BrowserFactory::class)->driver(Browser::IDENTIFIER))->toBeInstanceOf(Browser::class);
});
