<?php
$root = __DIR__ . '/..';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'usmanbalogun044@gmail.com')->first();
$svc = app(App\Services\WithdrawalService::class);
try {
    $w = $svc->request($user, 1000.0, 'Wema Bank', '0123456789', 'Test User');
    echo json_encode(['id' => $w->id, 'status' => $w->status], JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n";
}
