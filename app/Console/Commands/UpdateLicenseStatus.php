<?php

namespace App\Console\Commands;

use App\Models\DoctorPart\DoctorLicense;
use Illuminate\Console\Command;

class UpdateLicenseStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctorLicense:update-license-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update license status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*update active -> expire */
        DoctorLicense::where('expiry_date','<' ,now())
        ->where('license_status','active')
        ->update(['license_status'=>'expired']);

        /*update expire -> update */
        DoctorLicense::where('expiry_date','>' ,now())
        ->where('license_status','expired')
        ->update(['license_status'=>'active']);


           }
}
