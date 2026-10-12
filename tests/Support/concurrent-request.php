<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if (! $app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'testing') { throw new RuntimeException('Only the disposable testing database is allowed.'); }
$spec = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
$headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'HTTP_X_TENANT_ID' => (string) $spec['tenant']];
if (isset($spec['key'])) { $headers['HTTP_IDEMPOTENCY_KEY'] = $spec['key']; }
$request = Illuminate\Http\Request::create($spec['path'], 'POST', [], [], [], $headers, json_encode($spec['payload']));
$app->instance('request', $request);
Illuminate\Support\Facades\Auth::guard('web')->setUser(App\Models\User::findOrFail($spec['actor']));
echo "READY\n"; fflush(STDOUT);
if (trim(fgets(STDIN)) !== 'GO') { throw new RuntimeException('Missing synchronized start signal.'); }
$started = microtime(true);
echo "STARTED\n"; fflush(STDOUT);
$response = $kernel->handle($request);
$finished = microtime(true);
$kernel->terminate($request, $response);
echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true), 'started' => $started, 'finished' => $finished])."\n";
