<?php

namespace App\Widgets;

use App\Models\Department;
use Illuminate\Support\Str;
use TCG\Voyager\Widgets\BaseDimmer;

class DepartmentWidget extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = Department::count();
        $string = "Departemen";

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-company',
            'title'  => "{$count} {$string}",
            'text'   => "Kamu memiliki $count " . Str::lower($string) . " di kantormu. Tekan tombol untuk melihat.",
            'button' => [
                'text' => $string,
                'link' => route('voyager.departments.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/03.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return true;
    }
}
