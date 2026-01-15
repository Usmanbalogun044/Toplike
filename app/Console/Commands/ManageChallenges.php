<?php

namespace App\Console\Commands;

use App\Services\ChallengeService;
use App\Services\WalletService;
use Illuminate\Console\Command;

class ManageChallenges extends Command
{
    protected $signature = 'challenge:manage';
    protected $description = 'Close ended challenges, settle payouts, and create the next weekly challenge if needed';

    public function __construct(private readonly ChallengeService $challengeService, private readonly WalletService $walletService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Running weekly challenge maintenance...');
        $this->challengeService->settleAndRotate($this->walletService);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
