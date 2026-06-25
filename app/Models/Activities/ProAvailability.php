<?php

namespace App\Models\Activities;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['professionel_id', 'day_of_week', 'start_time', 'end_time', 'is_active'])]
class ProAvailability extends Model
{
    public function professionel()
    {
        return $this->belongsTo(User::class, 'professionel_id');
    }
}
