<?php
function loadMountedImages($folderPath) {
    $images = [];
    $error = '';

    if (is_dir($folderPath)) {
        foreach (scandir($folderPath) as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                    $images[] = [
                        'name' => $file,
                        'url' => $filePath,
                        'size' => filesize($filePath),
                        'lastModified' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
        }
    } else {
        $error = "Folder not found: $folderPath";
    }
    return [$images, $error];
}
