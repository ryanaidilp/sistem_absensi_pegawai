<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Exports\Sheets\Monthly\PnsAttendeSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\Monthly\HonorerAttendeSheet;

class AttendeMonthlyExport implements FromCollection, WithMultipleSheets
{
    private $date;
    private $users;

    public function __construct($date, $users)
    {
        $this->date = $date;
        $this->users = $users;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->users;
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new PnsAttendeSheet($this->users, $this->date);
        $sheets[] = new HonorerAttendeSheet($this->users, $this->date);
        return $sheets;
    }
}
