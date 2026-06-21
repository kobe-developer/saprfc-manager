<?php

use Illuminate\Support\Facades\Cache;
use SapRfcManager\Observability\MetricsCollector;

it('records metrics properly', function () {
   $metrics = new MetricsCollector();
   $env = 'sandbox';
   $func = 'BAPI_USER_GETLIST';
   $status = 'success';

   // Simulasi eksekusi pertama
   $metrics->record($env, $func, $status, 150.5);
   // Simulasi eksekusi kedua
   $metrics->record($env, $func, $status, 100.5);

   $countKey = "sap_metrics:req:{$env}:{$func}:{$status}";
   $timeKey = "sap_metrics:time:{$env}:{$func}:{$status}";

   expect(Cache::get($countKey))->toEqual(2)->and(Cache::get($timeKey))->toEqual(251.0);
});