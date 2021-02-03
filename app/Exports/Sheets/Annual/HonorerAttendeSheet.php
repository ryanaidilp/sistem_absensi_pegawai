<?php

namespace App\Exports\Sheets\Annual;

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
    private $month;
    private $date;

    public function __construct($users, $month, $date)
    {
        $this->date = Carbon::create($date->year, $month, 1);
        $this->month = $month;
        $this->users = $users;
    }

    /**
     * @return Builder
     */
    public function collection()
    {
        $users = $this->users->filter(function ($user) {
            return $user['status'] === 'Honorer';
        })->sortByDesc(function ($user) {
            return collect($user['presensi'])->average('percentage');
        })->values();

        $users = $users->map(function ($user) {
            $presences = collect($user['presensi']);
            $presences = $presences->filter(function ($presence) {
                return Carbon::parse($presence['date'])->month === $this->month;
            })->values();
            return array_merge($user, ['presensi' => $presences]);
        });

        dd($users->first());

        return $users;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->date->translatedFormat('F');
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:B3');
        $sheet->mergeCells('C1:C3');
        $sheet->mergeCells('D1:D3');
        $sheet->mergeCells('E1:E3');
        $sheet->mergeCells('F1:Y1');
        $sheet->mergeCells('F2:Y2');
        $sheet->mergeCells('Z1:Z3');
        $sheet->setCellValueExplicit('Z1', 'Rata-Rata', DataType::TYPE_STRING);
        for ($i = 1; $i <= 13; $i++) {
            $cellIndex = $i + 3;
            $sheet->setCellValueExplicit('Z' . $cellIndex, "=AVERAGE(F$cellIndex:Y$cellIndex)", DataType::TYPE_FORMULA);
            $sheet->getStyle("Z$cellIndex")->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
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

            $conditionalStyles = $sheet->getStyle("Z$cellIndex")->getConditionalStyles();
            $conditionalStyles[] = $conditional1;
            $conditionalStyles[] = $conditional2;
            $conditionalStyles[] = $conditional3;
            $sheet->getStyle("Z$cellIndex")->setConditionalStyles($conditionalStyles);
        }
        $sheet->setAutoFilter('Z1:Z16');
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
        ];
    }

    public function headings(): array
    {
        $data = [
            '',
            '',
            '',
            '',
            ''
        ];

        foreach ($this->collection()->first()['presensi'] as $presence) {
            array_push($data, Carbon::parse($presence['date'])->format('d'));
        }

        return [
            [
                'Nama',
                'Bagian',
                'Jabatan',
                'Jenis Kelamin',
                'Status',
                Str::upper($this->date->translatedFormat('F')),
            ],
            [
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
        ];

        foreach ($user['presensi'] as $presence) {
            array_push($data, $presence['percentage']);
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        ];
    }
}
