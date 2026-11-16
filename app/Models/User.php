<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'gender',
        'photo',
        'active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Public URL of the uploaded photo, null when the user has none.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    /**
     * Initials for the avatar placeholder, e.g. "Rina Kartika" -> "RK".
     */
    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Permission names of this user's role, cached per request.
     */
    private ?array $permissionNames = null;

    /**
     * users.role (string, used by the older code) and users.role_id are kept in sync.
     */
    protected static function booted()
    {
        static::saving(function (User $user) {
            if ($user->isDirty('role_id') && $user->role_id) {
                $user->role = Role::whereKey($user->role_id)->value('name') ?? $user->role;
            } elseif ($user->isDirty('role')) {
                $user->role_id = Role::where('name', $user->role)->value('id');
            }
        });
    }

    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === Role::SUPER_ROLE) {
            return true;
        }

        if ($this->permissionNames === null) {
            $this->permissionNames = $this->userRole
                ? $this->userRole->permissions()->pluck('name')->all()
                : [];
        }

        return in_array($permission, $this->permissionNames, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Users whose role has the given permission (admin excluded),
     * e.g. User::withPermission('tasks.view') = users that can be assigned as PIC.
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query
            ->where('role', '!=', Role::SUPER_ROLE)
            ->whereHas('userRole.permissions', fn ($q) => $q->where('name', $permission));
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPM()
    {
        return $this->role === 'pm';
    }
}
