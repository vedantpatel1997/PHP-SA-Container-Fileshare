<?php
require_once 'envloader.php';
require_once 'navigation.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azure Storage Explorer - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php renderNavigation('index.php'); ?>

    <div class="container mt-4">
        <div class="page-header mb-4">
            <h1>Azure Storage Explorer</h1>
            <p class="lead">Monitor and browse your Azure Storage resources</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Blob Container SAS</h5>
                        <p class="card-text flex-grow-1">Images from default/root folder of your Blob container using
                            SAS token.</p>
                        <a href="blob-images.php" class="btn btn-primary mt-auto">View Images</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Blob Container Mount</h5>
                        <p class="card-text flex-grow-1">Images from a specific folder mounted from your Blob container.
                        </p>
                        <a href="blob-images-subfolder.php" class="btn btn-primary mt-auto">View Images</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">File Share SAS</h5>
                        <p class="card-text flex-grow-1">Images from default/root folder of your Azure File Share using
                            SAS token.</p>
                        <a href="file-images.php" class="btn btn-primary mt-auto">View Images</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">File Share Mount</h5>
                        <p class="card-text flex-grow-1">Images from a specific folder mounted from your Azure File
                            Share.</p>
                        <a href="file-images-subfolder.php" class="btn btn-primary mt-auto">View Images</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Current Configuration</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Storage Account:</strong>
                                <?php echo getenv('AZURE_STORAGE_ACCOUNT') ?: 'Not set'; ?></li>
                            <li class="list-group-item"><strong>Blob Container:</strong>
                                <?php echo getenv('AZURE_BLOB_CONTAINER') ?: 'Not set'; ?></li>
                            <li class="list-group-item"><strong>File Share:</strong>
                                <?php echo getenv('AZURE_FILE_SHARE') ?: 'Not set'; ?></li>
                            <li class="list-group-item"><strong>Blob Container SAS Path:</strong>
                                <?php echo getenv('BLOB_IMAGE_PATH') ?: 'Root'; ?></li>
                            <li class="list-group-item"><strong>Blob Container Mount Folder:</strong>
                                <?php echo getenv('BLOB_IMAGE_SUBFOLDER') ?: 'Not set'; ?></li>
                            <li class="list-group-item"><strong>File Share SAS Path:</strong>
                                <?php echo getenv('FILE_IMAGE_PATH') ?: 'Root'; ?></li>
                            <li class="list-group-item"><strong>File Share Mount Folder:</strong>
                                <?php echo getenv('FILE_IMAGE_SUBFOLDER') ?: 'Not set'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>