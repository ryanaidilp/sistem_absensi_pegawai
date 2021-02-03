<?php

namespace App\Exports;

use App\Exports\Sheets\Daily\HonorerAttendeSheet;
use App\Exports\Sheets\Daily\PnsAttendeSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendeDailyExport implements
    FromCollection,
    WithMultipleSheets
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
        //
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new PnsAttendeSheet($this->users, $this->date);
        $sheets[] = new HonorerAttendeSheet($this->users, $this->date);
        return $sheets;
    }
}
