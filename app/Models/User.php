<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Acteurs\Client;
use App\Models\Acteurs\Professionel;
use App\Models\Activities\Booking;
use App\Models\Activities\BookinReview;
use App\Models\Activities\ProAvailability;
use App\Models\Activities\ProPortfolio;
use App\Models\Activities\Service;
use App\Services\WalletService;
use App\Traits\CloudflareUpload;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['first_name', 'last_name', 'telephone', 'username', 'email', 'role', 'avatar', 'default_currency_id', 'is_phone_verified', 'is_email_verified', 'email_verified_at', 'phone_verified_at', 'is_approved', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, CloudflareUpload, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'avatar_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $model->username = $model->username ?? '@' . $model->first_name . rand(0000, 9999);
        });

        static::created(function (self $user) {
            if ($user->default_currency_id) {
                app(WalletService::class)->ensureWallet($user, $user->default_currency_id);
            }
        });

        static::updated(function (self $user) {
            if ($user->wasChanged('default_currency_id') && $user->default_currency_id) {
                app(WalletService::class)->ensureWallet($user, $user->default_currency_id);
            }
        });
    }

    public function professionel()
    {
        return $this->hasOne(Professionel::class);
    }

    public function professionelBookings()
    {
        return $this->hasMany(Booking::class, 'professionel_id');
    }

    public function clientBookings()
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    public function clientReviews()
    {
        return $this->hasMany(BookinReview::class, 'client_id');
    }

    public function professionelReviews()
    {
        return $this->hasMany(BookinReview::class, 'professionel_id');
    }

    public function salons()
    {
        return $this->hasMany(Salon::class, 'owner_id');
    }

    public function availabilities()
    {
        return $this->hasMany(ProAvailability::class, 'professionel_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'professionel_id');
    }

    public function defaultCurrency()
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function proPortfolios()
    {
        return $this->hasMany(ProPortfolio::class, 'professionel_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * Get the profile photo URL attribute.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return $this->getImageUrl($this->avatar, 'profile-photos');
        }
        // Return default avatar
        return $this->defaultProfilePhotoUrl();
    }

    /**
     * Get the default profile photo URL.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->first_name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }
}
