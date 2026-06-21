<?php

namespace SapRfcManager\Observability;

use Illuminate\Support\Facades\Cache;

class MetricsCollector
{
   public function record(string $env, string $function, string $status, float $timeMs): void
   {
      if (!config('saprfc.metrics.enabled', true)) {
         return;
      }

      $countKey = "sap_metrics:req:{$env}:{$function}:{$status}";
      $timeKey = "sap_metrics:time:{$env}:{$function}:{$status}";

      Cache::increment($countKey);
      $currentTime = Cache::get($timeKey, 0);
      Cache::put($timeKey, $currentTime + $timeMs);
   }
}