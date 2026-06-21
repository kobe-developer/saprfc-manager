<?php

use Illuminate\Support\Facades\Cache;
use SapRfcManager\Observability\CircuitBreaker;
use SapRfcManager\Exceptions\SapRfcException;

beforeEach(function () {
   $this->circuitBreaker = new CircuitBreaker();
});

it('records success and clears failures', function () {
   $env = 'sandbox';
   $func = 'BAPI_TEST';
   $keyFailures = "sap_cb_{$env}_{$func}_failures";

   Cache::put($keyFailures, 2);

   $this->circuitBreaker->recordSuccess($env, $func);

   expect(Cache::has($keyFailures))->toBeFalse();
});

it('opens circuit when failure threshold is reached', function () {
   $env = 'sandbox';
   $func = 'BAPI_TEST';

   $this->circuitBreaker->recordFailure($env, $func);
   $this->circuitBreaker->recordFailure($env, $func);
   $this->circuitBreaker->recordFailure($env, $func);

   expect(Cache::get("sap_cb_{$env}_{$func}_open"))->toBeTrue();
});

it('throws exception when circuit is open', function () {
   $env = 'sandbox';
   $func = 'BAPI_TEST';

   Cache::put("sap_cb_{$env}_{$func}_open", true);

   $this->circuitBreaker->check($env, $func);
})->throws(SapRfcException::class, 'Circuit Breaker is OPEN for SAP RFC: BAPI_TEST on sandbox');