<?php

namespace SapRfcManager;

use Exception;
use Illuminate\Pipeline\Pipeline;
use SapRfcManager\Contracts\SapConnectionManagerInterface;
use SapRfcManager\Contracts\SapQueryInterface;
use SapRfcManager\DTO\SapResult;
use SapRfcManager\Events\SapRfcExecuted;
use SapRfcManager\Events\SapRfcExecuting;
use SapRfcManager\Observability\CircuitBreaker;
use SapRfcManager\Observability\MetricsCollector;

class SapRfcQuery implements SapQueryInterface
{
   private string $environment;
   public ?string $functionName = null;
   public array $params = [];

   public function __construct(
      private SapConnectionManagerInterface $manager,
      private CircuitBreaker $circuitBreaker,
      private MetricsCollector $metrics
   ) {
      $this->environment = config('saprfc.default.connection');
   }

   public function on(string $environment): self
   {
      $this->environment = $environment;
      return $this;
   }
   public function call(string $functionName): self
   {
      $this->functionName = $functionName;
      return $this;
   }
   public function with(array $params): self
   {
      $this->params = array_merge($this->params, $params);
      return $this;
   }

   public function execute(): SapResult
   {
      return app(Pipeline::class)
         ->send($this)
         ->through(config('saprfc.middleware', []))
         ->then(fn(SapRfcQuery $query) => $query->runCoreExecution());
   }

   public function getFunctionDescription(): array
   {
      try {
         $connection = $this->manager->connection($this->environment);
         return $connection->getFunction($this->functionName)->getFunctionDescription();
      } catch (Exception $e) {
         throw $e;
      }
   }

   public function getAttributes(): array
   {
      $connection = $this->manager->connection($this->environment);
      return $connection->getAttributes();
   }

   private function runCoreExecution(): SapResult
   {
      event(new SapRfcExecuting($this->functionName, $this->params));

      $this->circuitBreaker->check($this->environment, $this->functionName);

      $startTime = microtime(true);

      try {
         $retries = config('saprfc.retry.times', 3);

         $result = retry($retries, function () {
            $connection = $this->manager->connection($this->environment);
            return $connection->getFunction($this->functionName)->invoke($this->params, ['rtrim' => true]);
         }, config('saprfc.retry.backoff_ms', 500));

         $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

         $this->circuitBreaker->recordSuccess($this->environment, $this->functionName);
         $this->metrics->record($this->environment, $this->functionName, 'success', $executionTimeMs);

         $resultData = new SapResult($this->functionName, $result, $executionTimeMs);

         event(new SapRfcExecuted($this->functionName, $resultData));

         return $resultData;

      } catch (Exception $e) {
         $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

         $this->circuitBreaker->recordFailure($this->environment, $this->functionName);
         $this->metrics->record($this->environment, $this->functionName, 'error', $executionTimeMs);

         throw $e;
      }
   }
}