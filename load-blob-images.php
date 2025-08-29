<?php
function loadBlobImages($accountName, $containerName, $sasToken, $path='') {
    $images = [];
    $error = '';
    $baseUrl = "https://$accountName.blob.core.windows.net/$containerName";
    $listUrl = "$baseUrl?restype=container&comp=list&$sasToken";

    try {
        $response = @file_get_contents($listUrl);
        if ($response === FALSE) throw new Exception("Error fetching blob list");

        $xml = simplexml_load_string($response);
        if ($xml === FALSE) throw new Exception("Error parsing XML response");

        foreach ($xml->Blobs->Blob as $blob) {
            $blobName = (string)$blob->Name;
            if (!empty($path) && strpos($blobName, $path . '/') !== 0) continue;

            $ext = strtolower(pathinfo($blobName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                $images[] = [
                    'name' => $blobName,
                    'url' => "$baseUrl/$blobName?$sasToken",
                    'size' => (string)$blob->Properties->{'Content-Length'},
                    'lastModified' => (string)$blob->Properties->{'Last-Modified'},
                    'etag' => (string)$blob->Properties->{'Etag'}
                ];
            }
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
    return [$images, $error];
}
