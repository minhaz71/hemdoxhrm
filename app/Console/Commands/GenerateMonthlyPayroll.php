<?php

namespace App\Console\Commands;

use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyPayroll extends Command
{
    protected $signature = 'hrms:generate-payroll
                            {--month= : Month number (1-12, defaults to last month)}
                            {--year=  : Year (defaults to current year)}';

    protected $description = 'Bulk-generate payroll for all active employees for a given period';

    public function __construct(private readonly PayrollService $payrollService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = $this->resolvePeriod();
        $month  = $period['month'];
        $year   = $period['year'];
        $label  = Carbon::createFromDate($year, $month, 1)->format('F Y');

        $this->info("Generating payroll for {$label}…");

        $results = $this->payrollService->generateBulk($month, $year);

        $this->info("  Created : {$results['created']}");
        $this->info("  Skipped : {$results['skipped']} (already exists)");

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
        if ($this->option('month') && $this->option('year')) {
            return [
                'month' => (int) $this->option('month'),
                'year'  => (int) $this->option('year'),
            ];
        }

        // Default: last month
        $lastMonth = Carbon::now()->subMonthNoOverflow();

        return [
            'month' => (int) ($this->option('month') ?? $lastMonth->month),
            'year'  => (int) ($this->option('year')  ?? $lastMonth->year),
        ];
    }
}
