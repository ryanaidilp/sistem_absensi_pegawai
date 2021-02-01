<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BirthdayNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckForBirthday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for employee`s birthday';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::whereMonth('date_of_birth', today()->format('m'))
            ->whereDay('date_of_birth', today()->format('d'))->get();
        if ($users->count() > 0) {
            foreach ($users as $user) {
                $dob = Carbon::parse($user->date_of_birth);
                $user->notify(new BirthdayNotification);
                sendNotification("$user->name berulang tahun yang ke {$dob->age} tahun hari ini. Berikan doa dan ucapan terbaik kalian.", "Ulang tahun hari ini, " . today()->translatedFormat('l, d F Y') . "!");
            }
            $this->info('Successfully send birthday message');
        } else {
            $this->error('There are no employees whose birthdays today!');
        }

        return 0;
    }
}
