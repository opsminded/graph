<?php

declare(strict_types=1);

require_once __DIR__ . '/User.php';

final class GraphContext
{
    private static string $user;
    private static string $group;
    private static string $client_ip;

    public static function update(string $user, string $group, string $client_ip)
    {
        self::$user = $user;
        self::$group = $group;
        self::$client_ip = $client_ip;
    }

    public static function getUser(): string
    {
        return self::$user;
    }

    public static function getGroup(): string
    {
        return self::$group;
    }

    public static function getClientIP(): string
    {
        return self::$client_ip;
    }
}