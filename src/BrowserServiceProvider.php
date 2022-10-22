<?php

declare(strict_types=1);

namespace ProductTrap\Browser;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use ProductTrap\Contracts\BrowserFactory;
use ProductTrap\ProductTrapBrowser;

class BrowserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var ProductTrapBrowser $factory */
        $factory = $this->app->make(BrowserFactory::class);

        $factory->extend(Browser::IDENTIFIER, function () {
            /** @var ConfigRepository $repository */
            $repository = $this->app->make(ConfigRepository::class);

            /** @var array $config */
            $config = $repository->get(
                'producttrap.browsers.'.$repository->get('producttrap.browsers.default', 'basic_chromium'),
                [],
            );

            return new Browser(
                config: $config,
            );
        });
    }
}
