<?php

namespace App\Http\Controllers;

use App\Support\PersonName;

abstract class Controller
{
    /**
     * Forms take one "Full Name" field; the database keeps first_name /
     * last_name so nothing downstream changes.
     *
     * @return array{first_name: string, last_name: string}
     */
    protected function splitFullName(string $fullName): array
    {
        return PersonName::split($fullName);
    }
}
