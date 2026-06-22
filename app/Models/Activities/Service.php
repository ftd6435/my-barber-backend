<?php

namespace App\Models\Activities;

use App\Models\Currency;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['professionel_id', 'salon_id', 'category_id', 'currency_id', 'name', 'duration_minutes', 'is_active'])]
class Service extends Model
{
    use SoftDeletes;

    public function professionel()
    {
        return $this->belongsTo(User::class, 'professionel_id');
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function servicePrices()
    {
        return $this->hasMany(ServicePrice::class, 'service_id');
    }

    public function portfolios()
    {
        return $this->hasMany(ProPortfolio::class, 'service_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'service_id');
    }
}
