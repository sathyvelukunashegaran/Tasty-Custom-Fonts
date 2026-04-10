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

function assertTrueValue(bool $actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

function assertFalseValue(bool $actual, string $message): void
{
    assertSameValue(false, $actual, $message);
}

function assertArrayHasKeys(array $expectedKeys, array $actual, string $message): void
{
    $missingKeys = array_values(array_diff($expectedKeys, array_keys($actual)));

    if ($missingKeys === []) {
        return;
    }

    throw new RuntimeException(
        $message
        . "\nMissing keys: " . implode(', ', $missingKeys)
        . "\nActual keys: " . implode(', ', array_keys($actual))
    );
}

function assertWpErrorCode(string $expectedCode, mixed $actual, string $message): WP_Error
{
    if (!$actual instanceof WP_Error) {
        throw new RuntimeException(
            $message . "\nExpected a WP_Error but received: " . get_debug_type($actual)
        );
    }

    assertSameValue($expectedCode, $actual->get_error_code(), $message);

    return $actual;
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

function assertMatchesPattern(string $pattern, string $subject, string $message): void
{
    if (preg_match($pattern, $subject) === 1) {
        return;
    }

    throw new RuntimeException($message . "\nPattern: " . $pattern . "\nSubject: " . $subject);
}
