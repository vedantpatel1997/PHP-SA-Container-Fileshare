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
$showPHPInfo = isset($_GET['phpinfo']);

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
        ? ['status' => 'ok', 'message' => "Blob healthcheck image accessible."]
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
        ? ['status' => 'ok', 'message' => "File healthcheck image accessible."]
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

// Capture phpinfo output if requested
if ($showPHPInfo) {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();
    
    // Extract just the body content from phpinfo output
    if (preg_match('/<body>(.*?)<\/body>/s', $phpinfo, $matches)) {
        $phpinfo = $matches[1];
    }
    
    // Add some basic styling to phpinfo table
    $phpinfo = str_replace(
        ['<table', '<td', '<th', '<h1', '<h2'],
        ['<table class="table table-bordered table-sm"', '<td class="p-2"', '<th class="p-2 bg-light"', '<h2', '<h3 class="mt-4"'],
        $phpinfo
    );
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .status-ok {
            color: #198754;
            font-weight: 500;
        }

        .status-fail {
            color: #dc3545;
            font-weight: 500;
        }

        .status-warn {
            color: #fd7e14;
            font-weight: 500;
        }

        .alert-general {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .phpinfo-container {
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .btn-outline-primary {
            margin-top: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            margin-right: 8px;
        }
        
        .badge-ok {
            background-color: #d1e7dd;
        }
        
        .badge-fail {
            background-color: #f8d7da;
        }
        
        .badge-warn {
            background-color: #fff3cd;
        }
        
        /* Style phpinfo table to fit better with our design */
        #phpinfo table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        
        #phpinfo h1 {
            display: none;
        }
        
        #phpinfo h2 {
            color: #0d6efd;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        #phpinfo .center {
            text-align: left;
        }
        
        #phpinfo .e {
            font-weight: bold;
            width: 30%;
        }
    </style>
</head>

<body>
    <?php renderNavigation('healthcheck.php'); ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Azure Storage Health Check</h1>
            <?php if (!$showPHPInfo): ?>
                <a href="?phpinfo=1" class="btn btn-outline-primary">View PHP Info</a>
            <?php else: ?>
                <a href="?" class="btn btn-outline-secondary">Back to Health Check</a>
            <?php endif; ?>
        </div>
        
        <?php if (!$showPHPInfo): ?>
            <p class="text-muted">Validates SAS token, container/share list, and actual file access using healthcheck images (server-side checks).</p>

            <?php if ($hasWarning): ?>
            <div class="alert-general">
                <strong>Note:</strong> Some checks reported warnings or failures. Make sure your server is connected to the VPN or has network access to the storage accounts.
            </div>
            <?php endif; ?>

            <?php foreach ($results as $type => $checks): ?>
                <div class="card">
                    <h4 class="mb-3"><?php echo strtoupper($type); ?> Storage</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($checks as $c): ?>
                            <li class="mb-2 d-flex align-items-center">
                                <span class="status-badge badge-<?php echo $c['status']; ?>">
                                    <?php echo strtoupper($c['status']); ?>
                                </span>
                                <span class="status-<?php echo $c['status']; ?>">
                                    <?php echo $c['message']; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($showPHPInfo): ?>
            <div class="phpinfo-container">
                <h2 class="mb-3">PHP Information</h2>
                <div id="phpinfo">
                    <?php echo $phpinfo; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>