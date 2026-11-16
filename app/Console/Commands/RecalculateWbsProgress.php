<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\WbsProgressService;
use Illuminate\Console\Command;

class RecalculateWbsProgress extends Command
{
    protected $signature = 'wbs:recalculate {project? : ID project, kosongkan untuk semua project}';

    protected $description = 'Recalculate parent WBS and project progress from the leaf tasks';

    public function handle(WbsProgressService $progress)
    {
        $projects = $this->argument('project')
            ? Project::whereKey($this->argument('project'))->get()
            : Project::orderBy('id')->get();

        if ($projects->isEmpty()) {
            $this->error('Project not found.');
            return self::FAILURE;
        }

        foreach ($projects as $project) {
            $result = $progress->recalculateProject($project->id);
            $this->line("[{$project->id}] {$project->name}: {$result}%");
        }

        $this->info('Selesai.');
        return self::SUCCESS;
    }
}
