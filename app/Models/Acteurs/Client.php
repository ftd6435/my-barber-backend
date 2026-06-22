<?php

namespace App\Models\Acteurs;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable(['user_id', 'country', 'city', 'address', 'latitude', 'longitude'])]
class Client extends Model
{

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
