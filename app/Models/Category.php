<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'description', 'sort'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $cat = parent::resolveRouteBinding($value, $field);
        if ($cat) {
            return $cat;
        }

        // Backward compatibility: map legacy AI and Software category slugs to unified category
        if (in_array($value, ['ai-subscriptions', 'software-services', 'ai-subscriptions-software-services', 'ai-software-services'], true)) {
            return static::where('slug', 'ai-subscriptions-software-services')
                ->orWhere('slug', 'ai-subscriptions')
                ->orWhere('slug', 'software-services')
                ->first();
        }

        return null;
    }
}
