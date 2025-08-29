<?php
require_once 'envloader.php';
require_once 'navigation.php';

$account = getenv('AZURE_STORAGE_ACCOUNT');
$container = getenv('AZURE_BLOB_CONTAINER');
$share = getenv('AZURE_FILE_SHARE');
$sas = getenv('AZURE_SAS_TOKEN');

$testBlob = 'healthcheck.jpg';
$testFile = 'healthcheck.jpg';

$results = ['blob' => [], 'file' => []];
$hasWarning = false;

// Validate SAS token format
function validateSAS($sas)
{
    return !empty($sas) && strpos($sas, 'sig=') !== false;
}

// Encode path parts for safe URLs
function encodePath($path)
{
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

// Fetch file contents (server-side)
function fetchFileContents($url)
{
    return @file_get_contents($url);
}

// ------------------- BLOB CHECK -------------------
$results['blob'][] = validateSAS($sas)
    ? ['status' => 'ok', 'message' => 'Blob SAS token is valid.']
    : ['status' => 'fail', 'message' => 'Blob SAS token is missing or invalid.'];

if (validateSAS($sas)) {
    $listUrl = "https://$account.blob.core.windows.net/$container?restype=container&comp=list&$sas";
    $response = @file_get_contents($listUrl);
    $xmlOk = $response !== false && @simplexml_load_string($response) !== false;
    $results['blob'][] = $xmlOk
        ? ['status' => 'ok', 'message' => 'Blob container list accessible.']
        : ['status' => 'warn', 'message' => 'Cannot list blobs. Listing may be restricted or private endpoint in use.'];

    $blobUrl = "https://$account.blob.core.windows.net/$container/" . encodePath($testBlob) . "?$sas";
    $imgContent = fetchFileContents($blobUrl);
    $results['blob'][] = ($imgContent !== false)
        ? ['status' => 'ok', 'message' => "Blob healthcheck image accessible.", "content" => $imgContent]
        : ['status' => 'fail', 'message' => "Blob healthcheck image cannot be accessed. Make sure server can reach storage account (VPN may be required)."];
}

// ------------------- FILE SHARE CHECK -------------------
$results['file'][] = validateSAS($sas)
    ? ['status' => 'ok', 'message' => 'File share SAS token is valid.']
    : ['status' => 'fail', 'message' => 'File share SAS token is missing or invalid.'];

if (validateSAS($sas)) {
    $listUrl = "https://$account.file.core.windows.net/$share?restype=directory&comp=list&$sas";
    $response = @file_get_contents($listUrl);
    $xmlOk = $response !== false && @simplexml_load_string($response) !== false;
    $results['file'][] = $xmlOk
        ? ['status' => 'ok', 'message' => 'File share list accessible.']
        : ['status' => 'warn', 'message' => 'Cannot list files. Listing may be restricted or private endpoint in use.'];

    $fileUrl = "https://$account.file.core.windows.net/$share/" . encodePath($testFile) . "?$sas";
    $fileContent = fetchFileContents($fileUrl);
    $results['file'][] = ($fileContent !== false)
        ? ['status' => 'ok', 'message' => "File healthcheck image accessible.", "content" => $fileContent]
        : ['status' => 'fail', 'message' => "File healthcheck image cannot be accessed. Make sure server can reach storage account (VPN may be required)."];
}

// Check if any warn/fail exists
foreach ($results as $checks) {
    foreach ($checks as $c) {
        if (in_array($c['status'], ['warn', 'fail'])) {
            $hasWarning = true;
            break 2;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Azure Storage Health Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .status-ok {
            color: green;
            font-weight: bold
        }

        .status-fail {
            color: red;
            font-weight: bold
        }

        .status-warn {
            color: orange;
            font-weight: bold
        }

        img.healthcheck-img {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
        }

        .alert-general {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>

<body>
    <?php renderNavigation('healthcheck.php'); ?>
    <div class="container mt-4">
        <h1>Azure Storage Health Check</h1>
        <p>Validates SAS token, container/share list, and actual file access using healthcheck images (server-side
            checks).</p>

        <div class="alert-general">
            <strong>Note:</strong> If any images fail to load or checks report warnings, make sure your server is
            connected to the VPN or has network access to the storage accounts. Without VPN or proper network access,
            results may appear inaccurate.
        </div>

        <?php foreach ($results as $type => $checks): ?>
            <div class="card">
                <h4><?php echo strtoupper($type); ?></h4>
                <ul>
                    <?php foreach ($checks as $c): ?>
                        <li class="status-<?php echo $c['status']; ?>">
                            [<?php echo strtoupper($c['status']); ?>] <?php echo $c['message']; ?>
                            <?php if (isset($c['content']) && $c['status'] == 'ok'): ?>
                                <br><img src="data:image/jpeg;base64,<?php echo base64_encode($c['content']); ?>"
                                    class="healthcheck-img" alt="healthcheck image">
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>