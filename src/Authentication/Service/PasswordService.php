<?php
declare(strict_types=1);

namespace App\Authentication\Service;

class PasswordService
{

    public function hashPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    public function generatePassword(int $length = 8): string
    {
        return bin2hex(random_bytes($length / 2));
    }

}
