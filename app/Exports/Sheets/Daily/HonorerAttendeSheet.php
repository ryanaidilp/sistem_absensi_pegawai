<?php

namespace App\Exports\Sheets\Daily;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HonorerAttendeSheet implements
    WithTitle,
    WithStyles,
    WithMapping,
    WithHeadings,
    FromCollection,
    ShouldAutoSize,
    WithColumnFormatting,
    WithStrictNullComparison
{
    private $users;
    private $date;

    public function __construct($users, $date)
    {
        $this->users = $users;
        $this->date = $date;
    }

    public function collection()
    {
        return $this->users->filter(function ($user) {
            return $user['status'] === 'Honorer';
        })->sortByDesc(function ($user) {
            return collect($user['presensi'])->average('percentage');
        })->values();
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Honorer';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:B3');
        $sheet->mergeCells('C1:C3');
        $sheet->mergeCells('D1:D3');
        $sheet->mergeCells('E1:E3');
        $sheet->mergeCells('F1:M1');
        $sheet->mergeCells('F2:G2');
        $sheet->mergeCells('H2:I2');
        $sheet->mergeCells('J2:K2');
        $sheet->mergeCells('L2:M2');
        $sheet->mergeCells('N1:N3');
        $sheet->setCellValueExplicit('N1', 'Rata-Rata', DataType::TYPE_STRING);
        $sheet->setAutoFilter('N1:N16');
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ]
            ],
            2 => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            3 => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'F' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'G' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'H' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'I' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'J' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'K' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'L' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
            'M' => [
                'alignment' => [
                    'vertical' => 'center',
                    'horizontal' => 'center',
                ]
            ],
        ];
    }

    public function headings(): array
    {
        return [
            [
                'Nama',
                'Bagian',
                'Jabatan',
                'Jenis Kelamin',
                'Status',
                'Absen',
                'Rata-Rata'
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'Pagi',
                '',
                'ISHOMA',
                '',
                'Siang',
                '',
                'Pulang',
                '',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'Status',
                'Jam',
                'Status',
                'Jam',
                'Status',
                'Jam',
                'Status',
                'Jam',
            ]
        ];
    }

    public function map($user): array
    {
        $data = [
            $user['name'],
            $user['department'],
            $user['position'],
            $user['gender'],
            $user['status'],
        ];

        foreach ($user['presensi'] as $presence) {
            array_push($data, $presence['status'] . " ({$presence['percentage']}%)");
            array_push($data, $presence['attend_time']);
        }
        array_push($data, collect($user['presensi'])->average('percentage'));

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        ];
    }
}
