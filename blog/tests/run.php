<?php
/**
 * Test Runner — Cola_CaO Blog
 * Run: php tests/run.php
 */

$pass = 0; $fail = 0; $tests = [];

function test(string $name, callable $fn): void {
    global $pass, $fail, $tests;
    try {
        $fn();
        $pass++;
        echo "  PASS  $name\n";
    } catch (AssertionError $e) {
        $fail++;
        echo "  FAIL  $name — {$e->getMessage()}\n";
    } catch (Throwable $e) {
        $fail++;
        echo "  ERROR $name — {$e->getMessage()}\n";
    }
    $tests[] = $name;
}

function assertTrue($cond, string $msg = ''): void {
    if (!$cond) throw new AssertionError($msg ?: 'expected true, got false');
}

function assertFalse($cond, string $msg = ''): void {
    if ($cond) throw new AssertionError($msg ?: 'expected false, got true');
}

function assertEquals($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new AssertionError($msg ?: "expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
}

echo "\n=== Cola_CaO Blog Test Suite ===\n\n";

require __DIR__ . '/test_auth.php';
require __DIR__ . '/test_projects.php';

echo "\n---\n";
echo "Results: $pass passed, $fail failed, " . ($pass + $fail) . " total\n";

if ($fail > 0) exit(1);
