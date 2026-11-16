<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\WbsItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProjectReportExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithTitle,
    WithEvents
{
    protected $project;
    protected $rowNumber = 0;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function collection()
    {
        return WbsItem::where('project_id', $this->project->id)
            ->with(['assignedUser', 'deliverables'])
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'WBS Code',
            'Task Name',
            'Type',
            'Assigned To',
            'Start Date',
            'End Date',
            'Weight (%)',
            'Progress (%)',
            'Status',
            'Deliverables Count',
            'Description'
        ];
    }

    public function map($wbsItem): array
    {
        $this->rowNumber++;
        
        $status = 'Not Started';
        if ($wbsItem->progress >= 100) {
            $status = 'Completed';
        } elseif ($wbsItem->progress > 0) {
            $status = 'In Progress';
        }

        return [
            $this->rowNumber,
            $wbsItem->code ?? '-',
            $wbsItem->name,
            ucfirst($wbsItem->type),
            $wbsItem->assignedUser->name ?? '-',
            $wbsItem->start_date ?? '-',
            $wbsItem->end_date ?? '-',
            $wbsItem->weight ?? 0,
            $wbsItem->progress ?? 0,
            $status,
            $wbsItem->deliverables->count(),
            $wbsItem->description ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // header style
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Project Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Auto-size columns
                foreach(range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Add borders to all cells
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Add project info at the top
                $sheet->insertNewRowBefore(1, 3);
                
                $sheet->setCellValue('A1', 'PROJECT REPORT');
                $sheet->mergeCells('A1:L1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '1F2937']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                $sheet->setCellValue('A2', 'Project: ' . $this->project->name);
                $sheet->mergeCells('A2:L2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                $sheet->setCellValue('A3', 'Generated: ' . now()->format('d M Y H:i'));
                $sheet->mergeCells('A3:L3');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // Adjust row heights
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
            }
        ];
    }
}