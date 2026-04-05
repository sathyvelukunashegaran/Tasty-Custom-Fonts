<?php

declare(strict_types=1);

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'TastyFonts\\';

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = dirname(__DIR__) . '/includes/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
);

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected === $actual) {
        return;
    }

    throw new RuntimeException(
        $message . "\nExpected: " . var_export($expected, true) . "\nActual:   " . var_export($actual, true)
    );
}

function assertContainsValue(string $needle, string $haystack, string $message): void
{
    if (str_contains($haystack, $needle)) {
        return;
    }

    throw new RuntimeException($message . "\nMissing: " . $needle . "\nHaystack: " . $haystack);
}

function assertNotContainsValue(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        return;
    }

    throw new RuntimeException($message . "\nUnexpected: " . $needle . "\nHaystack: " . $haystack);
}
