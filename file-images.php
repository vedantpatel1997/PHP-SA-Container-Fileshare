<?php
require_once 'envloader.php';
require_once 'navigation.php';
require_once 'load-blob-images.php'; // same loader works if adapted, or you can make a separate file share loader

$accountName = getenv('AZURE_STORAGE_ACCOUNT');
$shareName = getenv('AZURE_FILE_SHARE');
$sasToken = getenv('AZURE_SAS_TOKEN');
$path = getenv('FILE_IMAGE_PATH') ?: '';

// Azure File Share URL
$baseUrl = "https://$accountName.file.core.windows.net/$shareName";
$listUrl = !empty($path) ? "$baseUrl/$path?restype=directory&comp=list&$sasToken" 
                          : "$baseUrl?restype=directory&comp=list&$sasToken";

$images = [];
$error = '';

try {
    $response = @file_get_contents($listUrl);
    if ($response === FALSE) throw new Exception("Error fetching file list. Check SAS token");

    $xml = simplexml_load_string($response);
    if ($xml === FALSE) throw new Exception("Error parsing XML response");

    if (!empty($xml->Entries->File)) {
        foreach ($xml->Entries->File as $file) {
            $fileName = (string)$file->Name;
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                $fileUrl = "$baseUrl/$fileName?$sasToken";
                $images[] = [
                    'name' => $fileName,
                    'url' => $fileUrl,
                    'size' => (string)$file->Properties->{'Content-Length'},
                    'lastModified' => (string)$file->Properties->{'Last-Modified'},
                    'etag' => (string)$file->Properties->{'Etag'}
                ];
            }
        }
    }
} catch(Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "File Share Images SAS";
$folderPathOrSource = "Share: $shareName" . (!empty($path) ? " in $path" : '');
$navPage = 'file-images.php';

require 'gallery-template.php';
