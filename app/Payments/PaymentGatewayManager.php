<?php

namespace App\Payments;

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

    public function get(?string $name = null): PaymentGateway
    {
        $name = $name ?: config('store.default_gateway', 'paystack');

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
        return $this->get();
    }
}
