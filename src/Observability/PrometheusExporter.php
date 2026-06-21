<?php

namespace SapRfcManager\Observability;

use Illuminate\Support\Facades\Redis;

class PrometheusExporter
{
   public function export(): string
   {
      $output = [
         '# HELP sap_rfc_requests_total Total number of SAP RFC requests.',
         '# TYPE sap_rfc_requests_total counter',
      ];

      $keys = Redis::keys('sap_metrics:req:*');

      foreach ($keys as $key) {
         // Format key: sap_metrics:req:{env}:{function}:{status}
         $parts = explode(':', $key);
         $count = (int) Redis::get($key);

         $output[] = sprintf(
            'sap_rfc_requests_total{env="%s",function="%s",status="%s"} %d',
            $parts[2] ?? 'unknown',
            $parts[3] ?? 'unknown',
            $parts[4] ?? 'unknown',
            $count
         );
      }

      return implode("\n", $output) . "\n";
   }
}