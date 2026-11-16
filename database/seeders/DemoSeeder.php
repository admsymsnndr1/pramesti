<?php

namespace Database\Seeders;

use App\Models\Deliverable;
use App\Models\Project;
use App\Models\ProjectDailySnapshot;
use App\Models\Role;
use App\Models\User;
use App\Models\WbsItem;
use App\Services\ProjectDashboardService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fictional demo data: users for every role, five projects with a WBS,
 * deliverables and a month of daily snapshots for the trend charts.
 *
 * Every demo account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    private const DAYS_OF_HISTORY = 30;

    /**
     * Phase => [weight, [task => weight | [sub task => weight]]]
     */
    private array $template = [
        'Analysis & Design' => [20, [
            'Requirement gathering' => 30,
            'User flow & wireframes' => 30,
            'Database design' => 20,
            'UI design' => 20,
        ]],
        'Development' => [50, [
            'Backend API' => [
                'Authentication & roles' => 25,
                'Core modules' => 50,
                'Reporting endpoints' => 25,
            ],
            'Frontend' => [
                'Layout & navigation' => 20,
                'Forms & validation' => 40,
                'Dashboard & charts' => 40,
            ],
        ]],
        'Testing' => [20, [
            'Functional testing' => 40,
            'User acceptance test' => 40,
            'Bug fixing' => 20,
        ]],
        'Deployment' => [10, [
            'Server setup' => 30,
            'Data migration' => 40,
            'Go-live & handover' => 30,
        ]],
    ];

    private Carbon $today;

    private array $users = [];

    private int $picTurn = 0;

    private int $statusTurn = 0;

    public function run()
    {
        $this->today = ProjectDashboardService::today();
        mt_srand(2024); // same demo data on every run

        $this->createUsers();

        $projects = [
            ['CPR-01', 'Customer Portal Revamp', 'Self-service portal for customers to track orders, invoices and support tickets.', -60, 45, 'ongoing'],
            ['WIS-02', 'Warehouse Inventory System', 'Stock in/out, stock opname and low-stock alerts for three warehouses.', -30, 90, 'ongoing'],
            ['HRA-03', 'HR Attendance Mobile App', 'Mobile check-in with geofencing and automatic monthly attendance recap.', -90, 10, 'ongoing'],
            ['KB-04', 'Internal Knowledge Base', 'Searchable documentation hub for internal SOPs and onboarding material.', -150, -20, 'completed'],
            ['DWM-05', 'Data Warehouse Migration', 'Move reporting from spreadsheets to a central data warehouse.', 20, 140, 'draft'],
        ];

        foreach ($projects as [$code, $name, $description, $startOffset, $endOffset, $status]) {
            $project = Project::create([
                'code'        => $code,
                'name'        => $name,
                'description' => $description,
                'start_date'  => $this->today->copy()->addDays($startOffset),
                'end_date'    => $this->today->copy()->addDays($endOffset),
                'status'      => $status,
                'pm_id'       => $this->users['pm'][0]->id,
                'created_by'  => $this->users['admin'][0]->id,
            ]);

            $this->createWbs($project);
            $this->createSnapshots($project);
        }
    }

    private function createUsers(): void
    {
        $people = [
            'admin'         => [['Admin Demo', 'admin@example.com', 'male']],
            'pm'            => [['Rina Kartika', 'pm@example.com', 'female']],
            'team_leader'   => [['Budi Santoso', 'lead@example.com', 'male']],
            'developer'     => [
                ['Dimas Pratama', 'dev@example.com', 'male'],
                ['Sari Wulandari', 'sari@example.com', 'female'],
                ['Fajar Nugroho', 'fajar@example.com', 'male'],
            ],
            'qa'            => [['Maya Lestari', 'qa@example.com', 'female']],
            'admin_project' => [['Andi Wijaya', 'analytics@example.com', 'male']],
        ];

        $password = Hash::make('password');

        foreach ($people as $role => $list) {
            $roleId = Role::where('name', $role)->value('id');

            foreach ($list as [$name, $email, $gender]) {
                $this->users[$role][] = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $password,
                    'gender'   => $gender,
                    'active'   => true,
                    'role_id'  => $roleId,
                ]);
            }
        }
    }

    private function createWbs(Project $project): void
    {
        $start = Carbon::parse($project->start_date);
        $end = Carbon::parse($project->end_date);
        $totalDays = max(1, $start->diffInDays($end));

        $cursor = $start->copy();
        $phaseNo = 0;

        foreach ($this->template as $phaseName => [$phaseWeight, $tasks]) {
            $phaseNo++;
            $phaseEnd = $phaseNo === count($this->template)
                ? $end->copy()
                : $cursor->copy()->addDays((int) round($totalDays * $phaseWeight / 100));

            $phase = $this->item($project, null, (string) $phaseNo, $phaseName, 'wbs', $phaseWeight, $cursor, $phaseEnd);
            $this->createChildren($project, $phase, $tasks, $cursor, $phaseEnd, $phaseName === 'Testing');

            $cursor = $phaseEnd->copy()->addDay();
        }

        // drafts have not started yet
        if ($project->status === 'draft') {
            return;
        }

        foreach ($project->wbsItems()->whereDoesntHave('children')->get() as $leaf) {
            $this->progressLeaf($project, $leaf);
        }
    }

    /**
     * Children are scheduled one after another inside the parent's period.
     */
    private function createChildren(Project $project, WbsItem $parent, array $children, Carbon $from, Carbon $to, bool $qa): void
    {
        $days = max(1, $from->diffInDays($to));
        $slot = max(1, intdiv($days, count($children)));
        $cursor = $from->copy();
        $no = 0;

        foreach ($children as $name => $value) {
            $no++;
            $childEnd = $no === count($children) ? $to->copy() : $cursor->copy()->addDays($slot - 1);
            $code = $parent->code . '.' . $no;

            if (is_array($value)) {
                $group = $this->item($project, $parent, $code, $name, 'wbs', 50, $cursor, $childEnd);
                $this->createChildren($project, $group, $value, $cursor, $childEnd, $qa);
            } else {
                $this->item($project, $parent, $code, $name, 'task', $value, $cursor, $childEnd, $this->nextPic($qa));
            }

            $cursor = $childEnd->copy()->addDay();
        }
    }

    private function item(Project $project, ?WbsItem $parent, string $code, string $name, string $type, float $weight, Carbon $start, Carbon $end, ?User $pic = null): WbsItem
    {
        return WbsItem::create([
            'project_id' => $project->id,
            'parent_id'  => $parent?->id,
            'pic_id'     => $pic?->id,
            'code'       => $code,
            'name'       => $name,
            'type'       => $type,
            'weight'     => $weight,
            'progress'   => 0,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'sort_order' => (int) last(explode('.', $code)),
            'is_locked'  => $type === 'wbs',
            'created_by' => $this->users['pm'][0]->id,
            'description' => $type === 'task' ? "Deliver \"{$name}\" for {$project->name}." : null,
        ]);
    }

    /**
     * Progress follows the schedule: finished periods are done, running ones are
     * partly done (a bit behind or ahead), future ones have not started.
     */
    private function progressLeaf(Project $project, WbsItem $leaf): void
    {
        $start = Carbon::parse($leaf->start_date);
        $end = Carbon::parse($leaf->end_date);

        if ($project->status === 'completed' || $end->lt($this->today->copy()->subDays(21))) {
            $progress = 100;
        } elseif ($end->lt($this->today)) {
            // recently due: mostly done, some still in testing or revision
            $progress = mt_rand(0, 2) ? 100 : mt_rand(16, 19) * 5;
        } elseif ($start->gt($this->today)) {
            $progress = 0;
        } else {
            $planned = ($start->diffInDays($this->today) + 1) / ($start->diffInDays($end) + 1) * 100;
            $progress = max(10, min(95, round($planned * mt_rand(60, 110) / 100 / 5) * 5));
        }

        $status = null;
        if ($progress > 0 && $progress < 100) {
            $status = ['on_progress', 'on_testing', 'on_progress', 'revisi_tester'][$this->statusTurn++ % 4];
        }

        $leaf->update(array_filter([
            'progress' => $progress,
            'status'   => $status,
        ], fn ($value) => $value !== null));

        if ($progress >= 60) {
            $this->createDeliverable($leaf, $progress >= 100 ? 'approved' : 'submitted');
        }
    }

    private function createDeliverable(WbsItem $leaf, string $status): void
    {
        Deliverable::create([
            'wbs_item_id' => $leaf->id,
            'name'        => $leaf->name . ' - document',
            'description' => 'Result of the task, reviewed by the QA team.',
            'url'         => 'https://example.com/docs/' . \Illuminate\Support\Str::slug($leaf->name),
            'status'      => $status,
            'uploaded_by' => $leaf->pic_id ?? $this->users['developer'][0]->id,
        ]);
    }

    private function nextPic(bool $qa): User
    {
        if ($qa) {
            return $this->users['qa'][0];
        }

        $team = array_merge($this->users['developer'], $this->users['team_leader']);

        return $team[$this->picTurn++ % count($team)];
    }

    /**
     * Backfill a month of weekday snapshots that grow towards today's numbers,
     * as if `dashboard:snapshot` had been running every evening.
     */
    private function createSnapshots(Project $project): void
    {
        if ($project->status === 'draft') {
            return;
        }

        $metrics = app(ProjectDashboardService::class)->metrics($project->fresh());
        $counts = $metrics['counts'];

        for ($daysAgo = self::DAYS_OF_HISTORY; $daysAgo >= 1; $daysAgo--) {
            $date = $this->today->copy()->subDays($daysAgo);
            if ($date->isWeekend()) {
                continue;
            }

            // completed projects were already done during the whole month
            $share = $project->status === 'completed'
                ? 1
                : pow((self::DAYS_OF_HISTORY - $daysAgo + 1) / (self::DAYS_OF_HISTORY + 1), 0.8) * 0.85 + 0.1;

            $complete = (int) floor($counts['complete'] * $share);
            $onTesting = (int) floor($counts['on_testing'] * $share);
            $revisi = (int) floor($counts['revisi_tester'] * $share);
            $onProgress = (int) floor($counts['on_progress'] * $share);

            ProjectDailySnapshot::create([
                'project_id'     => $project->id,
                'snapshot_date'  => $date->toDateString(),
                'open'           => $metrics['total'] - $complete - $onTesting - $revisi - $onProgress,
                'on_progress'    => $onProgress,
                'on_testing'     => $onTesting,
                'revisi_tester'  => $revisi,
                'complete'       => $complete,
                'total'          => $metrics['total'],
                'progress'       => round($metrics['progress'] * $share, 2),
                'level_progress' => array_map(fn ($value) => round($value * $share, 2), $metrics['level_progress']),
            ]);
        }
    }
}
