<?php
require_once 'envloader.php';
require_once 'navigation.php';

// Environment vars with defaults
$accountName = getenv('AZURE_STORAGE_ACCOUNT') ?: 'storageaccountvp';
$shareName   = getenv('AZURE_FILE_SHARE') ?: 'receipe-fileshare';
$sasToken    = getenv('AZURE_SAS_TOKEN') ?: 'YOUR_SAS_TOKEN';
$path        = getenv('FILE_IMAGE_PATH') ?: '';

// Base URL for file share (don’t append $path for listing)
$baseUrl = "https://$accountName.file.core.windows.net/$shareName";
$listUrl = !empty($path) ? "$baseUrl/$path?restype=directory&comp=list&$sasToken" 
                          : "$baseUrl?restype=directory&comp=list&$sasToken";

$images = [];
$error = '';

try {
    $response = @file_get_contents($listUrl);
    if ($response === FALSE) throw new Exception("Error fetching file list. Check SAS token or permissions.");

    $xml = simplexml_load_string($response);
    if ($xml === FALSE) throw new Exception("Error parsing XML response from Azure File Share.");

    if (!empty($xml->Entries->File)) {
        foreach ($xml->Entries->File as $file) {
            $fileName = (string)$file->Name;
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                $fileUrl = "$baseUrl/$fileName?$sasToken";
                $images[] = [
                    'name' => $fileName,
                    'url' => $fileUrl,
                    'size' => (string)$file->Properties->{'Content-Length'},
                    'lastModified' => (string)$file->Properties->{'Last-Modified'},
                    'etag' => (string)$file->Properties->{'Etag'}
                ];
            }
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
    <title>Azure File Share Images</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Segoe UI', sans-serif; }
        .gallery-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .card-img-top { border-top-left-radius: 12px; border-top-right-radius: 12px; height: 180px; object-fit: cover; }
        .card-body { padding: 0.75rem 1rem; }
        .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .card-text { font-size: 0.85rem; color: #555; }
        .page-header h1 { font-size: 2rem; font-weight: 700; }
        .breadcrumb-custom { font-size: 0.9rem; color: #777; margin-bottom: 20px; }
        .breadcrumb-custom span { margin: 0 5px; }
    </style>
</head>
<body>
<?php renderNavigation('file-images.php'); ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>File Share Images SAS</h1>
                <div class="breadcrumb-custom">
                    <span>Storage Account: <strong><?php echo $accountName; ?></strong></span> &gt;
                    <span>Share: <strong><?php echo $shareName; ?></strong></span>
                    <?php if(!empty($path)) echo "&gt; <span>Folder: <strong>$path</strong></span>"; ?>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($images) && empty($error)): ?>
                <div class="alert alert-info">No images found in the specified file share.</div>
            <?php endif; ?>

            <div class="gallery-container">
                <?php foreach ($images as $image): ?>
                    <div class="card">
                        <img src="<?php echo $image['url']; ?>" class="card-img-top" alt="<?php echo $image['name']; ?>">
                        <div class="card-body">
                            <h6 class="card-title" title="<?php echo $image['name']; ?>"><?php echo $image['name']; ?></h6>
                            <p class="card-text">
                                Size: <?php echo round($image['size']/1024, 2); ?> KB<br>
                                Modified: <?php echo date('Y-m-d H:i:s', strtotime($image['lastModified'])); ?>
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
