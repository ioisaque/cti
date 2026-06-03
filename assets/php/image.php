<?php

declare(strict_types=1);

$ip = $_GET['ip'] ?? '';
$src = $_GET['src'] ?? '';
$port = isset($_GET['port']) ? (int) $_GET['port'] : 80;

if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
  http_response_code(400);
  exit;
}

$src = basename($src);
if ($src === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $src)) {
  http_response_code(400);
  exit;
}

if ($port < 1 || $port > 65535) {
  $port = 80;
}

$url = 'http://' . $ip . ($port === 80 ? '/' : ':' . $port . '/') . $src;

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_CONNECTTIMEOUT => 3,
  CURLOPT_TIMEOUT => 15,
  CURLOPT_HEADER => true,
  CURLOPT_USERAGENT => 'CTI-LabHub/1.0',
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

if ($httpCode !== 200 || !is_string($response) || $headerSize >= strlen($response)) {
  http_response_code(404);
  exit;
}

$body = substr($response, $headerSize);
if ($body === '') {
  http_response_code(404);
  exit;
}

header('Content-Type: ' . (is_string($contentType) && $contentType !== '' ? $contentType : 'application/octet-stream'));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo $body;
