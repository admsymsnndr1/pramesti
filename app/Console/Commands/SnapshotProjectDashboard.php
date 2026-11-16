<?php

namespace App\Console\Commands;

use App\Services\ProjectDashboardService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotProjectDashboard extends Command
{
    protected $signature = 'dashboard:snapshot {--date= : Tanggal snapshot (Y-m-d), default hari ini}';

    protected $description = 'Simpan jumlah tasklist per status dan progress WBS semua project (data tren & S-curve dashboard admin project)';

    public function handle(ProjectDashboardService $dashboard)
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'), ProjectDashboardService::TIMEZONE)->startOfDay()
            : ProjectDashboardService::today();

        $count = $dashboard->snapshotAll($date);

        $this->info("Snapshot {$date->toDateString()} disimpan untuk {$count} project.");
        return self::SUCCESS;
    }
}
