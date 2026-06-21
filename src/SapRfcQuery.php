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
      private MetricsCollector $metrics,
      private Pipeline $pipeline
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

   /**
    * Entry point utama untuk eksekusi RFC.
    */
   public function execute(): SapResult
   {
      return $this->pipeline
         ->send($this)
         ->through(config('saprfc.middleware', []))
         ->then(fn(SapRfcQuery $query) => $query->runCoreExecution());
   }

   public function getFunctionDescription(): array
   {
      return $this->executeObservability(function () {
         return $this->manager->connection($this->environment)
            ->getFunction($this->functionName)
            ->getFunctionDescription();
      });
   }

   public function getAttributes(): array
   {
      return $this->executeObservability(function () {
         return $this->manager->connection($this->environment)
            ->getAttributes();
      });
   }

   /**
    * Inti eksekusi dengan Circuit Breaker, Metrics, dan Retry Logic.
    */
   private function runCoreExecution(): SapResult
   {
      $this->circuitBreaker->check($this->environment, $this->functionName);

      $startTime = microtime(true);
      event(new SapRfcExecuting($this->functionName, $this->params));

      try {
         // Logika retry dengan filter pengecualian
         $result = retry(
            config('saprfc.retry.times', 3),
            function () {
               return $this->manager->connection($this->environment)
                  ->getFunction($this->functionName)
                  ->invoke($this->params, ['rtrim' => true]);
            },
            config('saprfc.retry.backoff_ms', 500),
            fn($e) => $this->shouldRetry($e)
         );

         $duration = round((microtime(true) - $startTime) * 1000, 2);
         $this->recordOutcome('success', $duration);

         $resultData = new SapResult($this->functionName, $result, $duration);
         event(new SapRfcExecuted($this->functionName, $resultData));

         return $resultData;

      } catch (Exception $e) {
         $this->recordOutcome('error', round((microtime(true) - $startTime) * 1000, 2));
         throw $e;
      }
   }

   /**
    * Wrapper observabilitas untuk metode non-eksekusi (description/attributes).
    */
   private function executeObservability(callable $callback)
   {
      try {
         return $callback();
      } catch (Exception $e) {
         $this->metrics->record($this->environment, $this->functionName, 'error', 0);
         throw $e;
      }
   }

   private function recordOutcome(string $status, float $duration): void
   {
      if ($status === 'success') {
         $this->circuitBreaker->recordSuccess($this->environment, $this->functionName);
      } else {
         $this->circuitBreaker->recordFailure($this->environment, $this->functionName);
      }
      $this->metrics->record($this->environment, $this->functionName, $status, $duration);
   }

   private function shouldRetry(Exception $e): bool
   {
      $dontRetry = [
         \InvalidArgumentException::class,
      ];

      foreach ($dontRetry as $class) {
         if ($e instanceof $class) {
            return false;
         }
      }

      return true;
   }
}