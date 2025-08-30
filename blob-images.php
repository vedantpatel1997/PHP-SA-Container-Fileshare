<?php
require_once 'envloader.php';
require_once 'navigation.php';

$accountName = getenv('AZURE_STORAGE_ACCOUNT');
$containerName = getenv('AZURE_BLOB_CONTAINER');
$sasToken = getenv('AZURE_SAS_TOKEN');
$path = getenv('BLOB_IMAGE_PATH') ?: '';

// Azure Blob base URL
$baseUrl = "https://$accountName.blob.core.windows.net/$containerName";

// Blob list URL (include path if provided)
$listUrl = !empty($path)
    ? "$baseUrl/$path?restype=container&comp=list&include=metadata,snapshots,uncommittedblobs,copy&$sasToken"
    : "$baseUrl?restype=container&comp=list&include=metadata,snapshots,uncommittedblobs,copy&$sasToken";

$images = [];
$error = '';

try {
    // Debug log
    error_log("Fetching blob list from: " . $listUrl);

    $response = @file_get_contents($listUrl);
    if ($response === FALSE) {
        throw new Exception("Error fetching blob list. Check SAS token or container/path");
    }

    $xml = simplexml_load_string($response);
    if ($xml === FALSE) {
        throw new Exception("Error parsing XML response");
    }

    // Debug log raw XML
    error_log("Blob list raw XML:\n" . $response);

    if (!empty($xml->Blobs->Blob)) {
        foreach ($xml->Blobs->Blob as $blob) {
            $blobName = (string) $blob->Name;
            $ext = strtolower(pathinfo($blobName, PATHINFO_EXTENSION));

            // Only process images
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                $blobUrl = "$baseUrl/$blobName?$sasToken";

                // Collect properties
                $props = [];
                if (isset($blob->Properties)) {
                    foreach ($blob->Properties->children() as $propName => $propValue) {
                        $props[$propName] = (string) $propValue;
                    }
                }

                // Metadata (user-defined metadata in blob storage)
                if (isset($blob->Metadata)) {
                    foreach ($blob->Metadata->children() as $metaName => $metaValue) {
                        $props["metadata_$metaName"] = (string) $metaValue;
                    }
                }

                // Add basic info + all props
                $images[] = array_merge([
                    'name' => $blobName,
                    'url' => $blobUrl,
                ], $props);

                // Debug log each blob
                error_log("Blob: " . print_r(end($images), true));
            }
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in blob-images.php: " . $error);
}

$pageTitle = "Blob Container Images SAS";
$folderPathOrSource = "Container: $containerName" . (!empty($path) ? " in $path" : '');
$navPage = 'blob-images.php';

require 'gallery-template.php';
