<?php

namespace App\Models;

use App\Models\DoctorPart\Doctor;
use Illuminate\Database\Eloquent\Model;

class EquipmentList extends Model
{
    protected $table = 'equipment_lists';

    protected $fillable = [
        'doctor_id',
        'list_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function items()
    {
        return $this->hasMany(EquipmentListItem::class, 'list_id');
    }
}
