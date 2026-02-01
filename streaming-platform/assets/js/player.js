// Video player controls
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('main-video');
    if (!video) return;

    const playPauseBtn = document.querySelector('.play-pause');
    const progressBar = document.querySelector('.progress');
    const timeDisplay = document.querySelector('.time-display');
    const volumeSlider = document.querySelector('.volume-control input');

    if (playPauseBtn) {
        playPauseBtn.addEventListener('click', function() {
            if (video.paused) {
                video.play();
                this.innerHTML = '❚❚';
            } else {
                video.pause();
                this.innerHTML = '▶';
            }
        });
    }

    video.addEventListener('timeupdate', function() {
        const progress = (video.currentTime / video.duration) * 100;
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        
        if (timeDisplay) {
            const minutes = Math.floor(video.currentTime / 60);
            const seconds = Math.floor(video.currentTime % 60);
            const totalMinutes = Math.floor(video.duration / 60);
            const totalSeconds = Math.floor(video.duration % 60);
            timeDisplay.textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')} / ${totalMinutes}:${totalSeconds.toString().padStart(2, '0')}`;
        }
    });

    if (volumeSlider) {
        volumeSlider.addEventListener('input', function() {
            video.volume = this.value;
        });
    }

    // Click on progress bar to seek
    document.querySelector('.progress-bar').addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const pos = (e.clientX - rect.left) / rect.width;
        video.currentTime = pos * video.duration;
    });

    // Fullscreen
    document.querySelector('.fullscreen-btn').addEventListener('click', function() {
        if (!document.fullscreenElement) {
            video.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });
});