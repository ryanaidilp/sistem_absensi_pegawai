<?php

namespace App\Exports\Sheets\Monthly;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class PnsAttendeSheet implements
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
    private $lastCell;

    public function __construct($users, $date)
    {
        $this->users = $users;
        $this->date = $date;
        $this->lastCell = 7 + \count($this->users->first()['presensi']);
    }

    /**
     * @return Builder
     */
    public function collection()
    {
        return $this->users->filter(function ($user) {
            return $user['status'] === 'PNS';
        })->sortByDesc(function ($user) {
            return collect($user['presensi'])->average('percentage');
        })->values();
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PNS';
    }

    public function styles(Worksheet $sheet)
    {
        $averageCell = \num2alpha($this->lastCell + 1);
        $dataCount = $this->collection()->count();
        $lastCell = \num2alpha($this->lastCell);
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:B3');
        $sheet->mergeCells('C1:C3');
        $sheet->mergeCells('D1:D3');
        $sheet->mergeCells('E1:E3');
        $sheet->mergeCells('F1:F3');
        $sheet->mergeCells('G1:G3');
        $sheet->mergeCells('H1:H3');
        $sheet->mergeCells("I1:{$lastCell}1");
        $sheet->mergeCells("I2:{$lastCell}2");
        $sheet->mergeCells("{$averageCell}1:{$averageCell}3");
        $sheet->setCellValueExplicit("{$averageCell}1", 'Rata-Rata', DataType::TYPE_STRING);
        for ($i = 1; $i <= $dataCount; $i++) {
            $cellIndex = $i + 3;
            $sheet->setCellValueExplicit($averageCell . $cellIndex, "=AVERAGE(I$cellIndex:{$lastCell}$cellIndex)", DataType::TYPE_FORMULA);
            $sheet->getStyle("{$averageCell}$cellIndex")->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            $conditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional1->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHANOREQUAL);
            $conditional1->addCondition('50');
            $conditional1->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKRED);
            $conditional1->getStyle()->getFont()->setBold(true);

            $conditional2 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional2->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional2->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_BETWEEN);
            $conditional2->addCondition('50');
            $conditional2->addCondition('80');
            $conditional2->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKYELLOW);
            $conditional2->getStyle()->getFont()->setBold(true);

            $conditional3 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional3->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional3->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHANOREQUAL);
            $conditional3->addCondition('80');
            $conditional3->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN);
            $conditional3->getStyle()->getFont()->setBold(true);

            $conditionalStyles = $sheet->getStyle("{$averageCell}$cellIndex")->getConditionalStyles();
            $conditionalStyles[] = $conditional1;
            $conditionalStyles[] = $conditional2;
            $conditionalStyles[] = $conditional3;
            $sheet->getStyle("{$averageCell}$cellIndex")->setConditionalStyles($conditionalStyles);
        }
        $autoFilterCell = "{$averageCell}1:{$averageCell}" . ($dataCount + 4);
        $sheet->setAutoFilter($autoFilterCell);
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
            ]
        ];
    }

    public function headings(): array
    {
        $data = [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        foreach ($this->users->first()['presensi'] as $presence) {
            array_push($data, Carbon::parse($presence['date'])->format('d'));
        }

        return [
            [
                'Nama',
                'Bagian',
                'Jabatan',
                'Jenis Kelamin',
                'Status',
                'NIP',
                'Golongan',
                'Pangkat',
                Str::upper($this->date->translatedFormat('F')),
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Tanggal',
            ],
            $data
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
            $user['nip'],
            $user['group'],
            $user['rank'],
        ];

        foreach ($user['presensi'] as $presence) {
            array_push($data, $presence['percentage']);
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            \num2alpha($this->lastCell + 1) => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        ];
    }
}
