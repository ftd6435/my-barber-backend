<?php

namespace App\Models\Acteurs;

use App\Models\User;
use App\Traits\CloudflareUpload;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable(['user_id', 'business_name', 'bio', 'experience_years', 'mobile_service', 'travel_radius_km', 'country', 'city', 'address', 'document_type', 'document'])]
class Professionel extends Model
{
    use CloudflareUpload;

    protected $appends = [
        'document_url',
    ];

    #[Override]
    public function casts()
    {
        return [
            'mobile_service' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile photo URL attribute.
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if ($this->document) {
            return $this->getFileUrl($this->document, 'professionel-documents');
        }

        return null;
    }
}
