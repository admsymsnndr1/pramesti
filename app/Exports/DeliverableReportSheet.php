<?php

namespace App\Exports;

use App\Models\Deliverable;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DeliverableReportSheet implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithTitle
{
    protected $project;
    protected $rowNumber = 0;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function collection()
    {
        return Deliverable::whereHas('task', function($q) {
            $q->where('project_id', $this->project->id);
        })
        ->with(['task', 'uploader'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Deliverable Name',
            'Task',
            'Uploader',
            'Upload Date',
            'Status',
            'File Name',
            'Notes'
        ];
    }

    public function map($deliverable): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $deliverable->name,
            $deliverable->task->name ?? '-',
            $deliverable->uploader->name ?? '-',
            $deliverable->created_at->format('d M Y H:i'),
            ucfirst($deliverable->status),
            $deliverable->file_path ? basename($deliverable->file_path) : '-',
            $deliverable->notes ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669']
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Deliverables';
    }
}