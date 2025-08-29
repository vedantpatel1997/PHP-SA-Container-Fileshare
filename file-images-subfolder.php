<?php
require_once 'envloader.php';
require_once 'navigation.php';
require_once 'load-mounted-images.php';

$folderPath = getenv('FILE_IMAGE_SUBFOLDER') ?: 'Fileshare';
list($images, $error) = loadMountedImages($folderPath);

$pageTitle = "File Share Images Mount";
$folderPathOrSource = "Folder: $folderPath";
$navPage = 'file-images-subfolder.php';

require 'gallery-template.php';
