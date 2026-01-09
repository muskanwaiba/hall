document.addEventListener('DOMContentLoaded', function() {
    const galleryContainer = document.getElementById('gallery-placeholder');
    const seeMoreLink = galleryContainer.querySelector('.see-more-placeholder');

    if (!galleryContainer || !seeMoreLink) return;

    let galleryData = [];
    let currentIndex = 0;
    const INITIAL_LOAD = 6; // Show 6 images initially

    // Show loading spinner outside See More link
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-spinner';
    loadingDiv.style.cssText = 'text-align:center; padding:10px; color:white;';
    loadingDiv.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Loading gallery...`;
    galleryContainer.insertBefore(loadingDiv, seeMoreLink);

    // Fetch gallery JSON
    fetch('gallery_data.json')
        .then(res => {
            if (!res.ok) throw new Error('Failed to fetch gallery');
            return res.json();
        })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                showNoImagesMessage();
                return;
            }
            galleryData = data;
            loadingDiv.remove(); // Remove spinner
            insertImages(INITIAL_LOAD); // Load initial 6 images
        })
        .catch(err => {
            console.error('Gallery loading failed:', err);
            showErrorState();
        });

    // Insert images function
    function insertImages(count) {
        const nextImages = galleryData.slice(currentIndex, currentIndex + count);

        nextImages.forEach(image => {
            const link = document.createElement('a');
            link.href = 'Gallery.php';
            link.className = 'gallery-img-link';
            link.setAttribute('aria-label', `View ${image.alt || 'event hall'}`);

            const img = document.createElement('img');
            img.src = image.url;
            img.alt = image.alt || 'Event hall interior';
            img.className = 'gallery-img';
            img.loading = 'lazy';
            img.style.opacity = '0';
            img.onload = () => img.style.opacity = '1';

            link.appendChild(img);
            galleryContainer.insertBefore(link, seeMoreLink);
        });

        currentIndex += count;

        // Hide See More if all images loaded
        if (currentIndex >= galleryData.length) {
            seeMoreLink.style.display = 'none';
        }
    }

    // Handle See More click
    seeMoreLink.addEventListener('click', function(e) {
        e.preventDefault();
        // Load all remaining images
        const remaining = galleryData.length - currentIndex;
        if (remaining > 0) insertImages(remaining);
    });

    // No images message
    function showNoImagesMessage() {
        loadingDiv.innerHTML = `
            <div class="no-images-message" style="color:white; text-align:center; padding:10px;">
                <i class="fas fa-images"></i>
                <span>No photos available yet</span><br>
                <small>Visit gallery page</small>
            </div>`;
    }

    // Error state
    function showErrorState() {
        loadingDiv.innerHTML = `
            <div class="error-message" style="color:white; text-align:center; padding:10px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Failed to load gallery</span><br>
                <a href="Gallery.php" style="color:white; text-decoration:underline;">Visit Gallery →</a>
            </div>`;
    }
});
