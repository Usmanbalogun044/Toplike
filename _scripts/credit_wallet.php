<?php
// Usage: php _scripts/credit_wallet.php <email> <amount>

if ($argc < 3) {
    fwrite(STDERR, "Usage: php _scripts/credit_wallet.php <email> <amount>\n");
    exit(1);
}

$root = __DIR__ . '/..';
require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = $argv[1];
$amount = (float) $argv[2];

/** @var App\Models\User|null $user */
$user = App\Models\User::where('email', $email)->first();
if (! $user) {
    fwrite(STDERR, "User not found: $email\n");
    exit(2);
}

/** @var App\Services\WalletService $walletService */
$walletService = app(App\Services\WalletService::class);
$walletService->credit($user, $amount, App\Enums\TransactionType::DEPOSIT, null, 'Test funding');

echo json_encode([
    'user' => $user->email,
    'balance' => (string) $user->wallet->balance,
    'currency' => $user->wallet->currency,
], JSON_PRETTY_PRINT) . "\n";