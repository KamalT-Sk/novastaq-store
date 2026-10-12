<?php

namespace App\Payments;

use App\Models\Setting;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    /** @param iterable<PaymentGateway> $gateways */
    public function __construct(iterable $gateways = [])
    {
        foreach ($gateways as $gateway) {
            $this->register($gateway);
        }
    }

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->name()] = $gateway;
    }

    public function isEnabled(string $name): bool
    {
        return isset($this->gateways[$name]) && Setting::isGatewayEnabled($name);
    }

    /** Returns only gateways currently enabled in admin */
    public function enabled(): array
    {
        return array_filter($this->gateways, fn ($g, $k) => $this->isEnabled($k), ARRAY_FILTER_USE_BOTH);
    }

    public function defaultName(): string
    {
        $enabled = array_keys($this->enabled());
        if (! empty($enabled)) {
            return $enabled[0];
        }

        return config('store.default_gateway', 'paystack');
    }

    public function get(?string $name = null): PaymentGateway
    {
        $name = $name ?: $this->defaultName();

        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not supported.");
        }

        return $this->gateways[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /** @return array<string, PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }

    public function default(): PaymentGateway
    {
        return $this->get($this->defaultName());
    }
}
