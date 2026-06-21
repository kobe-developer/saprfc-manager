<?php

namespace SapRfcManager\Observability;

use Illuminate\Support\Facades\Cache;
use SapRfcManager\Exceptions\SapRfcException;

class CircuitBreaker
{
   private int $failureThreshold;
   private int $resetTimeout;

   public function __construct()
   {
      $this->failureThreshold = config('saprfc.circuit_breaker.threshold', 5);
      $this->resetTimeout = config('saprfc.circuit_breaker.timeout_seconds', 60);
   }

   public function check(string $environment, string $functionName): void
   {
      $key = "sap_cb_{$environment}_{$functionName}";

      if (Cache::get("{$key}_open")) {
         throw new SapRfcException("Circuit Breaker is OPEN for SAP RFC: {$functionName} on {$environment}");
      }
   }

   public function recordSuccess(string $environment, string $functionName): void
   {
      $key = "sap_cb_{$environment}_{$functionName}";
      Cache::forget("{$key}_failures");
      Cache::forget("{$key}_open");
   }

   public function recordFailure(string $environment, string $functionName): void
   {
      $key = "sap_cb_{$environment}_{$functionName}";
      $failures = (int) Cache::increment("{$key}_failures");

      if ($failures >= $this->failureThreshold) {
         Cache::put("{$key}_open", true, $this->resetTimeout);
      }
   }
}