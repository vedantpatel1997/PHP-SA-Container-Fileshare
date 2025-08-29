<?php
require_once 'envloader.php';
require_once 'navigation.php';

// Initialize variables
$blobStatus = 'unknown';
$fileStatus = 'unknown';
$blobError = '';
$fileError = '';
$showPhpInfo = isset($_GET['phpinfo']);

// Check blob storage connectivity
$accountName = getenv('AZURE_STORAGE_ACCOUNT');
$containerName = getenv('AZURE_BLOB_CONTAINER');
$sasToken = getenv('AZURE_SAS_TOKEN');

if (!empty($accountName) && !empty($containerName) && !empty($sasToken)) {
    $blobUrl = "https://$accountName.blob.core.windows.net/$containerName?restype=container&comp=list&$sasToken";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($blobUrl, false, $context);
    if ($response !== FALSE) {
        $blobStatus = 'ok';
    } else {
        $blobStatus = 'error';
        $blobError = "Unable to connect to blob container. Check credentials and network connectivity.";
    }
} else {
    $blobStatus = 'error';
    $blobError = "Missing configuration for blob storage.";
}

// Check file share connectivity
$shareName = getenv('AZURE_FILE_SHARE');

if (!empty($accountName) && !empty($shareName) && !empty($sasToken)) {
    $fileUrl = "https://$accountName.file.core.windows.net/$shareName?restype=directory&comp=list&$sasToken";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($fileUrl, false, $context);
    if ($response !== FALSE) {
        $fileStatus = 'ok';
    } else {
        $fileStatus = 'error';
        $fileError = "Unable to connect to file share. Check credentials and network connectivity.";
    }
} else {
    $fileStatus = 'error';
    $fileError = "Missing configuration for file share.";
}

// Display PHPInfo if requested
if ($showPhpInfo) {
    phpinfo();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Check - Azure Storage Explorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php renderNavigation('healthcheck.php'); ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <h1>System Health Check</h1>
                    <p class="lead">Monitor connectivity to your Azure Storage resources</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Blob Storage Status</h5>
                            </div>
                            <div class="card-body">
                                <div
                                    class="health-status <?php echo $blobStatus == 'ok' ? 'health-ok' : 'health-error'; ?>">
                                    <strong>Status:</strong>
                                    <?php echo $blobStatus == 'ok' ? 'OK' : 'ERROR'; ?>
                                </div>

                                <?php if ($blobStatus == 'error'): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $blobError; ?>
                                    </div>
                                <?php else: ?>
                                    <p>Successfully connected to blob container.</p>
                                <?php endif; ?>

                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>Account:</strong> <?php echo $accountName; ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Container:</strong> <?php echo $containerName; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">File Share Status</h5>
                            </div>
                            <div class="card-body">
                                <div
                                    class="health-status <?php echo $fileStatus == 'ok' ? 'health-ok' : 'health-error'; ?>">
                                    <strong>Status:</strong>
                                    <?php echo $fileStatus == 'ok' ? 'OK' : 'ERROR'; ?>
                                </div>

                                <?php if ($fileStatus == 'error'): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $fileError; ?>
                                    </div>
                                <?php else: ?>
                                    <p>Successfully connected to file share.</p>
                                <?php endif; ?>

                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>Account:</strong> <?php echo $accountName; ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Share:</strong> <?php echo $shareName; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="healthcheck.php?phpinfo=1" class="btn btn-info">View PHPInfo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>