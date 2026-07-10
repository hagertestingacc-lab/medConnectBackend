<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernal extends ConsoleKernel
{


public function schedule(Schedule $schedule)
{
$schedule->command("user:delete-unverified")->daily();
$schedule->command("doctorLicense:update-license-status")->daily();
}



}
