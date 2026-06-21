<?php

namespace SapRfcManager;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SAPNWRFC\Connection as SapConnection;
use SapRfcManager\Contracts\SapConnectionManagerInterface;
use SapRfcManager\Exceptions\SapRfcException;

class SapConnectionManager implements SapConnectionManagerInterface
{
   /** @var array<string, SapConnection> */
   private array $pool = [];

   public function connection(?string $environment = null): SapConnection
   {
      $environment ??= config('saprfc.default.connection');

      if (isset($this->pool[$environment])) {
         try {
            $this->pool[$environment]->ping();
            return $this->pool[$environment];
         } catch (Exception $e) {
            // Koneksi mati (timeout/terputus), buang dari pool
            $this->disconnect($environment);
         }
      }

      // Buat koneksi baru jika tidak ada di pool
      $this->pool[$environment] = $this->createConnection($environment);
      return $this->pool[$environment];
   }

   private function createConnection(string $environment): SapConnection
   {
      $config = config("saprfc.connections.{$environment}");
      if (!$config) {
         throw new SapRfcException("Configuration for SAP environment '{$environment}' not found.");
      }

      $allRouters = config('saprfc.routers', []);
      $activeRouterKey = Cache::get('sap_active_router', config('saprfc.default.router'));

      // Router Failover Strategy
      $tryOrder = array_unique(array_merge([$activeRouterKey], array_keys($allRouters)));

      foreach ($tryOrder as $routerKey) {
         try {
            $config['saprouter'] = $allRouters[$routerKey] ?? '';
            $connection = new SapConnection($config);
            $connection->ping();

            if ($routerKey !== $activeRouterKey) {
               Cache::forever('sap_active_router', $routerKey);
               $this->log()->info("SAP Router failover triggered. Active router updated to: {$routerKey}");
            }

            return $connection;
         } catch (Exception $e) {
            $this->log()->warning("SAP Connection failed via router '{$routerKey}'. Env: {$environment}. Error: {$e->getMessage()}");
            continue;
         }
      }

      throw new SapRfcException("All SAP routers are unreachable for environment: {$environment}");
   }

   public function disconnect(?string $environment = null): void
   {
      $environment ??= config('saprfc.default.connection');
      if (isset($this->pool[$environment])) {
         try {
            $this->pool[$environment]->close();
         } catch (Exception) {
            // Ignore close error
         }
         unset($this->pool[$environment]);
      }
   }

   public function disconnectAll(): void
   {
      foreach (array_keys($this->pool) as $env) {
         $this->disconnect($env);
      }
   }

   private function log()
   {
      return Log::channel(config('saprfc.log_channel', 'stack'));
   }

   public function __destruct()
   {
      $this->disconnectAll();
   }
}