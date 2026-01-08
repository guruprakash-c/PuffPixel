$(document).ready(function () {
    $('#thumbnailForm').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData();
        
        // Required main image
        const mainImage = $('#mainImage')[0].files[0];
        if (!mainImage) {
            showError('Please upload a main image.');
            return;
        }
        formData.append('mainImage', mainImage);

        // Optional watermark
        const watermark = $('#watermark')[0].files[0];
        if (watermark) {
            formData.append('watermark', watermark);
        }

        // Form fields
        formData.append('platform', $('#platform').val());
        formData.append('aspect', $('#aspect').val());
        formData.append('upscale', $('#upscale').is(':checked'));
        formData.append('title', $('#title').val().trim());
        formData.append('link', $('#link').val().trim());

        $('#preview').html('<p>Generating thumbnail...</p>');
        $('#error').empty();

        $.ajax({
            url: 'api', // Adjust path if needed
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.error) {
                    showError(response.error);
                } else {
                    const imgSrc = response.thumbnail;
                    const previewHtml = `
                        <h2>Your Thumbnail (Ready!)</h2>
                        <img src="${imgSrc}" alt="Generated Thumbnail">
                        <br>
                        <button type="button" class="download-btn" onclick="downloadImage('${imgSrc}')">
                            Download Thumbnail
                        </button>
                    `;
                    $('#preview').html(previewHtml);
                }
            },
            error: function (xhr) {
                let msg = 'Failed to generate thumbnail.';
                try {
                    const res = JSON.parse(xhr.responseText);
                    msg = res.error || msg;
                } catch (e) {}
                showError(msg);
            }
        });
    });

    function showError(msg) {
        $('#error').text(msg);
        $('#preview').empty();
    }
});

// Global function for download
function downloadImage(dataUrl) {
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = 'thumbnail_' + Date.now() + '.avif';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}