<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$hubConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'hub.json';
$localHubHosts = ['professor.local', 'isaque.local'];

if (is_readable($hubConfigFile)) {
  $hubConfig = json_decode((string) file_get_contents($hubConfigFile), true);
  if (is_array($hubConfig['localHubHosts'] ?? null)) {
    $localHubHosts = array_values(array_filter(
      array_map('strval', $hubConfig['localHubHosts']),
      static fn (string $host): bool => $host !== ''
    ));
  }
}

$requestHost = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$requestHost = strtolower($requestHost);

$isLocalHub = $requestHost === 'localhost'
  || $requestHost === '127.0.0.1'
  || $requestHost === '[::1]'
  || str_ends_with($requestHost, '.local')
  || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)\d+\.\d+$/', $requestHost) === 1
  || in_array($requestHost, array_map('strtolower', $localHubHosts), true);

if (!$isLocalHub) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Estações disponíveis apenas no hub local.']);
  exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'discover.php';

$hostsFile = LABHUB_ASSETS . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'hosts.json';
$hostsData = ['updated_at' => null, 'hosts' => []];

if (is_readable($hostsFile)) {
  $decoded = json_decode((string) file_get_contents($hostsFile), true);
  if (is_array($decoded)) {
    $hostsData = $decoded;
  }
}

$updatedAt = is_string($hostsData['updated_at'] ?? null) ? $hostsData['updated_at'] : null;
$hostsForPage = [];

foreach (is_array($hostsData['hosts'] ?? null) ? $hostsData['hosts'] : [] as $host) {
  if (is_array($host)) {
    $hostsForPage[] = labhub_strip_host_for_storage($host);
  }
}

$stations = labhub_enrich_hosts_live($hostsForPage, 2);
$stations = labhub_sort_hosts_list($stations);
$isDiscoverAdmin = labhub_is_discover_admin($discoverAdminHosts);

echo json_encode([
  'ok' => true,
  'updatedAt' => $updatedAt,
  'isDiscoverAdmin' => $isDiscoverAdmin,
  'stations' => $stations,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
