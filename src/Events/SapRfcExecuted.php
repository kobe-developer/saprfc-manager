<?php

namespace SapRfcManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SapRfcManager\DTO\SapResult;

class SapRfcExecuted
{
   use Dispatchable, SerializesModels;

   public function __construct(
      public string $functionName,
      public SapResult $result
   ) {
   }
}