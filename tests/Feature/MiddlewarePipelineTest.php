<?php

use Mockery\MockInterface;
use SAPNWRFC\Connection as SapConnection;
use SAPNWRFC\RemoteFunction;
use SapRfcManager\Contracts\SapConnectionManagerInterface;
use SapRfcManager\Facades\SapRfc;
use SapRfcManager\SapRfcQuery;

class ModifyParamsMiddleware
{
   public function handle(SapRfcQuery $query, Closure $next)
   {
      $query->with(['INJECTED' => 'YES']);
      return $next($query);
   }
}

it('passes through middleware and modifies params', function () {
   // Daftarkan middleware ke config secara dinamis saat runtime test
   config(['saprfc.middleware' => [ModifyParamsMiddleware::class]]);

   $mockFunction = Mockery::mock(RemoteFunction::class);
   // Verifikasi bahwa parameter yang di-invoke MENGANDUNG parameter yang di-inject oleh middleware
   $mockFunction->shouldReceive('invoke')
      ->once()
      ->with(['ORIGINAL' => 'NO', 'INJECTED' => 'YES'], ['rtrim' => true])
      ->andReturn(['STATUS' => 'OK']);

   $mockConnection = Mockery::mock(SapConnection::class);
   $mockConnection->shouldReceive('getFunction')->andReturn($mockFunction);

   $this->mock(SapConnectionManagerInterface::class, function (MockInterface $mock) use ($mockConnection) {
      $mock->shouldReceive('connection')->andReturn($mockConnection);
   });

   $result = SapRfc::call('BAPI_TEST')
      ->with(['ORIGINAL' => 'NO'])
      ->execute();

   expect($result->get('STATUS'))->toBe('OK');
});