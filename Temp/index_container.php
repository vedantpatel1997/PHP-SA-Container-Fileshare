<?php
// Azure Storage account + container
$accountName = "storageaccountvp";
$containerName = "receipe-container";

// Your SAS token (from CLI output)
$sasToken = "se=2025-09-27T22%3A59Z&sp=rl&spr=https&sv=2022-11-02&ss=fb&srt=sco&sig=IyUiX5DXpwXxgyt5MVKfcvAeIld0SICzMENzosnOTZE%3D";

// Base URL
$baseUrl = "https://$accountName.blob.core.windows.net/$containerName";

// URL to list blobs in the container
$listUrl = "$baseUrl?restype=container&comp=list&$sasToken";

// Fetch XML response from Azure
$response = file_get_contents($listUrl);
if ($response === FALSE) {
    die("Error fetching blob list. Check SAS token or permissions.");
}

// Parse XML
$xml = simplexml_load_string($response);

// Start HTML
echo "<!DOCTYPE html><html><head><title>Azure Blob Gallery</title>";
echo "<style>
        body { font-family: Arial, sans-serif; background:#f8f9fa; text-align:center; }
        .gallery { display:flex; flex-wrap:wrap; justify-content:center; }
        .gallery img { margin:10px; border-radius:8px; max-width:200px; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
      </style>";
echo "</head><body>";
echo "<h2>Images from Azure Blob Container</h2>";
echo "<div class='gallery'>";

// Loop through blobs
foreach ($xml->Blobs->Blob as $blob) {
    $blobName = (string)$blob->Name;
    $blobUrl = "$baseUrl/$blobName?$sasToken";
    echo "<img src='$blobUrl' alt='$blobName' />";
}

echo "</div></body></html>";
?>
