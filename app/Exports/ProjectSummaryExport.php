<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectSummaryExport implements WithMultipleSheets
{
    protected $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function sheets(): array
    {
        return [
            new ProjectReportExport($this->project),
            new DeliverableReportSheet($this->project),
        ];
    }
}