<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'discover.php';

$projects = labhub_discover_local_projects($projectsMaxDepth, $projectsSkipDirs);
$projects = array_values(array_filter(
  $projects,
  static function (array $host): bool {
    $manifest = is_array($host['manifest'] ?? null) ? $host['manifest'] : [];
    $type = strtolower((string) ($manifest['type'] ?? ''));

    return $type !== 'hidden';
  }
));

echo json_encode(['projects' => $projects], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
