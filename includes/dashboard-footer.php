</main>
</div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
    const basePath = '<?php echo getBasePath(); ?>';

    // Mobile sidebar toggle
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.add('open');
    });

    document.getElementById('sidebarClose').addEventListener('click', () => {
        document.getElementById('sidebar').classList.remove('open');
    });

    // User dropdown
    const userDropdown = document.getElementById('userDropdown');
    userDropdown.querySelector('.user-trigger').addEventListener('click', () => {
        userDropdown.classList.toggle('open');
    });

    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!userDropdown.contains(e.target)) {
            userDropdown.classList.remove('open');
        }
    });

    // Toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // Sidebar dropdown toggle
    function toggleDropdown(button) {
        const dropdown = button.closest('.sidebar-dropdown');
        dropdown.classList.toggle('open');
    }
</script>
</body>

</html>