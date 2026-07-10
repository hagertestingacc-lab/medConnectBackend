<?php

namespace App\Console\Commands;

use App\Models\AllUserPart\AllUser;
use App\Models\DoctorPart\DoctorLicense;
use App\Services\Doctor\DoctorActivities;
use Illuminate\Console\Command;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete unverified users after time period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiryHours= 1;
        $cutOff = now()->subHours($expiryHours);

                 
        
      $users=  AllUser::whereNull("email_verified_at")
         ->where("created_at",'<',$cutOff)
         ->get();
          
foreach($users as $user)
    {
        $public_id =$user->doctor->cloudinary_profile_img_id;
      DoctorActivities::deleteProfileImg($public_id);
      
       $user->delete();  
}
         
$count=$users->count();
        $this->info("Deleted {$count}  unverified users.");
        }
}