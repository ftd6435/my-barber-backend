<?php

namespace App\Models;

use App\Models\Activities\Service;
use App\Traits\CloudflareUpload;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['owner_id', 'name', 'description', 'address', 'salon_phone', 'salon_email', 'latitude', 'longitude', 'logo', 'banner', 'is_active'])]
class Salon extends Model
{
    use SoftDeletes, CloudflareUpload;

    protected $appends = [
        'logo_url',
        'banner_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * Get the salon logo URL attribute.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return $this->getImageUrl($this->logo, 'salons-photos');
        }
        // Return default logo
        return $this->defaultLogoUrl();
    }

    /**
     * Get the default profile photo URL.
     */
    protected function defaultLogoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the salon logo URL attribute.
     */
    public function getBannerUrlAttribute(): ?string
    {
        if ($this->banner) {
            return $this->getImageUrl($this->banner, 'salons-photos');
        }
        // Return default banner
        return null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'salon_id');
    }
}
