<?php

namespace SapRfcManager\Http\Controllers;

use Illuminate\Http\Response;
use SapRfcManager\Observability\PrometheusExporter;

class PrometheusMetricsController
{
   public function __invoke(PrometheusExporter $exporter): Response
   {
      return response($exporter->export(), 200, [
         'Content-Type' => 'text/plain; version=0.0.4',
      ]);
   }
}