<?php
require_once 'envloader.php';
require_once 'navigation.php';

// Environment vars with defaults
$accountName   = getenv('AZURE_STORAGE_ACCOUNT') ;
$containerName = getenv('AZURE_BLOB_CONTAINER') ;
$sasToken      = getenv('AZURE_SAS_TOKEN');
$path          = getenv('BLOB_IMAGE_PATH') ?: '';  // optional "subfolder"

// Base container URL (NO $path here!)
$baseUrl = "https://$accountName.blob.core.windows.net/$containerName";

// URL to list blobs
$listUrl = "$baseUrl?restype=container&comp=list&$sasToken";

// Initialize
$images = [];
$error = '';

// Fetch data from Azure
try {
    $response = @file_get_contents($listUrl);
    if ($response === FALSE) {
        throw new Exception("Error fetching blob list. Check SAS token or permissions.");
    }

    // Parse XML
    $xml = simplexml_load_string($response);
    if ($xml === FALSE) {
        throw new Exception("Error parsing response from Azure.");
    }

    // Collect images
    foreach ($xml->Blobs->Blob as $blob) {
        $blobName = (string) $blob->Name;

        // If user specified a subfolder path, filter blobs
        if (!empty($path) && strpos($blobName, $path . '/') !== 0) {
            continue;
        }

        // Check if it's an image
        $ext = strtolower(pathinfo($blobName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            $blobUrl = "$baseUrl/$blobName?$sasToken"; // correct URL
            $properties = [
                'name' => $blobName,
                'url' => $blobUrl,
                'size' => (string) $blob->Properties->{'Content-Length'},
                'lastModified' => (string) $blob->Properties->{'Last-Modified'},
                'etag' => (string) $blob->Properties->{'Etag'}
            ];
            $images[] = $properties;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Azure Blob Images</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f6f8;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}

.page-header p.lead {
    color: #555;
    margin-bottom: 1.5rem;
}

.gallery-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.card {
    border: none;
    border-radius: 12px;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
}

.card-img-top {
    height: 180px;
    object-fit: cover;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.card-body {
    padding: 0.75rem 1rem;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-text {
    font-size: 0.85rem;
    color: #555;
}

.alert-info {
    background-color: #e7f1ff;
    color: #0d6efd;
    border-radius: 6px;
}

.alert-danger {
    border-radius: 6px;
}

</style>
</head>
<body>
<?php renderNavigation('blob-images.php'); ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Blob Container Images SAS</h1>
                <p class="lead">Images from <?php echo $containerName; ?><?php echo !empty($path) ? " in {$path}" : ''; ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($images) && empty($error)): ?>
                <div class="alert alert-info">No images found in the specified container.</div>
            <?php endif; ?>

            <div class="gallery-container">
                <?php foreach ($images as $image): ?>
                    <div class="card">
                        <img src="<?php echo $image['url']; ?>" class="card-img-top" alt="<?php echo $image['name']; ?>">
                        <div class="card-body">
                            <h6 class="card-title" title="<?php echo $image['name']; ?>"><?php echo $image['name']; ?></h6>
                            <p class="card-text">
                                Size: <?php echo round($image['size'] / 1024, 2); ?> KB<br>
                                Modified: <?php echo date('Y-m-d H:i:s', strtotime($image['lastModified'])); ?><br>
                                ETag: <?php echo $image['etag']; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
