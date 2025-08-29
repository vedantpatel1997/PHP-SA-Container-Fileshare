<?php
require_once 'envloader.php';
require_once 'navigation.php';
require_once 'load-blob-images.php';

$accountName = getenv('AZURE_STORAGE_ACCOUNT');
$containerName = getenv('AZURE_BLOB_CONTAINER');
$sasToken = getenv('AZURE_SAS_TOKEN');
$path = getenv('BLOB_IMAGE_PATH') ?: '';

list($images, $error) = loadBlobImages($accountName, $containerName, $sasToken, $path);

$pageTitle = "Blob Container Images SAS";
$folderPathOrSource = "Container: $containerName" . (!empty($path) ? " in $path" : '');
$navPage = 'blob-images.php';

require 'gallery-template.php';
