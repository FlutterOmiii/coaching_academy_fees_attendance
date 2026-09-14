<?php

namespace App\Support;

class PersonName
{
    /**
     * Forms take one "Full Name" field; the database keeps first_name /
     * last_name. First word -> first_name, the rest -> last_name (may be empty
     * for single-word names).
     *
     * @return array{first_name: string, last_name: string}
     */
    public static function split(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [''];

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ];
    }
}
