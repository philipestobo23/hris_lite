<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\DtrService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class BuildDtrCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'dtr:build
        {--employee= : Employee id (defaults to every employee)}
        {--from= : Start date, Y-m-d (defaults to the first of this month)}
        {--to= : End date, Y-m-d (defaults to today)}';

    /**
     * @var string
     */
    protected $description = 'Turn biometric punches into daily time records for a date range.';

    public function handle(DtrService $dtr): int
    {
        $from = $this->option('from')
            ? CarbonImmutable::parse($this->option('from'))
            : CarbonImmutable::now()->startOfMonth();

        $to = $this->option('to')
            ? CarbonImmutable::parse($this->option('to'))
            : CarbonImmutable::now();

        if ($to < $from) {
            $this->components->error('The --to date is before --from.');

            return self::FAILURE;
        }

        $employees = Employee::query()
            ->when($this->option('employee'), fn ($query) => $query->whereKey($this->option('employee')))
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            $this->components->warn('No employees to build.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Building %s to %s for %d employee(s).',
            $from->toDateString(),
            $to->toDateString(),
            $employees->count(),
        ));

        $built = 0;

        $this->withProgressBar($employees, function (Employee $employee) use ($dtr, $from, $to, &$built): void {
            $built += $dtr->buildRange($employee, $from, $to)->count();
        });

        $this->newLine(2);
        $this->components->info("{$built} daily time record(s) built.");

        return self::SUCCESS;
    }
}
