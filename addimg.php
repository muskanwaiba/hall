<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Responsive Media Gallery</title>
<style>
  :root {
    --primary-color: #3498db;
    --danger-color: #e74c3c;
    --bg-color: #f7f7f7;
    --card-bg: #fff;
    --shadow-color: rgba(0, 0, 0, 0.08);
    --overlay-color: rgba(0, 0, 0, 0.21);
    --border-radius: 10px;
    --transition-fast: 0.18s;
    --transition-normal: 0.36s;
  }
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: var(--bg-color);
    color: #333;
  }
  h2 {
    text-align: center;
    padding: 32px 0 10px;
    font-size: 28px;
  }
  #upload-section {
    width: 92%;
    margin: 0 auto 32px;
    text-align: center;
  }
  #drop-zone {
    border: 2px dashed #ccc;
    border-radius: var(--border-radius);
    background: #fafcff;
    padding: 38px 14px 36px 14px;
    color: #5e5e5e;
    cursor: pointer;
    box-shadow: 0 2px 8px var(--shadow-color);
    transition: border-color var(--transition-fast), background var(--transition-fast);
    position: relative;
    margin-bottom: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  #drop-zone.dragover {
    border-color: var(--primary-color);
    background: #f2faff;
  }
  #drop-zone .plus-icon {
    font-size: 40px;
    color: var(--primary-color);
    font-weight: bold;
    margin-bottom: 12px;
    display: block;
  }
  #drop-zone .drop-text {
    font-size: 19px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #383838;
  }
  #drop-zone .drop-subtext {
    font-size: 15px;
    color: #999;
    margin-bottom: 8px;
    font-weight: 400;
  }
  #drop-zone .browse-link {
    color: var(--primary-color);
    text-decoration: underline;
    cursor: pointer;
  }
  #fileInput {
    display: none;
  }
  .gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    grid-auto-rows: 180px;
    gap: 15px;
    width: 92%;
    margin: 0 auto 55px;
  }
  .gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: var(--border-radius);
    background: var(--card-bg);
    box-shadow: 0 2px 10px var(--shadow-color);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform var(--transition-normal), box-shadow var(--transition-normal);
    cursor: pointer;
  }
  .gallery-item:hover {
    transform: scale(1.038);
    box-shadow: 0 8px 24px var(--shadow-color);
  }
  .gallery-item img,
  .gallery-item video {
    max-width: 100%;
    max-height: 100%;
    border-radius: var(--border-radius);
    object-fit: cover;
    pointer-events: none;
  }
  .gallery-item video {
    background: #000;
    min-width: 75px;
    min-height: 75px;
  }
  .delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: var(--danger-color);
    border: none;
    color: white;
    padding: 7px 11px;
    cursor: pointer;
    border-radius: 50%;
    font-size: 15px;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.20);
    transition: background var(--transition-fast);
    user-select: none;
    z-index: 10;
    opacity: 0.94;
  }
  .delete-btn:hover,
  .delete-btn:focus {
    background: #b91e1e;
    outline: none;
  }
  #modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0; right: 0; bottom: 0;
    background-color: rgba(0,0,0,0.86);
    justify-content: center;
    align-items: center;
    transition: opacity var(--transition-normal);
    opacity: 0;
    pointer-events: none;
  }
  #modal.show {
    display: flex;
    opacity: 1;
    pointer-events: all;
  }
  #modal-content {
    max-width: 92vw;
    max-height: 92vh;
    border-radius: var(--border-radius);
    background: #232628;
    box-shadow: 0 8px 24px rgba(0,0,0,0.65);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  #modal-content img,
  #modal-content video {
    max-width: 92vw;
    max-height: 80vh;
    display: block;
    border-radius: var(--border-radius);
    background: #000;
    margin-bottom: 0;
  }
  #modal-content video {
    outline: 1px solid #333;
    background: #0a0a0a;
  }
  .close-btn {
    position: absolute;
    top: -48px;
    right: 2px;
    font-size: 32px;
    color: #fff;
    cursor: pointer;
    font-weight: bold;
    user-select: none;
    transition: color var(--transition-fast);
    z-index: 11;
  }
  .close-btn:hover {
    color: var(--danger-color);
  }
  @media (max-width: 768px) {
    .gallery {
      grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
      grid-auto-rows: 90px;
    }
    #modal-content img, #modal-content video {
      max-width: 98vw;
      max-height: 68vh;
    }
  }
  @media (max-width: 480px) {
    .gallery {
      grid-template-columns: 1fr;
      grid-auto-rows: 180px;
    }
  }
</style>
</head>
<body>
<h2>From Our Media Gallery</h2>
<div id="upload-section" aria-label="Media upload section">
  <div id="drop-zone" tabindex="0">
    <span class="plus-icon">＋</span>
    <div class="drop-text">Add item image or video</div>
    <div class="drop-subtext">
      Drop an image or video, or <span class="browse-link" onclick="document.getElementById('fileInput').click()">browse</span> from your computer
    </div>
    <input id="fileInput" type="file" accept="image/*,video/*" multiple>
  </div>
</div>
<div class="gallery" id="gallery"></div>
<div id="modal" onclick="closeModal(event)">
  <div id="modal-content">
    <span class="close-btn" onclick="closeModal(event)" aria-label="Close modal">&times;</span>
  </div>
</div>
<script>
  // Example initial media, use real data (images/videos) for production
  const initialMedia = [
    {
      kind: "img",
      src: "https://images.unsplash.com/photo-1591203281954-23fa2ff8ef18?ixlib=rb-4.1.0&auto=format&fit=crop&q=80&w=700",
      alt: "Decorated wedding hall"
    },
    {
      kind: "img",
      src: "https://images.unsplash.com/photo-1578298880489-ff269cea9894?ixlib=rb-4.1.0&auto=format&fit=crop&q=60&w=600",
      alt: "Luxurious banquet setup"
    },
    {
      kind: "video",
      src: "https://www.w3schools.com/html/mov_bbb.mp4",
      alt: "Sample video"
    }
  ];
  const dropZone = document.getElementById("drop-zone");
  const fileInput = document.getElementById("fileInput");
  const gallery = document.getElementById("gallery");
  const modal = document.getElementById("modal");
  const modalContent = document.getElementById("modal-content");

  function renderInitialMedia() {
    initialMedia.forEach(item =>
      createMediaItem(item.kind, item.src, item.alt, item.kind === "video" ? "video/mp4" : "")
    );
  }
  renderInitialMedia();

  dropZone.addEventListener("click", () => fileInput.click());
  dropZone.addEventListener("dragover", function(e) {
    e.preventDefault(); e.stopPropagation();
    this.classList.add("dragover");
  });
  dropZone.addEventListener("dragleave", function(e) {
    e.preventDefault(); e.stopPropagation();
    this.classList.remove("dragover");
  });
  dropZone.addEventListener("drop", function(e) {
    e.preventDefault(); e.stopPropagation();
    this.classList.remove("dragover");
    handleFiles(e.dataTransfer.files);
  });
  fileInput.addEventListener("change", () => handleFiles(fileInput.files));

  function handleFiles(files) {
    [...files].forEach(file => {
      const type = file.type;
      if(type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function(e) {
          createMediaItem("img", e.target.result, file.name);
        };
        reader.readAsDataURL(file);
      } else if(type.startsWith("video/")) {
        const reader = new FileReader();
        reader.onload = function(e) {
          createMediaItem("video", e.target.result, file.name, type);
        };
        reader.readAsDataURL(file);
      }
    });
    fileInput.value = "";
  }
  function createMediaItem(kind, src, alt, videoType="") {
    const div = document.createElement("div");
    div.className = "gallery-item";
    let media;
    if(kind === "img") {
      media = document.createElement("img");
      media.src = src;
      media.alt = alt;
      media.tabIndex = 0;
      media.setAttribute('aria-label', alt);
    } else {
      media = document.createElement("video");
      media.src = src;
      media.controls = false;
      media.muted = true;
      media.loop = true;
      media.playsInline = true;
      media.tabIndex = 0;
      media.setAttribute('aria-label', alt);
      media.addEventListener('mouseenter', () => media.play());
      media.addEventListener('mouseleave', () => media.pause());
      media.poster = "";
    }
    div.onclick = function(e) {
      if(e.target.classList.contains('delete-btn')) return;
      openModal(kind, src, alt, videoType);
    };
    const btn = document.createElement("button");
    btn.className = "delete-btn";
    btn.setAttribute("aria-label","Remove media");
    btn.innerHTML = "&times;";
    btn.onclick = function(event) {
      event.stopPropagation();
      if(confirm("Delete this media?")) div.remove();
    };
    div.appendChild(media);
    div.appendChild(btn);
    gallery.appendChild(div);
  }
  function openModal(kind, src, alt="", videoType="") {
    modal.classList.add("show");
    modalContent.innerHTML = `<span class="close-btn" onclick="closeModal(event)" aria-label="Close modal">&times;</span>`;
    if(kind === "img") {
      const img = document.createElement('img');
      img.src = src;
      img.alt = alt;
      modalContent.appendChild(img);
    } else {
      const video = document.createElement('video');
      video.src = src;
      video.controls = true;
      video.autoplay = true;
      video.loop = true;
      video.style.background="#000";
      if(videoType) video.type = videoType;
      modalContent.appendChild(video);
    }
  }
  function closeModal(event) {
    if(event.target.id === 'modal' || event.target.classList.contains('close-btn')) {
      modal.classList.remove('show');
      setTimeout(() => modalContent.innerHTML = `<span class="close-btn" onclick="closeModal(event)" aria-label="Close modal">&times;</span>`, 300);
    }
    event.stopPropagation();
  }
  document.addEventListener('keydown', function(event) {
    if(event.key === "Escape") closeModal({target:{id:'modal'}, stopPropagation:()=>{}});
  });
</script>
</body>
</html>
