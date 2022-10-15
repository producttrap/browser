<?php

declare(strict_types=1);

namespace ProductTrap\Browser\Tests;

use ProductTrap\Browser\BrowserServiceProvider;
use ProductTrap\ProductTrapServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ProductTrapServiceProvider::class, BrowserServiceProvider::class];
    }
}
