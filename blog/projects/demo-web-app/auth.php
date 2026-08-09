<?php
/**
 * Simple authentication module
 * Demonstrates: bcrypt hashing, cookie sessions, math CAPTCHA
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generateCaptcha(): array {
    $a = rand(1, 20);
    $b = rand(1, 20);
    $op = rand(0, 1) ? '+' : '-';
    if ($op === '-' && $a < $b) { $tmp = $a; $a = $b; $b = $tmp; }
    $answer = $op === '+' ? $a + $b : $a - $b;
    return [
        'question' => "$a $op $b = ?",
        'answer'   => $answer,
    ];
}

function loginUser(string $username, bool $isAdmin = false): void {
    setcookie('username', $username, time() + 86400 * 7, '/');
    setcookie('is_admin', $isAdmin ? 'true' : 'false', time() + 86400 * 7, '/');
}

function logoutUser(): void {
    setcookie('username', '', time() - 3600, '/');
    setcookie('is_admin', '', time() - 3600, '/');
}

function isLoggedIn(): bool {
    return isset($_COOKIE['username']) && $_COOKIE['username'] !== '';
}

function isAdmin(): bool {
    return isLoggedIn() && isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true';
}
