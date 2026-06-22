<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCommissionSetting extends Model
{
    protected $fillable = [
        'percentage',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
