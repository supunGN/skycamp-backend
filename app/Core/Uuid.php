<?php

/**
 * UUID Generator Class
 * Generates RFC 4122 compliant UUIDs
 */

class Uuid
{
    /**
     * Generate a version 4 UUID
     */
    public static function generate(): string
    {
        // Generate 16 random bytes
        $data = random_bytes(16);

        // Set version (4) and variant bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits

        // Format as UUID string
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

    /**
     * Generate a version 4 UUID without hyphens
     */
    public static function generateShort(): string
    {
        return str_replace('-', '', self::generate());
    }

    /**
     * Validate UUID format
     */
    public static function isValid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Generate UUID from namespace and name (version 5)
     */
    public static function generateFromNamespace(string $namespace, string $name): string
    {
        // Convert namespace UUID to binary
        $nsBinary = hex2bin(str_replace('-', '', $namespace));

        // Create SHA-1 hash
        $hash = sha1($nsBinary . $name, true);

        // Set version (5) and variant bits
        $hash[6] = chr(ord($hash[6]) & 0x0f | 0x50); // Version 5
        $hash[8] = chr(ord($hash[8]) & 0x3f | 0x80); // Variant bits

        // Format as UUID string
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($hash, 0, 4)),
            bin2hex(substr($hash, 4, 2)),
            bin2hex(substr($hash, 6, 2)),
            bin2hex(substr($hash, 8, 2)),
            bin2hex(substr($hash, 10, 6))
        );
    }

    /**
     * Generate sequential UUID (for database primary keys)
     */
    public static function generateSequential(): string
    {
        // Use timestamp for first part
        $timestamp = dechex(time());

        // Generate random part
        $random = bin2hex(random_bytes(10));

        // Format as UUID
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            $timestamp,
            substr($random, 0, 4),
            substr($random, 4, 4),
            substr($random, 8, 4),
            substr($random, 12, 12)
        );
    }
}
