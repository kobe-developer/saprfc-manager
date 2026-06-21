<?php

namespace SapRfcManager\DTO;

class SapResult
{
   public function __construct(
      public readonly string $functionName,
      public readonly array $data,
      public readonly float $executionTimeMs
   ) {
      //
   }

   public function get(string $key, mixed $default = null): mixed
   {
      return $this->data[$key] ?? $default;
   }

   public function toArray(): array
   {
      return [
         'function_name' => $this->functionName,
         'execution_time' => $this->executionTimeMs,
         'data' => $this->data,
      ];
   }
}