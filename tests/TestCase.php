<?php

namespace SapRfcManager\Tests;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as Orchestra;
use SapRfcManager\Providers\SapRfcServiceProvider;

class TestCase extends Orchestra
{
   protected function getPackageProviders($app)
   {
      return [
         SapRfcServiceProvider::class,
      ];
   }

   protected function getEnvironmentSetUp($app)
   {
      $app['config']->set('saprfc.default.connection', 'sandbox');
      $app['config']->set('saprfc.connections.sandbox', [
         'ashost' => '127.0.0.1',
         'sysnr' => '00',
         'client' => '100',
         'user' => 'USER',
         'passwd' => 'PASSWORD',
      ]);
      $app['config']->set('saprfc.circuit_breaker.threshold', 3);
      $app['config']->set('saprfc.retry.times', 2);
      $app['config']->set('saprfc.retry.backoff_ms', 10);
      $app['config']->set('cache.default', 'array');
   }

   protected function tearDown(): void
   {
      Cache::flush();
      parent::tearDown();
   }
}