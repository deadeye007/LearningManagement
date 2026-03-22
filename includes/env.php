<?php

function loadEnvironmentFile($filePath) {
    static $loadedFiles = [];

    if (isset($loadedFiles[$filePath]) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmedLine = trim($line);

        if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
            continue;
        }

        if (strpos($trimmedLine, '=') === false) {
            continue;
        }

        list($name, $value) = array_map('trim', explode('=', $trimmedLine, 2));
        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === '\'' && substr($value, -1) === '\'')
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    $loadedFiles[$filePath] = true;
}

loadEnvironmentFile(dirname(__DIR__) . '/.env');
?>
