<?php
require_once 'envloader.php';
require_once 'navigation.php';

// Get folder path from env variable (mounted in your Azure App Service)
$folderPath = getenv('BLOB_IMAGE_SUBFOLDER') ?: 'BlobMountedFolder'; // local mounted folder path

// Initialize images array
$images = [];

// Scan folder
if (is_dir($folderPath)) {
    $files = scandir($folderPath);
    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                $images[] = [
                    'name' => $file,
                    'url'  => $filePath, // for local mount, may need relative URL mapping
                    'size' => filesize($filePath),
                    'lastModified' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
    }
} else {
    $error = "Folder not found: $folderPath";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blob Images - Mount</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.gallery-container { display: grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap:20px; margin-top:20px;}
.card { border:none; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s;}
.card:hover { transform: translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.15);}
.card-img-top { border-top-left-radius:12px; border-top-right-radius:12px; height:180px; object-fit:cover;}
.card-body { padding:.75rem 1rem;}
.card-title { font-size:1rem; font-weight:600; margin-bottom:.25rem; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;}
.card-text { font-size:.85rem; color:#555;}
.page-header h1 { font-size:2rem; font-weight:700;}
</style>
</head>
<body>
<?php renderNavigation('blob-images-subfolder.php'); ?>

<div class="container mt-4">
    <div class="page-header mb-3">
        <h1>Blob Container Images Mount</h1>
        <p class="lead">Images from folder: <?php echo htmlspecialchars($folderPath); ?></p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if(empty($images) && empty($error)): ?>
        <div class="alert alert-info">No images found.</div>
    <?php endif; ?>

    <div class="gallery-container">
        <?php foreach($images as $img): ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($img['url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($img['name']); ?>">
                <div class="card-body">
                    <h6 class="card-title" title="<?php echo htmlspecialchars($img['name']); ?>"><?php echo htmlspecialchars($img['name']); ?></h6>
                    <p class="card-text">
                        Size: <?php echo round($img['size']/1024,2); ?> KB<br>
                        Modified: <?php echo $img['lastModified']; ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
