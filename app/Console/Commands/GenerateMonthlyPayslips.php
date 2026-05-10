<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PayslipService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyPayslips extends Command
{
    protected $signature = 'hrms:generate-payslips
                            {--month= : Month number (1-12, defaults to last month)}
                            {--year=  : Year (defaults to current year)}
                            {--user=  : User ID to record as generator (defaults to first admin)}';

    protected $description = 'Bulk-generate PDF payslips for all paid payrolls in a given period';

    public function __construct(private readonly PayslipService $payslipService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = $this->resolvePeriod();
        $month  = $period['month'];
        $year   = $period['year'];
        $label  = Carbon::createFromDate($year, $month, 1)->format('F Y');

        $generator = $this->resolveGenerator();

        if (! $generator) {
            $this->error('No admin user found. Pass --user=<id> or seed an admin account.');
            return self::FAILURE;
        }

        $this->info("Generating payslips for {$label} (as {$generator->name})…");

        $results = $this->payslipService->generateBulk($month, $year, $generator);

        $this->info("  Created : {$results['created']}");
        $this->info("  Skipped : {$results['skipped']}");

        if (! empty($results['errors'])) {
            $this->warn('  Errors:');
            foreach ($results['errors'] as $err) {
                $this->warn("    - {$err}");
            }
        }

        return self::SUCCESS;
    }

    private function resolvePeriod(): array
    {
        $lastMonth = Carbon::now()->subMonthNoOverflow();

        return [
            'month' => (int) ($this->option('month') ?? $lastMonth->month),
            'year'  => (int) ($this->option('year')  ?? $lastMonth->year),
        ];
    }

    private function resolveGenerator(): ?User
    {
        if ($userId = $this->option('user')) {
            return User::find($userId);
        }

        // Fall back to the first admin account
        return User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
    }
}
