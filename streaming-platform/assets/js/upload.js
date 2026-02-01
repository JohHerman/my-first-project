// Upload functionality for XAMPP
document.addEventListener('DOMContentLoaded', function() {
    // Preview video before upload
    const videoInput = document.getElementById('video');
    if (videoInput) {
        videoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const videoPreview = document.getElementById('video-preview');
                const video = document.createElement('video');
                video.width = 320;
                video.controls = true;
                
                const source = document.createElement('source');
                source.src = URL.createObjectURL(file);
                source.type = file.type;
                
                video.appendChild(source);
                videoPreview.innerHTML = '';
                videoPreview.appendChild(video);
                videoPreview.innerHTML += `<p>${file.name} (${formatFileSize(file.size)})</p>`;
            }
        });
    }
    
    // Form validation
    const uploadForm = document.querySelector('.upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const video = document.getElementById('video').files[0];
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a video title');
                return false;
            }
            
            if (!video) {
                e.preventDefault();
                alert('Please select a video file');
                return false;
            }
            
            // Check file extension
            const allowed = ['mp4', 'webm', 'avi', 'mov', 'm4v'];
            const ext = video.name.split('.').pop().toLowerCase();
            
            if (!allowed.includes(ext)) {
                e.preventDefault();
                alert('Please select a valid video file (MP4, WebM, AVI, MOV)');
                return false;
            }
            
            return true;
        });
    }
});

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
}