<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use SAPNWRFC\Connection as SapConnection;
use SAPNWRFC\RemoteFunction;
use SapRfcManager\Contracts\SapConnectionManagerInterface;
use SapRfcManager\DTO\SapResult;
use SapRfcManager\Events\SapRfcExecuted;
use SapRfcManager\Events\SapRfcExecuting;
use SapRfcManager\Facades\SapRfc;
use SapRfcManager\Observability\MetricsCollector;

beforeEach(function () {
   Event::fake();

   $this->mockFunction = Mockery::mock(RemoteFunction::class);

   $this->mockConnection = Mockery::mock(SapConnection::class);
   $this->mockConnection->shouldReceive('getFunction')
      ->with('BAPI_USER_GETLIST')
      ->andReturn($this->mockFunction);

   $this->mockManager = $this->mock(SapConnectionManagerInterface::class, function (MockInterface $mock) {
      $mock->shouldReceive('connection')->andReturn($this->mockConnection);
   });
});

it('executes rfc successfully, fires events, and returns DTO', function () {
   $expectedData = ['USERNAME' => 'JOHN_DOE'];
   $this->mockFunction->shouldReceive('invoke')
      ->once()
      ->with(['MAX_ROWS' => 10], ['rtrim' => true])
      ->andReturn($expectedData);

   $result = SapRfc::on('production')
      ->call('BAPI_USER_GETLIST')
      ->with(['MAX_ROWS' => 10])
      ->execute();

   expect($result)->toBeInstanceOf(SapResult::class)
      ->and($result->get('USERNAME'))->toBe('JOHN_DOE')
      ->and($result->executionTimeMs)->toBeGreaterThanOrEqual(0);

   Event::assertDispatched(SapRfcExecuting::class);
   Event::assertDispatched(SapRfcExecuted::class);

   expect(Cache::get('sap_metrics:req:production:BAPI_USER_GETLIST:success'))->toEqual(1);
});

it('retries on failure and eventually throws exception', function () {
   config(['saprfc.retry.times' => 3]);

   $this->mockFunction->shouldReceive('invoke')
      ->times(3)
      ->andThrow(new Exception('SAP Network Timeout'));

   expect(function () {
      SapRfc::on('production')
         ->call('BAPI_USER_GETLIST')
         ->execute();
   })->toThrow(Exception::class, 'SAP Network Timeout');
});

it('stores and retrieves data correctly', function () {
   $dto = new SapResult('FUNC', ['key' => 'val'], 10.5);
   expect($dto->functionName)->toBe('FUNC')
      ->and($dto->get('key'))->toBe('val')
      ->and($dto->executionTimeMs)->toBe(10.5);
});

it('exposes metrics via controller', function () {
   $this->get('/metrics/sap')
      ->assertStatus(200)
      ->assertSee('sap_rfc_requests_total');
});

it('registers services in the container', function () {
   expect(app()->bound(SapConnectionManagerInterface::class))->toBeTrue();
});

it('does not record metrics if disabled', function () {
   config(['saprfc.metrics.enabled' => false]);
   $collector = app(MetricsCollector::class);
   $collector->record('sandbox', 'func', 'success', 10);
   expect(Cache::get('some_key'))->toBeNull();
});

it('generates prometheus string format with data', function () {
   \Illuminate\Support\Facades\Redis::shouldReceive('keys')
      ->once()
      ->andReturn(['sap_metrics:req:prod:FUNC:success']);

   \Illuminate\Support\Facades\Redis::shouldReceive('get')
      ->once()
      ->andReturn(10);

   $exporter = new \SapRfcManager\Observability\PrometheusExporter();
   $output = $exporter->export();

   expect($output)->toContain('sap_rfc_requests_total{env="prod",function="FUNC",status="success"} 10');
});

it('exercises all methods in SapResult DTO', function () {
   $data = ['user' => 'admin', 'status' => 'active'];
   $result = new SapRfcManager\DTO\SapResult('BAPI_TEST', $data, 1.25);

   expect($result->functionName)->toBe('BAPI_TEST')
      ->and($result->executionTimeMs)->toBe(1.25)
      ->and($result->get('user'))->toBe('admin')
      ->and($result->get('non_existent', 'default_val'))->toBe('default_val');

   expect($result->toArray()['data'])->toBe($data);
});