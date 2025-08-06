<?php
function render_toast() {
    echo '<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;"></div>';
}

function renderToastScript() {
    ?>
    <script>
        function showToast(message, type = 'error') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                console.error('Toast container not found');
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.style.cssText = "background: #333; color: #fff; padding: 10px 20px; border-radius: 5px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;";

            // Add icon to the toast
            if (type === 'success') {
                toast.innerHTML = `<i class="fas fa-check-circle" style="color: limegreen;"></i> ${message}`;
            } else {
                toast.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: orange;"></i> ${message}`;
            }

            toastContainer.appendChild(toast);

            // Remove the toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
    <?php
}
?>
<?