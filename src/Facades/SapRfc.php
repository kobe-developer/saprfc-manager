<?php

namespace SapRfcManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SapRfcManager\SapRfcQuery on(string $environment)
 * @method static \SapRfcManager\SapRfcQuery call(string $functionName)
 * @method static \SapRfcManager\SapRfcQuery with(array $params)
 * @method static array getFunctionDescription()
 * @method static array getAttributes()
 */
class SapRfc extends Facade
{
   protected static function getFacadeAccessor(): string
   {
      return 'saprfc.query';
   }
}