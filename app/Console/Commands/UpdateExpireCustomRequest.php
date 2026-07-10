<?php

namespace App\Console\Commands;

use App\Models\customRequestsPart\customRequest;
use Illuminate\Console\Command;

class UpdateExpireCustomRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:update-expire-custom-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update custom request to expire status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
$requests=customRequest::where("expires_at",">=",now())
           ->where("status","!=","expired")->get();

foreach($requests as $request)
    {
  $request::update(["status"=>"expired"]);
  
$this->info("Record ID {$request->id} marked as expired.");
}
$this->info("Processed {$requests->count()} records.");
}

}