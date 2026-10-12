<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
                if (! Schema::hasTable('settings')) {
                    return $default;
                }
                $record = static::find($key);
                if (! $record || $record->value === null) {
                    return $default;
                }
                $decoded = json_decode($record->value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $record->value;
            });
        } catch (Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
            $stored = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
            static::updateOrCreate(['key' => $key], ['value' => $stored]);
            Cache::forget("setting.{$key}");
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * List of enabled gateway names. Default: all configured gateways.
     *
     * @return array<string>
     */
    public static function enabledGateways(): array
    {
        return static::get('enabled_gateways', ['paystack', 'tsara', 'nomba']);
    }

    public static function isGatewayEnabled(string $name): bool
    {
        return in_array($name, static::enabledGateways(), true);
    }

    public static function setGatewayEnabled(string $name, bool $enabled): void
    {
        $current = static::enabledGateways();
        if ($enabled && ! in_array($name, $current, true)) {
            $current[] = $name;
        } elseif (! $enabled) {
            $current = array_values(array_filter($current, fn ($g) => $g !== $name));
        }
        static::set('enabled_gateways', $current);
    }
}
