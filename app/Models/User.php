<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'ai_token_limit',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'ai_token_limit' => 'integer',
        ];
    }

    /**
     * Apakah pengguna memiliki permission tertentu (server-side).
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->role?->can($permission) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Apakah pengguna boleh mengelola seluruh konten (bukan hanya miliknya).
     */
    public function managesAllContent(): bool
    {
        return $this->role?->managesAllContent() ?? false;
    }

    /**
     * Apakah pengguna bertugas memoderasi konten.
     */
    public function isModerator(): bool
    {
        return $this->hasPermission(Permission::ModerateContent);
    }

    /**
     * Relasi destinasi yang dikelola pengguna ini.
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class, 'manager_id');
    }

    /**
     * Relasi usaha UMKM milik pengguna ini.
     */
    public function umkmBusinesses(): HasMany
    {
        return $this->hasMany(UmkmBusiness::class, 'owner_id');
    }

    /**
     * Relasi penginapan yang dikelola pengguna ini.
     */
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class, 'manager_id');
    }

    /**
     * Relasi artikel budaya yang ditulis pengguna ini.
     */
    public function cultureArticles(): HasMany
    {
        return $this->hasMany(CultureArticle::class, 'author_id');
    }

    /**
     * Relasi event yang dikelola pengguna ini.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    /**
     * Favorit pengguna ini.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Ulasan pengguna ini.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Rencana perjalanan pengguna ini.
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    /**
     * Riwayat percakapan AI Trip Planner milik pengguna ini.
     */
    public function tripPlannerMessages(): HasMany
    {
        return $this->hasMany(TripPlannerMessage::class);
    }

    /**
     * Kuota prompt AI Trip Planner milik pengguna ini.
     */
    public function tripPlannerUsages(): HasMany
    {
        return $this->hasMany(TripPlannerUsage::class);
    }

    /**
     * Catatan pemakaian token AI milik pengguna ini.
     */
    public function aiUsages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }

    public function roleLabel(): string
    {
        return $this->role?->label() ?? '-';
    }

    /**
     * Kontrol akses panel Filament.
     *
     * Guest ditolak oleh middleware; pengguna terautentikasi hanya boleh masuk
     * bila perannya diizinkan mengakses panel (wisatawan dilarang).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role?->canAccessPanel() ?? false;
    }
}
