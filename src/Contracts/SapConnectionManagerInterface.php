<?php

namespace SapRfcManager\Contracts;

use SAPNWRFC\Connection as SapConnection;

interface SapConnectionManagerInterface
{
   public function connection(?string $environment = null): SapConnection;
   public function disconnect(?string $environment = null): void;
   public function disconnectAll(): void;
}