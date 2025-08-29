<?php
require_once 'envloader.php';
require_once 'navigation.php';
require_once 'load-mounted-images.php';

$folderPath = getenv('BLOB_IMAGE_SUBFOLDER') ?: 'BlobMountedFolder';
list($images, $error) = loadMountedImages($folderPath);

$pageTitle = "Blob Container Images Mount";
$folderPathOrSource = "Folder: $folderPath";
$navPage = 'blob-images-subfolder.php';

require 'gallery-template.php';
