<?php

namespace App\Exports;

use App\Models\Permission;
use App\Models\Role;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    protected int $rowNumber = 0;
    protected int $totalPermissions;

    public function __construct()
    {
        $this->totalPermissions = Permission::count();
    }

    public function collection()
    {
        return Role::with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Role',
            'Kode',
            'Deskripsi',
            'Role Sistem',
            'Jumlah User',
            'Jumlah Hak Akses',
            'Permissions',
        ];
    }

    public function map($role): array
    {
        $isSuper = $role->isSuperRole();

        return [
            ++$this->rowNumber,
            $role->display_name,
            $role->name,
            $role->description ?? '-',
            $role->is_system ? 'Yes' : 'No',
            $role->users_count,
            $isSuper ? 'Semua' : $role->permissions->count() . ' / ' . $this->totalPermissions,
            $isSuper ? 'Semua hak akses' : ($role->permissions->pluck('display_name')->implode(', ') ?: '-'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Roles';
    }
}
