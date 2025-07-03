<?php

declare(strict_types=1);

$request = json_decode(file_get_contents("php://stdin"), true);

$directory = $request['directory'] ?? null;
if (!$directory || !is_dir($directory)) {
    echo json_encode(['error' => 'Invalid or missing directory path']);
    exit(1);
}

// 呼び出し例
$output = shell_exec("php-class-diagram \"$directory\"");
echo json_encode(['plantuml' => $output]);
