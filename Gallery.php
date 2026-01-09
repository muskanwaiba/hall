<?php
// 1. Define the path to the JSON data file.
$data_file = __DIR__ . '/gallery_data.json';
$gallery_images = [];

// 2. Safely read and decode the JSON file.
if (file_exists($data_file)) {
    $json_data = file_get_contents($data_file);
    // Decode the JSON string into a PHP associative array (true).
    $decoded_data = json_decode($json_data, true);

    // Safety check: ensure decoded data is a non-empty array.
    if (is_array($decoded_data)) {
        $gallery_images = $decoded_data;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Responsive Dynamic Gallery</title>

<style>
/* --- Styles (No changes to your original CSS) --- */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background: #f7f7f7;
    }
    h2 {
        text-align: center;
        padding: 30px 0 10px;
        font-size: 28px;
        color: #333;
    }
    .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        grid-auto-rows: 180px;
        grid-auto-flow: dense;
        gap: 15px;
        width: 90%;
        margin: 0 auto 50px;
    }
    .gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 6px;
        transition: transform 0.3s ease;
    }
    .gallery-item:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 10;
    }
    .gallery-item:hover img {
        transform: scale(1.1);
    }
    /* Grid Span Logic */
    .gallery-item[data-row-span="2"] {
        grid-row: span 3;
    }
    .gallery-item[data-col-span="2"] {
        grid-column: span 2;
    }
    @media (max-width: 768px) {
        .gallery {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            grid-auto-rows: 120px;
        }
    }
    @media (max-width: 480px) {
        .gallery {
            grid-template-columns: 1fr;
            grid-auto-rows: 180px;
        }
    }
/* --- End of Styles --- */
</style>
</head>
<body>

<h2>From Our Gallery (Total: <?= count($gallery_images) ?>)</h2>

<div class="gallery">
    <?php 
    // Check if the array is empty before looping
    if (empty($gallery_images)): ?>
        <p style="grid-column: 1 / -1; text-align: center;">No images available in the gallery.</p>
    <?php 
    else: 
        foreach ($gallery_images as $img): 
            // Default to '1' if span properties are missing in the data
            $col_span = $img['col_span'] ?? '1'; 
            $row_span = $img['row_span'] ?? '1'; 
    ?>
    <div class="gallery-item" 
            data-col-span="<?= htmlspecialchars($col_span) ?>" 
            data-row-span="<?= htmlspecialchars($row_span) ?>" 
            onclick="openModal(this)">
        
        <img src="<?= htmlspecialchars($img['url']) ?>" 
             alt="<?= htmlspecialchars($img['alt']) ?>" 
             loading="lazy">
    </div>
    <?php 
        endforeach; 
    endif;
    ?>
</div>

<div id="modal" onclick="closeModal(event)"
    style="display:none; position:fixed; z-index:1000; left:0; top:0; right:0; bottom:0;
             background-color:rgba(0,0,0,0.8); justify-content:center; align-items:center;">
    <span class="close-btn"
        onclick="closeModal(event)"
        style="position:absolute; top:20px; right:30px; font-size:28px; color:white;
               cursor:pointer; font-weight:bold; user-select:none;">
        &times;
    </span>
    <img id="modal-img" src="" alt="Expanded View"
        style="max-width:90%; max-height:90%; border-radius:8px;">
</div>

<script>
    function openModal(item) {
        const modal = document.getElementById('modal');
        const modalImg = document.getElementById('modal-img');
        const img = item.querySelector('img');

        modal.style.display = 'flex';
        modalImg.src = img.src;
        modalImg.alt = img.alt;

        event.stopPropagation();
    }

    function closeModal(event) {
        if (event.target.id === 'modal' || event.target.classList.contains('close-btn')) {
            document.getElementById('modal').style.display = 'none';
        }
        event.stopPropagation();
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            document.getElementById('modal').style.display = 'none';
        }
    });
</script>

</body>
</html>