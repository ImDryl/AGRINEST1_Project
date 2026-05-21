<?php

namespace App\Payment;

use App\Entity\Order;

/**
 * Demo payment options for mobile checkout (no real gateway).
 */
final class OrderPaymentMethods
{
    /** @var array<string, string> id => display label */
    public const METHODS = [
        'gcash' => 'GCash',
        'cod' => 'Cash on Delivery',
        'card' => 'Credit / Debit Card',
    ];

    public static function isValidKey(string $key): bool
    {
        return isset(self::METHODS[strtolower(trim($key))]);
    }

    public static function labelForKey(string $key): ?string
    {
        $key = strtolower(trim($key));

        return self::METHODS[$key] ?? null;
    }

    /**
     * @return array<int, array{id: string, label: string, description: string}>
     */
    public static function apiItems(): array
    {
        return [
            [
                'id' => 'gcash',
                'label' => self::METHODS['gcash'],
                'description' => 'Pay via GCash (demo — no real charge).',
            ],
            [
                'id' => 'cod',
                'label' => self::METHODS['cod'],
                'description' => 'Pay when your order is delivered.',
            ],
            [
                'id' => 'card',
                'label' => self::METHODS['card'],
                'description' => 'Card payment (demo — no real charge).',
            ],
        ];
    }

    public static function resolvePaymentLabel(Order $order): string
    {
        $stored = trim((string) $order->getPaymentMethod());
        if ($stored !== '') {
            return $stored;
        }

        [, $legacy] = self::parseLegacyStatus($order->getStatus() ?? '');

        return $legacy;
    }

    public static function resolveStatus(Order $order): string
    {
        $stored = trim((string) $order->getPaymentMethod());
        if ($stored !== '') {
            return trim((string) ($order->getStatus() ?? 'Pending')) ?: 'Pending';
        }

        [$status] = self::parseLegacyStatus($order->getStatus() ?? 'Pending');

        return $status !== '' ? $status : 'Pending';
    }

    /**
     * Old mobile orders stored payment inside status, e.g. "Pending · GCash".
     *
     * @return array{0: string, 1: string}
     */
    public static function parseLegacyStatus(string $raw): array
    {
        if (preg_match('/^(.+?)\s*[\·|]\s*(.+)$/u', $raw, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$raw, ''];
    }
}
