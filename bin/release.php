<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(\STDERR, "Usage: composer release <version>\n");
    exit(1);
}

$version = ltrim($argv[1], 'v');

if (!preg_match('/^\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\d+)?$/', $version)) {
    fwrite(\STDERR, "Invalid version format. Expected semver (e.g. 1.0.1, 2.0.0-beta1).\n");
    exit(1);
}

$rootDir = dirname(__DIR__);
$pluginFile = $rootDir . '/health-check.php';

$content = file_get_contents($pluginFile);

// Update plugin header version
$content = preg_replace(
    '/\*\s*Version:\s*\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\d+)?/',
    '* Version: ' . $version,
    $content,
);

// Update HEALTH_CHECK_VERSION constant
$content = preg_replace(
    "/define\('HEALTH_CHECK_VERSION',\s*'[^']+'\);/",
    "define('HEALTH_CHECK_VERSION', '" . $version . "');",
    $content,
);

file_put_contents($pluginFile, $content);

echo "Updated version to $version in health-check.php\n";

// Validate the modified file is valid PHP
passthru('php -l ' . escapeshellarg($pluginFile), $lintCode);
if ($lintCode !== 0) {
    fwrite(\STDERR, "PHP lint check failed after version update.\n");
    exit(1);
}

// Commit, tag, push
$commands = [
    "git add health-check.php",
    "git commit -m \"feat: bump version to $version\"",
    "git tag v$version",
    "git push origin main",
    "git push origin v$version",
];

foreach ($commands as $cmd) {
    echo "> $cmd\n";
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        fwrite(\STDERR, "Command failed with exit code $exitCode\n");
        exit(1);
    }
}

echo "\n✓ Release v$version pushed. CI will build and publish the zip.\n";
