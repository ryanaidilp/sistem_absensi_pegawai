<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Notifications\BirthdayNotification;
use App\Repositories\Interfaces\UserRepositoryInterface;

class CheckForBirthday extends Command
{

    private $userRepository;

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
    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = $this->userRepository->allByBirthday(today());
        if ($users->count() > 0) {
            foreach ($users as $user) {
                $dob = Carbon::parse($user->date_of_birth);
                $user->notify(new BirthdayNotification);
                sendNotification("$user->name berulang tahun yang ke-{$dob->age} hari ini. Berikan doa dan ucapan terbaik kalian.", "Ulang tahun hari ini, " . today()->translatedFormat('l, d F Y') . "!");
            }
            $this->info('Successfully send birthday message');
        } else {
            $this->error('There are no employees whose birthdays today!');
        }

        return 0;
    }
}
