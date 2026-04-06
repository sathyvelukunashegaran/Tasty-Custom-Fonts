<?php

declare(strict_types=1);

/**
 * @param array<string, callable> $tests
 */
function runTestSuite(array $tests): never
{
    $failures = 0;

    foreach ($tests as $name => $test) {
        try {
            $test();
            echo "[PASS] {$name}\n";
        } catch (Throwable $throwable) {
            $failures++;
            echo "[FAIL] {$name}\n";
            echo $throwable->getMessage() . "\n";
        }
    }

    exit($failures > 0 ? 1 : 0);
}
