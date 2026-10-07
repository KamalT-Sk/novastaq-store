<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'phone',
        'password',
    ]; // role is deliberately not fillable: only the Team page, installer or console set it

    /**
     * Staff roles and what each can reach in the admin. A user with no role is a customer.
     * Areas: orders, catalogue (add/edit products & categories), publish (make live), delete, team, system.
     */
    public const ROLES = [
        'owner' => [
            'label' => 'Owner',
            'about' => 'Everything, including the team and system updates.',
            'can' => ['orders', 'catalogue', 'publish', 'delete', 'team', 'system'],
        ],
        'manager' => [
            'label' => 'Manager',
            'about' => 'Orders, products and categories. No team or system settings.',
            'can' => ['orders', 'catalogue', 'publish', 'delete'],
        ],
        'editor' => [
            'label' => 'Catalogue editor',
            'about' => "Adds and edits products, photos and categories. New products stay hidden until you make them live. Can't delete or see orders.",
            'can' => ['catalogue'],
        ],
        'orders' => [
            'label' => 'Order handler',
            'about' => "Sees orders, marks plans done and updates deliveries. Can't change products.",
            'can' => ['orders'],
        ],
    ];

    public function isStaff(): bool
    {
        return isset(self::ROLES[$this->role]);
    }

    public function hasAccess(string $area): bool
    {
        return in_array($area, self::ROLES[$this->role]['can'] ?? [], true);
    }

    public function roleLabel(): ?string
    {
        return self::ROLES[$this->role]['label'] ?? null;
    }

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
        ];
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }
}
