<?php

return [
   'default' => [
      'router' => env('SAP_ROUTER', 'bisnet'),
      'connection' => env('SAP_CONNECTION', 'default'),
   ],

   'log_channel' => env('LOG_CHANNEL', 'stack'),

   'retry' => [
      'times' => env('SAP_RETRY_TIMES', 3),
      'backoff_ms' => env('SAP_RETRY_BACKOFF_MS', 500),
   ],

   'routers' => [
      'bisnet' => env('SAP_ROUTER_BISNET', '/H/127.0.0.1/S/1200/W/SAP_DEFAULT'),
      'iforte' => env('SAP_ROUTER_IFORTE', '/H/127.0.0.2/S/1200/W/SAP_DEFAULT'),
   ],

   'connections' => [
      'default' => [
         'ashost' => env('SAP_ASHOST', 'sap-host.server.com'),
         'sysnr' => env('SAP_SYSNR', '00'),
         'client' => env('SAP_CLIENT', '100'),
         'sid' => env('SAP_SID', 'DEFAUT'),
         'user' => env('SAP_USER', 'SYSTEM'),
         'passwd' => env('SAP_PASSWD', 'PASSWORD'),
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