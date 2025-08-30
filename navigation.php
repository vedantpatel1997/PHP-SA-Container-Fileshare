<?php
function renderNavigation($currentPage)
{
    $pages = [
        'Home' => 'index.php',
        'Blob Container SAS' => 'blob-images.php',
        'Blob Container Mount' => 'blob-images-subfolder.php',
        'File Share SAS' => 'file-images.php',
        'File Share Mount' => 'file-images-subfolder.php',
        'Health Check' => 'healthcheck.php'
    ];

    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">';
    echo '<div class="container">';
    echo '<a class="navbar-brand" href="index.php">Azure Storage Explorer</a>';
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
    echo '<span class="navbar-toggler-icon"></span>';
    echo '</button>';
    echo '<div class="collapse navbar-collapse" id="navbarNav">';
    echo '<ul class="navbar-nav ms-auto">';

    foreach ($pages as $name => $url) {
        $active = ($currentPage == $url) ? 'active' : '';
        echo "<li class='nav-item'><a class='nav-link $active' href='$url'>$name</a></li>";
    }

    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</nav>';
}
?>