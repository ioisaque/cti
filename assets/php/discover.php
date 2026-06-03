<?php

declare(strict_types=1);

const LABHUB_STATION_FILE = 'station.json';
const LABHUB_PROJECT_MANIFEST = 'manifest.json';
define('LABHUB_ROOT', dirname(__DIR__, 2));
define('LABHUB_ASSETS', dirname(__DIR__));

$hostsFile = LABHUB_ASSETS . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'hosts.json';
$configFile = LABHUB_ASSETS . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'discover.conf';
$projectsMaxDepth = 5;
$projectsSkipDirs = ['assets', '.git', 'node_modules', 'vendor'];
$subnetPrefix = null;

$ports = [80, 8080];
$timeoutSeconds = 1;
$requireApacheHeader = false;
$scanStart = 1;
$scanEnd = 254;
$localHostname = 'isaque.local';
$discoverAdminHosts = ['isaque.local', 'professor.local'];

if (is_readable($configFile)) {
  $config = parse_ini_file($configFile, false, INI_SCANNER_TYPED);
  if (is_array($config)) {
    if (!empty($config['ports']) && is_string($config['ports'])) {
      $ports = array_values(array_filter(array_map('intval', preg_split('/\s+/', trim($config['ports'])))));
    }
    if (isset($config['timeout'])) {
      $timeoutSeconds = max(1, (int) $config['timeout']);
    }
    if (isset($config['require_apache_header'])) {
      $requireApacheHeader = (bool) $config['require_apache_header'];
    }
    if (isset($config['scan_start'])) {
      $scanStart = max(1, min(254, (int) $config['scan_start']));
    }
    if (isset($config['scan_end'])) {
      $scanEnd = max(1, min(254, (int) $config['scan_end']));
    }
    if (!empty($config['subnet_prefix']) && is_string($config['subnet_prefix'])) {
      $subnetPrefix = trim($config['subnet_prefix'], ".\t\n\r\0\x0B");
    }
    if (!empty($config['local_hostname']) && is_string($config['local_hostname'])) {
      $localHostname = trim($config['local_hostname']);
    }
    if (isset($config['projects_max_depth'])) {
      $projectsMaxDepth = max(1, min(10, (int) $config['projects_max_depth']));
    }
    if (!empty($config['projects_skip_dirs']) && is_string($config['projects_skip_dirs'])) {
      $projectsSkipDirs = array_values(array_filter(preg_split('/\s+/', trim($config['projects_skip_dirs']))));
    }
    if (!empty($config['discover_admin_hosts']) && is_string($config['discover_admin_hosts'])) {
      $discoverAdminHosts = array_values(array_filter(preg_split('/\s+/', trim($config['discover_admin_hosts']))));
    }
  }
}

function labhub_resolve_admin_host_ip(string $adminHost): ?string
{
  $adminHost = strtolower(trim($adminHost));
  if ($adminHost === '') {
    return null;
  }

  if (filter_var($adminHost, FILTER_VALIDATE_IP)) {
    return $adminHost;
  }

  $resolved = gethostbyname($adminHost);
  if ($resolved === $adminHost) {
    return null;
  }

  return $resolved;
}

function labhub_viewer_admin_host(array $adminHosts): ?string
{
  $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
  $localIp = labhub_local_ipv4();

  foreach ($adminHosts as $adminHost) {
    if (!is_string($adminHost) || trim($adminHost) === '') {
      continue;
    }

    $normalized = strtolower(trim($adminHost));
    $adminIp = labhub_resolve_admin_host_ip($normalized);
    if ($adminIp === null) {
      continue;
    }

    if ($adminIp === $remoteAddr) {
      return $normalized;
    }

    if (
      $localIp !== null
      && $adminIp === $localIp
      && in_array($remoteAddr, ['127.0.0.1', '::1'], true)
    ) {
      return $normalized;
    }
  }

  return null;
}

function labhub_hub_admin_host(array $adminHosts): ?string
{
  $localIp = labhub_local_ipv4();
  if ($localIp === null) {
    return null;
  }

  foreach ($adminHosts as $adminHost) {
    if (!is_string($adminHost) || trim($adminHost) === '') {
      continue;
    }

    $normalized = strtolower(trim($adminHost));
    $adminIp = labhub_resolve_admin_host_ip($normalized);
    if ($adminIp !== null && $adminIp === $localIp) {
      return $normalized;
    }
  }

  return null;
}

function labhub_should_hide_station(array $host, ?string $viewerAdminHost, ?string $hubAdminHost): bool
{
  if ($viewerAdminHost === null || $viewerAdminHost === '') {
    return false;
  }

  $viewerAdminHost = strtolower($viewerAdminHost);
  $displayHost = strtolower((string) ($host['display_host'] ?? ''));
  $hostIp = (string) ($host['ip'] ?? '');

  if ($displayHost === $viewerAdminHost) {
    return true;
  }

  $viewerIp = labhub_resolve_admin_host_ip($viewerAdminHost);
  if ($viewerIp !== null && $hostIp === $viewerIp) {
    return true;
  }

  if (
    $hubAdminHost !== null
    && $viewerAdminHost === strtolower($hubAdminHost)
    && $hostIp === '127.0.0.1'
  ) {
    return true;
  }

  return false;
}

function labhub_is_discover_admin(array $adminHosts): bool
{
  return labhub_viewer_admin_host($adminHosts) !== null;
}

function labhub_ensure_hosts_file_writable(string $hostsFile): array
{
  $dataDir = dirname($hostsFile);

  if (!is_dir($dataDir)) {
    if (!@mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
      return [
        'ok' => false,
        'error' => 'Não foi possível criar a pasta de dados: ' . $dataDir,
      ];
    }
  }

  if (!is_writable($dataDir)) {
    return [
      'ok' => false,
      'error' => 'Sem permissão de escrita na pasta: ' . $dataDir,
    ];
  }

  if (is_file($hostsFile) && !is_writable($hostsFile)) {
    return [
      'ok' => false,
      'error' => 'Sem permissão de escrita em: ' . $hostsFile,
    ];
  }

  $tempFile = $hostsFile . '.tmp';
  if (@file_put_contents($tempFile, "{}\n") === false) {
    return [
      'ok' => false,
      'error' => 'Não foi possível gravar arquivo de teste: ' . $tempFile,
    ];
  }

  @unlink($tempFile);

  return ['ok' => true, 'error' => null];
}

function labhub_exit_hosts_not_writable(array $check): void
{
  $message = is_string($check['error'] ?? null) && $check['error'] !== ''
    ? $check['error']
    : 'hosts.json não está gravável.';

  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
  }

  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'discover.php') {
  if (!labhub_is_discover_admin($discoverAdminHosts)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => false, 'error' => 'Somente hosts em discover_admin_hosts podem executar a descoberta.']);
    exit;
  }

  $writableCheck = labhub_ensure_hosts_file_writable($hostsFile);
  if (!$writableCheck['ok']) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => false, 'error' => $writableCheck['error']]);
    exit;
  }

  header('Content-Type: application/json; charset=UTF-8');

  $shellScript = LABHUB_ASSETS . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'discover-hosts.sh';
  $command = '/bin/bash ' . escapeshellarg($shellScript) . ' --sync 2>&1';
  exec($command, $output, $exitCode);

  if ($exitCode !== 0) {
    echo json_encode([
      'ok' => false,
      'error' => 'Falha ao executar varredura na rede.',
      'output' => implode("\n", $output),
    ]);
    exit;
  }

  if (!is_readable($hostsFile)) {
    echo json_encode(['ok' => false, 'error' => 'hosts.json não encontrado após a varredura.']);
    exit;
  }

  $payload = json_decode((string) file_get_contents($hostsFile), true);
  if (!is_array($payload)) {
    echo json_encode(['ok' => false, 'error' => 'hosts.json inválido após a varredura.']);
    exit;
  }

  $response = [
    'ok' => true,
    'updated_at' => $payload['updated_at'] ?? null,
    'host_count' => $payload['host_count'] ?? 0,
    'subnet' => $payload['subnet'] ?? null,
    'preserved' => !empty($payload['scan_preserved']),
    'warning' => is_string($payload['scan_warning'] ?? null) ? $payload['scan_warning'] : null,
  ];
  echo json_encode($response);
  exit;
}

function labhub_subnet_prefix(): ?string
{
  $commands = [
    'ipconfig getifaddr en0 2>/dev/null',
    'ipconfig getifaddr en1 2>/dev/null',
    'ipconfig getifaddr en2 2>/dev/null',
  ];

  foreach ($commands as $command) {
    $ip = trim((string) shell_exec($command));
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
      }
    }
  }

  $ifconfig = (string) shell_exec('ifconfig 2>/dev/null');
  if ($ifconfig !== '' && preg_match_all('/^\s+inet (\d+\.\d+\.\d+\.\d+)(?:\s|$)/m', $ifconfig, $matches)) {
    foreach ($matches[1] as $ip) {
      if (strpos($ip, '127.') === 0) {
        continue;
      }
      if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        continue;
      }
      $parts = explode('.', $ip);
      if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
      }
    }
  }

  return null;
}

function labhub_local_ipv4(): ?string
{
  $commands = [
    'ipconfig getifaddr en0 2>/dev/null',
    'ipconfig getifaddr en1 2>/dev/null',
    'ipconfig getifaddr en2 2>/dev/null',
  ];

  foreach ($commands as $command) {
    $ip = trim((string) shell_exec($command));
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return $ip;
    }
  }

  $ifconfig = (string) shell_exec('ifconfig 2>/dev/null');
  if ($ifconfig !== '' && preg_match_all('/^\s+inet (\d+\.\d+\.\d+\.\d+)(?:\s|$)/m', $ifconfig, $matches)) {
    foreach ($matches[1] as $ip) {
      if (strpos($ip, '127.') === 0) {
        continue;
      }
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ip;
      }
    }
  }

  return null;
}

function labhub_curl_probe(string $url, int $timeoutSeconds): array
{
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_NOBODY => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 2,
    CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
    CURLOPT_TIMEOUT => $timeoutSeconds,
    CURLOPT_USERAGENT => 'CTI-LabHub-Discover/1.0',
  ]);

  $response = curl_exec($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);

  $serverHeader = '';
  if (is_string($response) && $response !== '') {
    if (preg_match('/^Server:\s*(.+)$/im', $response, $matches)) {
      $serverHeader = trim($matches[1]);
    }
  }

  return [
    'ok' => $httpCode >= 200 && $httpCode < 400,
    'http_code' => $httpCode,
    'server' => $serverHeader,
    'error' => $error,
  ];
}

function labhub_fetch_manifest(string $baseUrl, int $timeoutSeconds): ?array
{
  $manifestUrl = rtrim($baseUrl, '/') . '/' . LABHUB_STATION_FILE;
  $ch = curl_init($manifestUrl);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 2,
    CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
    CURLOPT_TIMEOUT => $timeoutSeconds,
    CURLOPT_USERAGENT => 'CTI-LabHub-Discover/1.0',
  ]);

  $body = curl_exec($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($httpCode !== 200 || !is_string($body) || $body === '') {
    return null;
  }

  $manifest = json_decode($body, true);
  return is_array($manifest) ? $manifest : null;
}

function labhub_icon_ok(string $iconUrl, int $timeoutSeconds): bool
{
  $ch = curl_init($iconUrl);
  curl_setopt_array($ch, [
    CURLOPT_NOBODY => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
    CURLOPT_TIMEOUT => $timeoutSeconds,
    CURLOPT_USERAGENT => 'CTI-LabHub-Discover/1.0',
  ]);
  curl_exec($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

  return $httpCode >= 200 && $httpCode < 400;
}

function labhub_is_apache(array $probe): bool
{
  if ($probe['server'] !== '' && stripos($probe['server'], 'apache') !== false) {
    return true;
  }

  return false;
}

function labhub_host_id(string $ip, int $port): string
{
  return str_replace('.', '-', $ip) . '-' . $port;
}

function labhub_host_asset_url(array $host): string
{
  $ip = is_string($host['ip'] ?? null) ? $host['ip'] : '';
  $port = (int) ($host['port'] ?? 80);
  if ($ip === '') {
    return '';
  }

  if (is_string($host['asset_url'] ?? null) && $host['asset_url'] !== '') {
    return $host['asset_url'];
  }

  return 'http://' . $ip . ($port === 80 ? '/' : ':' . $port . '/');
}

function labhub_manifest_name(?array $manifest, string $fallback): string
{
  if (!is_array($manifest) || $manifest === []) {
    return $fallback;
  }

  foreach (['name', 'aluno', 'nome', 'app_name'] as $key) {
    if (is_string($manifest[$key] ?? null) && $manifest[$key] !== '') {
      return $manifest[$key];
    }
  }

  return $fallback;
}

function labhub_apply_manifest_to_host(array $host, ?array $manifest): array
{
  $displayHost = is_string($host['display_host'] ?? null) && $host['display_host'] !== ''
    ? $host['display_host']
    : (is_string($host['ip'] ?? null) ? $host['ip'] : '');

  $assetUrl = labhub_host_asset_url($host);
  $host['manifest'] = is_array($manifest) ? $manifest : null;
  $host['has_manifest'] = is_array($manifest) && $manifest !== [];
  $host['name'] = labhub_manifest_name($manifest, $displayHost);
  $host['icon_url'] = null;

  if ($host['has_manifest'] && !empty($manifest['icons'][0]['src']) && is_string($manifest['icons'][0]['src'])) {
    $iconSrc = $manifest['icons'][0]['src'];
    $host['icon_url'] = preg_match('/^https?:\/\//i', $iconSrc)
      ? $iconSrc
      : rtrim($assetUrl, '/') . '/' . ltrim($iconSrc, '/');
  }

  $host['has_photo'] = $host['has_manifest']
    && is_string($host['icon_url'])
    && $host['icon_url'] !== '';

  return $host;
}

function labhub_enrich_hosts_live(array $hosts, int $timeoutSeconds = 2): array
{
  $mh = curl_multi_init();
  $handles = [];

  foreach ($hosts as $index => $host) {
    $ip = is_string($host['ip'] ?? null) ? $host['ip'] : '';
    if ($ip === '') {
      continue;
    }

    if ($ip === '127.0.0.1') {
      $port = (int) ($host['port'] ?? 80);
      $manifest = null;
      $localManifestFile = dirname(LABHUB_ROOT) . DIRECTORY_SEPARATOR . LABHUB_STATION_FILE;
      if (is_readable($localManifestFile)) {
        $fromFile = json_decode((string) file_get_contents($localManifestFile), true);
        if (is_array($fromFile)) {
          $manifest = $fromFile;
        }
      }
      if ($manifest === null) {
        $manifest = labhub_fetch_manifest('http://127.0.0.1' . ($port === 80 ? '/' : ':' . $port . '/'), $timeoutSeconds);
      }
      $hosts[$index] = labhub_apply_manifest_to_host($host, $manifest);
      continue;
    }

    $assetUrl = labhub_host_asset_url($host);
    $manifestUrl = rtrim($assetUrl, '/') . '/' . LABHUB_STATION_FILE;
    $ch = curl_init($manifestUrl);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 2,
      CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
      CURLOPT_TIMEOUT => $timeoutSeconds,
      CURLOPT_USERAGENT => 'CTI-LabHub-Discover/1.0',
    ]);

    $handles[] = [
      'index' => $index,
      'host' => $host,
      'handle' => $ch,
    ];
    curl_multi_add_handle($mh, $ch);
  }

  $running = null;
  do {
    curl_multi_exec($mh, $running);
    if ($running > 0) {
      if (curl_multi_select($mh, 1.0) === -1) {
        usleep(100000);
      }
    }
  } while ($running > 0);

  foreach ($handles as $item) {
    $ch = $item['handle'];
    $body = curl_multi_getcontent($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_multi_remove_handle($mh, $ch);

    $manifest = null;
    if ($httpCode === 200 && is_string($body) && $body !== '') {
      $decoded = json_decode($body, true);
      if (is_array($decoded)) {
        $manifest = $decoded;
      }
    }

    $hosts[$item['index']] = labhub_apply_manifest_to_host($item['host'], $manifest);
  }

  curl_multi_close($mh);

  return $hosts;
}

function labhub_strip_host_for_storage(array $host): array
{
  $assetUrl = labhub_host_asset_url($host);

  return [
    'id' => $host['id'] ?? '',
    'ip' => $host['ip'] ?? '',
    'port' => (int) ($host['port'] ?? 80),
    'url' => $host['url'] ?? '',
    'asset_url' => $assetUrl,
    'display_host' => is_string($host['display_host'] ?? null) && $host['display_host'] !== ''
      ? $host['display_host']
      : ($host['ip'] ?? ''),
    'server' => $host['server'] ?? '',
    'online' => $host['online'] ?? true,
    'last_seen' => $host['last_seen'] ?? date('c'),
  ];
}

function labhub_host_is_ready(array $host): bool
{
  return !empty($host['has_manifest']) && !empty($host['has_photo']);
}

function labhub_host_is_master(array $host): bool
{
  $manifest = $host['manifest'] ?? null;
  if (!is_array($manifest)) {
    return false;
  }

  return !empty($manifest['master']);
}

function labhub_sort_hosts_by_name_ip(array $a, array $b): int
{
  $aMaster = labhub_host_is_master($a);
  $bMaster = labhub_host_is_master($b);
  if ($aMaster !== $bMaster) {
    return $bMaster <=> $aMaster;
  }

  $ipA = is_string($a['ip'] ?? null) ? $a['ip'] : '';
  $ipB = is_string($b['ip'] ?? null) ? $b['ip'] : '';
  $nameA = is_string($a['name'] ?? null) ? $a['name'] : $ipA;
  $nameB = is_string($b['name'] ?? null) ? $b['name'] : $ipB;

  $aHasStudentName = is_array($a['manifest'] ?? null) && ($a['manifest'] ?? []) !== [];
  $bHasStudentName = is_array($b['manifest'] ?? null) && ($b['manifest'] ?? []) !== [];

  if ($aHasStudentName && $bHasStudentName) {
    $byName = strcasecmp($nameA, $nameB);
    if ($byName !== 0) {
      return $byName;
    }
  }

  $ipLongA = $ipA !== '' ? (int) sprintf('%u', ip2long($ipA)) : 0;
  $ipLongB = $ipB !== '' ? (int) sprintf('%u', ip2long($ipB)) : 0;
  if ($ipLongA !== $ipLongB) {
    return $ipLongA <=> $ipLongB;
  }

  return ($a['port'] ?? 0) <=> ($b['port'] ?? 0);
}

function labhub_sort_hosts_list(array $hostsList): array
{
  $readyHosts = [];
  $otherHosts = [];

  foreach ($hostsList as $host) {
    if (labhub_host_is_ready($host)) {
      $readyHosts[] = $host;
    } else {
      $otherHosts[] = $host;
    }
  }

  usort($readyHosts, 'labhub_sort_hosts_by_name_ip');
  usort($otherHosts, 'labhub_sort_hosts_by_name_ip');

  return array_merge($readyHosts, $otherHosts);
}

function labhub_discover_local_projects(int $maxDepth, array $skipDirs): array
{
  $hubRoot = LABHUB_ROOT;
  $apacheDocRoot = dirname($hubRoot);
  if (!is_dir($hubRoot)) {
    return [];
  }

  $hubRootReal = realpath($hubRoot);
  if ($hubRootReal === false) {
    return [];
  }

  $skipLookup = array_fill_keys($skipDirs, true);
  $projects = [];
  $seenPaths = [];

  $scan = static function (string $dir, int $depth) use (
    &$scan,
    $hubRootReal,
    $apacheDocRoot,
    $maxDepth,
    $skipLookup,
    &$projects,
    &$seenPaths
  ): void {
    if ($depth > $maxDepth) {
      return;
    }

    $baseName = basename($dir);
    if (isset($skipLookup[$baseName])) {
      return;
    }

    $manifestFile = $dir . DIRECTORY_SEPARATOR . LABHUB_PROJECT_MANIFEST;
    if (is_file($manifestFile) && $dir !== $hubRootReal) {
      $relativePath = substr($dir, strlen($apacheDocRoot) + 1);
      if ($relativePath === false || $relativePath === '') {
        return;
      }

      $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
      if (isset($seenPaths[$relativePath])) {
        return;
      }

      $seenPaths[$relativePath] = true;
      $manifest = json_decode((string) file_get_contents($manifestFile), true);
      if (!is_array($manifest)) {
        return;
      }

      $urlPath = trim($relativePath, '/');
      $hubPath = substr($dir, strlen($hubRootReal) + 1);
      $hubPath = $hubPath !== false ? trim(str_replace(DIRECTORY_SEPARATOR, '/', $hubPath), '/') : $baseName;
      $projectUrl = './' . $hubPath . '/';

      $host = [
        'id' => 'project-' . md5($relativePath),
        'type' => 'project',
        'ip' => '127.0.0.1',
        'port' => 80,
        'url' => $projectUrl,
        'asset_url' => $projectUrl,
        'display_host' => $hubPath,
        'server' => 'local',
        'online' => true,
        'last_seen' => date('c'),
      ];
      $projects[] = labhub_apply_manifest_to_host($host, $manifest);
    }

    $entries = @scandir($dir);
    if (!is_array($entries)) {
      return;
    }

    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $child = $dir . DIRECTORY_SEPARATOR . $entry;
      if (is_dir($child)) {
        $scan($child, $depth + 1);
      }
    }
  };

  $scan($hubRootReal, 0);

  return labhub_sort_hosts_list($projects);
}

function labhub_discover_hosts(
  string $subnetPrefix,
  array $ports,
  int $timeoutSeconds,
  bool $requireApacheHeader,
  int $scanStart,
  int $scanEnd,
  string $localHostname
): array {
  $hosts = [];
  $skipIps = ['127.0.0.1'];
  $localIpv4 = labhub_local_ipv4();
  if ($localIpv4 !== null) {
    $skipIps[] = $localIpv4;
  }

  foreach ($ports as $port) {
    $url = 'http://' . $localHostname . ($port === 80 ? '/' : ':' . $port . '/');
    $probe = labhub_curl_probe($url, $timeoutSeconds);
    if (!$probe['ok']) {
      $url = 'http://127.0.0.1' . ($port === 80 ? '/' : ':' . $port . '/');
      $probe = labhub_curl_probe($url, $timeoutSeconds);
    }
    if (!$probe['ok']) {
      continue;
    }
    if ($requireApacheHeader && !labhub_is_apache($probe)) {
      continue;
    }

    $loopbackUrl = 'http://127.0.0.1' . ($port === 80 ? '/' : ':' . $port . '/');

    $hosts[labhub_host_id('127.0.0.1', $port)] = [
      'id' => labhub_host_id('127.0.0.1', $port),
      'ip' => '127.0.0.1',
      'port' => $port,
      'url' => $url,
      'asset_url' => $loopbackUrl,
      'display_host' => $localHostname,
      'server' => $probe['server'],
      'online' => true,
      'last_seen' => date('c'),
    ];
  }

  $scanIps = [];
  for ($hostOctet = $scanStart; $hostOctet <= $scanEnd; $hostOctet++) {
    $ip = $subnetPrefix . '.' . $hostOctet;
    if (!in_array($ip, $skipIps, true)) {
      $scanIps[] = $ip;
    }
  }

  foreach (array_chunk($scanIps, 32) as $ipBatch) {
    $mh = curl_multi_init();
    $handles = [];

    foreach ($ipBatch as $ip) {
      foreach ($ports as $port) {
        $url = 'http://' . $ip . ($port === 80 ? '/' : ':' . $port . '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
          CURLOPT_NOBODY => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HEADER => true,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_MAXREDIRS => 2,
          CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
          CURLOPT_TIMEOUT => $timeoutSeconds,
          CURLOPT_USERAGENT => 'CTI-LabHub-Discover/1.0',
        ]);

        $handles[] = [
          'ip' => $ip,
          'port' => $port,
          'url' => $url,
          'handle' => $ch,
        ];
        curl_multi_add_handle($mh, $ch);
      }
    }

    $running = null;
    do {
      $status = curl_multi_exec($mh, $running);
      if ($running > 0) {
        if (curl_multi_select($mh, 1.0) === -1) {
          usleep(100000);
        }
      }
    } while ($running > 0);

    foreach ($handles as $item) {
      $ch = $item['handle'];
      $response = curl_multi_getcontent($ch);
      $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

      $serverHeader = '';
      if (is_string($response) && $response !== '' && preg_match('/^Server:\s*(.+)$/im', $response, $matches)) {
        $serverHeader = trim($matches[1]);
      }

      curl_multi_remove_handle($mh, $ch);

      if ($httpCode < 200 || $httpCode >= 400) {
        continue;
      }

      $probe = [
        'ok' => true,
        'http_code' => $httpCode,
        'server' => $serverHeader,
      ];

      if ($requireApacheHeader && !labhub_is_apache($probe)) {
        continue;
      }

      $assetUrl = 'http://' . $item['ip'] . ($item['port'] === 80 ? '/' : ':' . $item['port'] . '/');
      $hostId = labhub_host_id($item['ip'], $item['port']);
      $hosts[$hostId] = [
        'id' => $hostId,
        'ip' => $item['ip'],
        'port' => $item['port'],
        'url' => $item['url'],
        'asset_url' => $assetUrl,
        'display_host' => $item['ip'],
        'server' => $probe['server'],
        'online' => true,
        'last_seen' => date('c'),
      ];
    }

    curl_multi_close($mh);
  }

  $hostsList = array_values($hosts);
  usort($hostsList, 'labhub_sort_hosts_by_name_ip');

  if ($localIpv4 !== null) {
    $hostsList = array_values(array_filter(
      $hostsList,
      static function (array $host) use ($localIpv4): bool {
        return ($host['ip'] ?? '') !== $localIpv4;
      }
    ));
  }

  return $hostsList;
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'discover.php') {
  return;
}

if ($subnetPrefix === null || $subnetPrefix === '') {
  $subnetPrefix = labhub_subnet_prefix();
}

if ($subnetPrefix === null || $subnetPrefix === '') {
  $message = 'Não foi possível detectar a sub-rede automaticamente. Verifique a conexão de rede do Mac.';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
  }
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

$writableCheck = labhub_ensure_hosts_file_writable($hostsFile);
if (!$writableCheck['ok']) {
  labhub_exit_hosts_not_writable($writableCheck);
}

$hosts = labhub_discover_hosts(
  $subnetPrefix,
  $ports,
  $timeoutSeconds,
  $requireApacheHeader,
  $scanStart,
  $scanEnd,
  $localHostname
);

$previousHosts = [];
if (is_readable($hostsFile)) {
  $previousPayload = json_decode((string) file_get_contents($hostsFile), true);
  if (is_array($previousPayload['hosts'] ?? null)) {
    $previousHosts = $previousPayload['hosts'];
  }
}

$newHostCount = count($hosts);
$previousHostCount = count($previousHosts);
$scanPreserved = false;
$scanWarning = null;

$labHostCount = 0;
foreach ($hosts as $host) {
  if (($host['ip'] ?? '') !== '127.0.0.1') {
    $labHostCount++;
  }
}

if ($labHostCount === 0 && $previousHostCount > 2) {
  $scanPreserved = true;
  $scanWarning = 'Nenhum PC do laboratório respondeu na varredura. A lista salva de '
    . $previousHostCount . ' estações foi mantida.';
  $hosts = $previousHosts;
  $newHostCount = $previousHostCount;
  echo $scanWarning . PHP_EOL;
}

$hostsForStorage = [];
foreach ($hosts as $host) {
  $hostsForStorage[] = labhub_strip_host_for_storage($host);
}

$payload = [
  'updated_at' => date('c'),
  'subnet' => $subnetPrefix . '.0/24',
  'scan_range' => [$scanStart, $scanEnd],
  'ports' => $ports,
  'host_count' => $newHostCount,
  'hosts' => $hostsForStorage,
  'scan_preserved' => $scanPreserved,
  'scan_warning' => $scanWarning,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
  $message = 'Falha ao gerar hosts.json.';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
  }
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

$tempFile = $hostsFile . '.tmp';
if (file_put_contents($tempFile, $json . PHP_EOL) === false || !rename($tempFile, $hostsFile)) {
  @unlink($tempFile);
  $message = 'Falha ao gravar hosts.json.';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
  }
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

if (!$scanPreserved) {
  $labCount = 0;
  foreach ($hosts as $host) {
    if (($host['ip'] ?? '') !== '127.0.0.1') {
      $labCount++;
    }
  }
  echo 'Descobertos ' . $labCount . ' PC(s) no laboratório + sua estação em ' . $subnetPrefix . '.x' . PHP_EOL;
}
exit(0);
