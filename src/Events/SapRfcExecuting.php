<?php

namespace SapRfcManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SapRfcExecuting
{
   use Dispatchable, SerializesModels;

   public function __construct(
      public string $functionName,
      public array $params
   ) {
   }
}