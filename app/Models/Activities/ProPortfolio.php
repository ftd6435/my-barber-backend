<?php

namespace App\Models\Activities;

use App\Models\User;
use App\Traits\CloudflareUpload;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['professionel_id', 'service_id', 'image', 'is_active'])]
class ProPortfolio extends Model
{
    use CloudflareUpload;

    protected $appends = [
        'image_url',
    ];

    public function professionel()
    {
        return $this->belongsTo(User::class, 'professionel_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get the portfolio image URL attribute.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return $this->getImageUrl($this->image, 'pro-portfolios');
        }
        // Return default avatar
        return null;
    }
}
