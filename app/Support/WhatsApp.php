<?php

namespace App\Support;

use App\Models\Setting;

/**
 * WhatsApp click-to-chat helper.
 *
 * A wa.me link opens WhatsApp (app or web) with the message pre-filled to a
 * specific number; the staff member taps send. This is the free, no-approval
 * way to send a genuine WhatsApp — the paid Business API would be needed only
 * for fully unattended, bulk sending.
 */
class WhatsApp
{
    /**
     * Normalise a phone number to digits-with-country-code, as wa.me expects
     * (no +, no spaces). A bare 10-digit Indian number gets the configured
     * country code prepended.
     */
    public static function number(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        // Drop a leading 0 (local trunk prefix) before adding a country code.
        $digits = ltrim($digits, '0');

        if (strlen($digits) === 10) {
            $cc = preg_replace('/\D+/', '', (string) Setting::get('whatsapp_country_code', '91'));
            $digits = $cc.$digits;
        }

        // Sanity bounds for an international number.
        return (strlen($digits) >= 11 && strlen($digits) <= 15) ? $digits : null;
    }

    /** A wa.me deep link that opens WhatsApp with the message ready to send. */
    public static function link(?string $phone, string $message): ?string
    {
        $number = self::number($phone);

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
