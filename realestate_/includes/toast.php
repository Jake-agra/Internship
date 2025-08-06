<?php
// Toast notification system for the real estate application

function renderToastContainer() {
    echo '<div id="toast-container"></div>';
}

function renderToastScript() {
    ?>
    <script>
        // Enhanced Toast notification function
        function showToast(message, type = 'error') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                console.warn('Toast container not found');
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            // Add icon based on type
            const icon = getToastIcon(type);
            
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-icon">${icon}</div>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" onclick="closeToast(this)">&times;</button>
                </div>
                <div class="toast-progress"></div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Start progress bar animation
            const progressBar = toast.querySelector('.toast-progress');
            progressBar.style.animation = 'progressBar 5s linear forwards';
            
            // Auto remove after 5 seconds
            const timeoutId = setTimeout(() => {
                closeToast(toast.querySelector('.toast-close'));
            }, 5000);
            
            // Store timeout ID for manual close
            toast.dataset.timeoutId = timeoutId;
        }
        
        function getToastIcon(type) {
            const icons = {
                'success': '✓',
                'error': '✕',
                'warning': '⚠',
                'info': 'ℹ'
            };
            return icons[type] || icons['info'];
        }
        
        function closeToast(closeButton) {
            const toast = closeButton.closest('.toast');
            if (toast) {
                // Clear timeout if manually closed
                if (toast.dataset.timeoutId) {
                    clearTimeout(parseInt(toast.dataset.timeoutId));
                }
                
                // Add fade out animation
                toast.classList.add('fade-out');
                
                // Remove after animation
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }
        }
        
        // Show toast for server-side messages
        function showServerToast(message, type) {
            if(message && message.trim() !== '') {
                showToast(message, type);
            }
        }
    </script>
    <?php
}

function showToastMessage($message, $type = 'error') {
    if (!empty($message)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showServerToast('" . addslashes($message) . "', '$type');
            });
        </script>";
    }
}

// Function to display PHP alert messages (fallback for non-JS users)
function renderAlert($message, $type = 'danger') {
    if (!empty($message)) {
        $alertClass = $type === 'error' ? 'alert-danger' : 'alert-' . $type;
        echo "<div class='alert $alertClass'>" . htmlspecialchars($message) . "</div>";
    }
}
?>
