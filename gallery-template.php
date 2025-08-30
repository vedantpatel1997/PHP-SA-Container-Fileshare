<?php
// gallery-template.php
// Expects: $pageTitle, $folderPathOrSource, $images (array), $error (string), $navPage (string)
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; }
.page-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.3rem; }
.page-header p.lead { color: #6c757d; margin-bottom: 1.5rem; }

.gallery-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; margin-top: 20px; }
.card { display: flex; flex-direction: column; border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
.card-img-top { width: 100%; height: 180px; object-fit: cover; transition: transform 0.3s; }
.card:hover .card-img-top { transform: scale(1.05); }
.card-body { padding: 1rem; display: flex; flex-direction: column; flex-grow: 1; background-color: #fff; }
.card-title { font-size: 1rem; font-weight: 600; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin-bottom: 0.5rem; }
.card-text { font-size: 0.85rem; color: #495057; flex-grow: 1; overflow: hidden; }
.more-properties { display: none; }
.toggle-more { color: #0d6efd; cursor: pointer; font-size: 0.85rem; margin-top: auto; text-align: center; display: block; }
.alert-info { background-color: #e7f1ff; color: #0d6efd; border-radius: 6px; }
.alert-danger { border-radius: 6px; }
.properties-list strong { font-weight: 600; }
</style>
</head>

<body>
<?php renderNavigation($navPage); ?>

<div class="container mt-4">
    <div class="page-header mb-3">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="lead"><?php echo htmlspecialchars($folderPathOrSource); ?></p>
    </div>

    <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($images) && empty($error)): ?>
            <div class="alert alert-info">No images found.</div>
    <?php endif; ?>

    <div class="gallery-container">
        <?php foreach ($images as $img): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($img['url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($img['name']); ?>">
                    <div class="card-body">
                        <h6 class="card-title" title="<?php echo htmlspecialchars($img['name']); ?>">
                            <?php echo htmlspecialchars($img['name']); ?>
                        </h6>
                        <div class="card-text">
                            <?php
                            $props = [];
                            $count = 0;
                            foreach ($img as $k => $v) {
                                if (in_array($k, ['url', 'name']))
                                    continue;
                                $props[$k] = $v;
                            }
                            $firstFive = array_slice($props, 0, 5, true);
                            $rest = array_slice($props, 5, null, true);
                            ?>
                            <div class="first-properties">
                                <?php foreach ($firstFive as $k => $v): ?>
                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $k))); ?>:</strong>
                                        <?php
                                        if (strtolower($k) === 'content-length') {
                                            $sizeKB = round((int) $v / 1024, 2);
                                            echo $sizeKB >= 1024 ? round($sizeKB / 1024, 2) . " MB" : $sizeKB . " KB";
                                        } else {
                                            echo htmlspecialchars($v);
                                        }
                                        ?><br>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($rest)): ?>
                                    <div class="more-properties">
                                        <?php foreach ($rest as $k => $v): ?>
                                                <strong><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $k))); ?>:</strong>
                                                <?php
                                                if (strtolower($k) === 'content-length') {
                                                    $sizeKB = round((int) $v / 1024, 2);
                                                    echo $sizeKB >= 1024 ? round($sizeKB / 1024, 2) . " MB" : $sizeKB . " KB";
                                                } else {
                                                    echo htmlspecialchars($v);
                                                }
                                                ?><br>
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="toggle-more" onclick="toggleAll(event)">... more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
let expanded = false;
function toggleAll(event){
    event.stopPropagation();
    expanded = !expanded;
    document.querySelectorAll('.more-properties').forEach(mp => mp.style.display = expanded ? 'block' : 'none');
    document.querySelectorAll('.toggle-more').forEach(btn => btn.innerText = expanded ? '... less' : '... more');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
