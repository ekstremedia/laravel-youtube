document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('upload-form');
    if (!uploadForm) return;

    const submitBtn = uploadForm.querySelector('button[type="submit"]');
    const fileInput = uploadForm.querySelector('input[name="video"]');
    const progressContainer = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const statusMessage = document.getElementById('status-message');

    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validate file size client-side
        const file = fileInput.files[0];
        if (!file) {
            showError('Please select a video file');
            return;
        }

        const maxSize = parseInt(uploadForm.dataset.maxSize) * 1024 * 1024; // Convert MB to bytes
        if (file.size > maxSize) {
            showError(`File size exceeds maximum allowed size of ${uploadForm.dataset.maxSize}MB`);
            return;
        }

        // Prepare form data
        const formData = new FormData(uploadForm);

        // Disable form during upload
        setFormDisabled(true);
        showProgress();

        try {
            // Upload with XMLHttpRequest to track progress
            const response = await uploadWithProgress(formData);

            if (response.success) {
                if (response.job_id) {
                    // Start polling for job progress
                    pollJobProgress(response.job_id);
                } else if (response.video_id) {
                    // Direct upload success
                    showSuccess('Video uploaded successfully!');
                    setTimeout(() => {
                        window.location.href = response.redirect || '/youtube-admin/videos';
                    }, 2000);
                }
            } else {
                showError(response.message || 'Upload failed');
                setFormDisabled(false);
            }
        } catch (error) {
            showError(error.message || 'An error occurred during upload');
            setFormDisabled(false);
        }
    });

    function uploadWithProgress(formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Track upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    updateProgress(percentComplete, 'Uploading to server...');
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid server response'));
                    }
                } else {
                    try {
                        const error = JSON.parse(xhr.responseText);
                        reject(new Error(error.message || `Server error: ${xhr.status}`));
                    } catch (e) {
                        reject(new Error(`Server error: ${xhr.status}`));
                    }
                }
            });

            xhr.addEventListener('error', function() {
                reject(new Error('Network error occurred'));
            });

            xhr.addEventListener('timeout', function() {
                reject(new Error('Upload timed out'));
            });

            // Set timeout to 10 minutes for large files
            xhr.timeout = 600000;

            // Add headers for AJAX request
            xhr.open('POST', uploadForm.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            // CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken.content);
            }

            xhr.send(formData);
        });
    }

    function pollJobProgress(jobId) {
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/youtube-admin/upload/progress/${jobId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to get progress');
                }

                const data = await response.json();

                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    updateProgress(100, 'Upload complete!');
                    showSuccess('Video uploaded to YouTube successfully!');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/youtube-admin/videos';
                    }, 2000);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    showError(data.message || 'Upload failed');
                    setFormDisabled(false);
                } else if (data.status === 'processing') {
                    updateProgress(data.progress || 50, data.message || 'Processing upload...');
                } else if (data.status === 'uploading') {
                    updateProgress(data.progress || 75, data.message || 'Uploading to YouTube...');
                }
            } catch (error) {
                console.error('Progress polling error:', error);
                // Continue polling even if one request fails
            }
        }, 2000); // Poll every 2 seconds

        // Stop polling after 30 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
            showError('Upload timeout - please check your videos page');
            setFormDisabled(false);
        }, 1800000);
    }

    function showProgress() {
        progressContainer.classList.remove('hidden');
        statusMessage.className = 'hidden';
        updateProgress(0, 'Preparing upload...');
    }

    function updateProgress(percent, text) {
        progressBar.style.width = percent + '%';
        progressBar.textContent = percent + '%';
        if (progressText) {
            progressText.textContent = text;
        }
    }

    function showSuccess(message) {
        progressContainer.classList.add('hidden');
        statusMessage.className = 'mt-4 p-4 rounded-lg bg-green-900/50 border border-green-700/50 text-green-300';
        statusMessage.textContent = message;
    }

    function showError(message) {
        progressContainer.classList.add('hidden');
        statusMessage.className = 'mt-4 p-4 rounded-lg bg-red-900/50 border border-red-700/50 text-red-300';
        statusMessage.textContent = message;
    }

    function setFormDisabled(disabled) {
        submitBtn.disabled = disabled;
        fileInput.disabled = disabled;
        uploadForm.querySelectorAll('input, textarea, select').forEach(input => {
            input.disabled = disabled;
        });

        if (disabled) {
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
});