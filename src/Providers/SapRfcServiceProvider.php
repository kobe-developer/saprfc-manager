<?php

namespace SapRfcManager\Providers;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SapRfcManager\Contracts\SapConnectionManagerInterface;
use SapRfcManager\Observability\CircuitBreaker;
use SapRfcManager\Observability\MetricsCollector;
use SapRfcManager\SapConnectionManager;
use SapRfcManager\SapRfcQuery;

class SapRfcServiceProvider extends ServiceProvider
{
   public function register(): void
   {
      $this->mergeConfigFrom(__DIR__ . '/../../config/saprfc.php', 'saprfc');

      $this->app->singleton(SapConnectionManagerInterface::class, function ($app) {
         return new SapConnectionManager();
      });

      $this->app->bind('saprfc.query', function ($app) {
         return new SapRfcQuery(
            $app->make(SapConnectionManagerInterface::class),
            $app->make(CircuitBreaker::class),
            $app->make(MetricsCollector::class),
            $app->make(Pipeline::class)
         );
      });
   }

   public function boot(): void
   {
      if ($this->app->runningInConsole()) {
         $this->publishes([
            __DIR__ . '/../../config/saprfc.php' => config_path('saprfc.php'),
         ], 'saprfc-config');
      }

      if (config('saprfc.metrics.expose_route', true)) {
         Route::get(
            config('saprfc.metrics.route_path', '/metrics/sap'),
            \SapRfcManager\Http\Controllers\PrometheusMetricsController::class
         );
      }
   }
}