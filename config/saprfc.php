<?php

return [
   'default' => [
      'router' => env('SAP_SAPROUTER', 'bisnet'),
      'connection' => env('SAP_CONNECTION', 'default'),
   ],

   'log_channel' => env('SAP_LOG_CHANNEL', 'stack'),

   'retry' => [
      'times' => env('SAP_RETRY_TIMES', 3),
      'backoff_ms' => env('SAP_RETRY_BACKOFF_MS', 500),
   ],

   'routers' => [
      'bisnet' => env('SAP_SAPROUTER_BISNET'),
      'iforte' => env('SAP_SAPROUTER_IFORTE'),
   ],

   'connections' => [
      'default' => [
         'ashost' => env('SAP_ASHOST'),
         'sysnr' => env('SAP_SYSNR'),
         'client' => env('SAP_CLIENT'),
         'sid' => env('SAP_SID'),
         'user' => env('SAP_USER'),
         'passwd' => env('SAP_PASSWD'),
      ],
   ],

   'middleware' => [
      //
   ],

   'circuit_breaker' => [
      'threshold' => 5,
      'timeout_seconds' => 60,
   ],

   'metrics' => [
      'enabled' => true,
      'expose_route' => true,
      'route_path' => '/metrics/sap',
   ],
];