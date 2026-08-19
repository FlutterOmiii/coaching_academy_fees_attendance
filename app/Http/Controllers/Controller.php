<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Forms take one "Full Name" field; the database keeps first_name /
     * last_name so nothing downstream changes. First word → first_name,
     * the rest → last_name (may be empty for single-word names).
     *
     * @return array{first_name: string, last_name: string}
     */
    protected function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [''];

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ];
    }
}
