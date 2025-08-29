<?php
$accountName = "storageaccountvp";
$shareName   = "receipe-fileshare";

// Use your SAS token
$sasToken = "se=2025-09-27T22%3A59Z&sp=rl&spr=https&sv=2022-11-02&ss=fb&srt=sco&sig=IyUiX5DXpwXxgyt5MVKfcvAeIld0SICzMENzosnOTZE%3D";

// Base URL for file share
$baseUrl = "https://$accountName.file.core.windows.net/$shareName";

// List directory contents
$listUrl = "$baseUrl?restype=directory&comp=list&$sasToken";

$response = file_get_contents($listUrl);
if ($response === FALSE) {
    die("Error fetching file list.");
}

$xml = simplexml_load_string($response);

// Display images
foreach ($xml->Entries->File as $file) {
    $fileName = (string)$file->Name;
    $fileUrl = "$baseUrl/$fileName?$sasToken";
    echo "<img src='$fileUrl' width='200' style='margin:5px' />";
}
?>
