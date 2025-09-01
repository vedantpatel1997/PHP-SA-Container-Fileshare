<?php
require_once 'envloader.php';
require_once 'navigation.php';

$accountName = getenv('AZURE_STORAGE_ACCOUNT');
$shareName = getenv('AZURE_FILE_SHARE');
$sasToken = getenv('AZURE_SAS_TOKEN');
$path = trim(getenv('FILE_IMAGE_PATH') ?: '', "/");

// ---- Helpers ----
function encodePathSegments(string $path): string
{
    if ($path === '')
        return '';
    $parts = array_filter(explode('/', $path), 'strlen');
    $enc = array_map('rawurlencode', $parts);
    return implode('/', $enc);
}

function curl_head_with_ms(string $url, array $extraHeaders = [], int $timeout = 20): array
{
    $ch = curl_init($url);
    $headers = array_merge([
        // Use a modern storage API version so x-ms-file-* headers are returned
        'x-ms-version: 2023-08-03',
        'Accept: */*'
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,   // HEAD
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $assoc = [];
    if ($raw !== false) {
        // Parse headers into an associative array
        $lines = preg_split("/\r\n|\n|\r/", $raw);
        foreach ($lines as $line) {
            if (stripos($line, 'HTTP/') === 0) {
                $assoc['__StatusLine'] = $line;
                continue;
            }
            if (strpos($line, ':') !== false) {
                list($k, $v) = explode(':', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if ($k === '')
                    continue;
                if (!isset($assoc[$k]))
                    $assoc[$k] = $v;
                else
                    $assoc[$k] .= ', ' . $v;
            }
        }
    }

    curl_close($ch);
    return [
        'status' => $status,
        'headers' => $assoc,
        'error' => $err,
        'raw' => $raw,
    ];
}

function console_log($data)
{
    echo "<script>console.log(" . json_encode($data) . ");</script>";
}

// ---- Build URLs ----
$encodedPath = encodePathSegments($path);
$baseUrl = "https://$accountName.file.core.windows.net/$shareName";
$listUrl = ($encodedPath !== '')
    ? "$baseUrl/$encodedPath?restype=directory&comp=list&$sasToken"
    : "$baseUrl?restype=directory&comp=list&$sasToken";

$images = [];
$error = '';
$debug = ['listUrl' => $listUrl, 'items' => []];

try {
    $response = @file_get_contents($listUrl);
    if ($response === FALSE) {
        throw new Exception("Error fetching file list. Check SAS token/path.");
    }

    $xml = simplexml_load_string($response);
    if ($xml === FALSE) {
        throw new Exception("Error parsing XML response");
    }

    if (!empty($xml->Entries->File)) {
        foreach ($xml->Entries->File as $file) {
            $fileName = (string) $file->Name;
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                continue; // only images
            }

            // Build full path and encoded URL-safe path
            $fullPath = $path !== '' ? "$path/$fileName" : $fileName;
            $encodedFullPath = ($encodedPath !== '')
                ? encodePathSegments($path) . '/' . rawurlencode($fileName)
                : rawurlencode($fileName);

            $imgUrl = "$baseUrl/$encodedFullPath?$sasToken"; // for <img src>
            $headUrl = "$baseUrl/$encodedFullPath?$sasToken"; // HEAD request

            // Start with size from list call if present
            $props = [];
            if (isset($file->Properties->{'Content-Length'})) {
                $props['Content-Length'] = (string) $file->Properties->{'Content-Length'};
            }

            // HEAD request for properties
            $head = curl_head_with_ms($headUrl);
            if ($head && $head['status'] === 200) {
                foreach ($head['headers'] as $hKey => $hVal) {
                    if ($hKey === '__StatusLine')
                        continue;
                    $props[$hKey] = $hVal;
                }
            } else {
                $props['__prop_status'] = $head['status'];
                if (!empty($head['error']))
                    $props['__prop_error'] = $head['error'];
            }

            // Normalize Content-Length
            if (!isset($props['Content-Length']) && isset($props['x-ms-content-length']) && ctype_digit((string) $props['x-ms-content-length'])) {
                $props['Content-Length'] = $props['x-ms-content-length'];
            }

            $images[] = array_merge([
                'name' => $fileName,
                'url' => $imgUrl,
            ], $props);

            // Collect debug info
            $debug['items'][] = [
                'file' => $fileName,
                'imgUrl' => $imgUrl,
                'headUrl' => $headUrl,
                'headStatus' => $head['status'],
                'headHdrs' => $head['headers'],
                'headErr' => $head['error'],
            ];
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "File Share Images SAS";
$folderPathOrSource = "Share: $shareName" . ($path !== '' ? " in $path" : '');
$navPage = 'file-images.php';

require 'gallery-template.php';

// dump structured logs to the browser console
console_log($debug);
