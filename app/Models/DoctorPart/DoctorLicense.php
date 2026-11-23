<?php

namespace App\Models\DoctorPart;

use Illuminate\Database\Eloquent\Model;

class DoctorLicense extends Model
{

protected $primaryKey = 'license_number';
public $incrementing = false;
public $timestamps = false;

     protected $table ="doctor_licenses";
protected $hidden = [
    "syndicate_number",
    "syndicate_branch",
    "issue_date",
        "workplace_type",
    "license_level ",
    "last_updated",




];
protected $casts = [
    "issue_date" => "datetime",
    "expiry_date" => "datetime",
    "last_updated" => "datetime",
];


   public function doctor()
    {
        return $this->hasOne(Doctor::class,'license_table_id');

    }
}