<?php

namespace SapRfcManager\Contracts;

use SapRfcManager\DTO\SapResult;

interface SapQueryInterface
{
   public function on(string $environment): self;
   public function call(string $functionName): self;
   public function with(array $params): self;
   public function execute(): SapResult;
}