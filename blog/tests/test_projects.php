<?php
/**
 * Seam 5: Project File Path Safety
 *
 * Tests that the path traversal prevention in projects/index.php works:
 *   - basename() strips directory components
 *   - realpath() resolves the true path
 *   - str_starts_with() gates access to project directory only
 */

echo "[Seam 5] Project File Path Safety\n";

// Replicate the exact gating logic from projects/index.php
function isPathSafe(string $projectId, string $fileName, string $projectsDir): bool {
    $filePath = $projectsDir . '/' . $projectId . '/' . basename($fileName);
    if (!file_exists($filePath)) return false;
    $realFilePath = realpath($filePath);
    $realProjectDir = realpath($projectsDir . '/' . $projectId);
    if ($realFilePath === false || $realProjectDir === false) return false;
    return str_starts_with($realFilePath, $realProjectDir);
}

// Set up test fixtures
$testDir = __DIR__ . '/../projects/';

// Ensure demo project exists
$demoDir = $testDir . 'demo-web-app';
if (!is_dir($demoDir)) mkdir($demoDir, 0777, true);

test('basename strips directory traversal attempts', function () {
    // basename('../../../etc/passwd') should return just 'passwd'
    assertEquals('passwd', basename('../../../etc/passwd'));
    assertEquals('auth.php', basename('/etc/something/auth.php'));
    assertEquals('index.php', basename('index.php'));
    assertEquals('config', basename('/home/user/config'));
});

test('realpath resolves and validates actual paths', function () {
    // A real file should resolve
    $real = realpath(__FILE__);
    assertTrue($real !== false, 'realpath of existing file must not be false');
    assertTrue(strlen($real) > 0, 'realpath must return non-empty string');

    // A fake file should return false
    assertFalse(realpath('/nonexistent/path/file.txt'), 'realpath of nonexistent file must be false');
});

test('legitimate file access is allowed', function () use ($testDir) {
    // demo-web-app/auth.php exists → should be safe
    assertTrue(isPathSafe('demo-web-app', 'auth.php', $testDir),
        'reading auth.php from demo-web-app must be allowed');
});

test('directory traversal via ../ is blocked', function () use ($testDir) {
    // Attacker tries: ?project=demo-web-app&view=../../../wp-config.php
    assertFalse(isPathSafe('demo-web-app', '../../../wp-config.php', $testDir),
        'path traversal via ../ must be denied');
});

test('directory traversal via absolute path is blocked', function () use ($testDir) {
    // Attacker tries: ?project=demo-web-app&view=/etc/passwd
    assertFalse(isPathSafe('demo-web-app', '/etc/passwd', $testDir),
        'absolute path outside project must be denied');
});

test('access to non-existent project is denied', function () use ($testDir) {
    assertFalse(isPathSafe('nonexistent-project', 'file.php', $testDir),
        'non-existent project directory must be denied');
});

test('access to non-existent file in valid project is denied', function () use ($testDir) {
    assertFalse(isPathSafe('demo-web-app', 'secret-keys.txt', $testDir),
        'non-existent file in valid project must be denied');
});

test('str_starts_with prevents sibling directory escape', function () use ($testDir) {
    // Create a temp scenario: /projects/a/ and /projects/b/
    // Accessing /projects/a/ should not allow reaching /projects/b/file
    $dirA = $testDir . 'dir-a';
    $dirB = $testDir . 'dir-b';
    @mkdir($dirA); @mkdir($dirB);
    file_put_contents($dirB . '/secret.txt', 'hidden');

    // Attacker tries to access secret.txt via dir-a path
    assertFalse(isPathSafe('dir-a', '../dir-b/secret.txt', $testDir),
        'basename should strip ../dir-b/ prefix before realpath check');

    // Clean up
    @unlink($dirB . '/secret.txt');
    @rmdir($dirB);
    @rmdir($dirA);
});

echo "\n";
