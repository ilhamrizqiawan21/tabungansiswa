    </main>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar & UI Scripts -->
<script>
    // ============================================
    // SIDEBAR TOGGLE FUNCTIONALITY
    // ============================================
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        
        // Update toggle button icon
        const toggleBtn = document.querySelector('.sidebar-toggle i');
        if (document.body.classList.contains('sidebar-collapsed')) {
            toggleBtn.classList.remove('fa-chevron-left');
            toggleBtn.classList.add('fa-chevron-right');
        } else {
            toggleBtn.classList.remove('fa-chevron-right');
            toggleBtn.classList.add('fa-chevron-left');
        }
    }

    // Load sidebar state from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            document.body.classList.add('sidebar-collapsed');
            const toggleBtn = document.querySelector('.sidebar-toggle i');
            toggleBtn.classList.remove('fa-chevron-left');
            toggleBtn.classList.add('fa-chevron-right');
        }
    });

    // ============================================
    // USER DROPDOWN MENU
    // ============================================
    function toggleUserMenu(event) {
        event.preventDefault();
        const dropdown = event.target.closest('.user-dropdown');
        dropdown.classList.toggle('open');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown && !dropdown.contains(event.target)) {
            dropdown.classList.remove('open');
        }
    });

    // ============================================
    // CURRENT TIME DISPLAY
    // ============================================
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = `${hours}:${minutes}`;
        }
    }

    updateTime();
    setInterval(updateTime, 60000);

    // ============================================
    // AUTO-CLOSE ALERTS
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 5000);
    });

    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================
    document.addEventListener('keydown', function(event) {
        // Alt + S untuk collapse sidebar
        if (event.altKey && event.key === 's') {
            event.preventDefault();
            toggleSidebar();
        }
    });

    // ============================================
    // SMOOTH SCROLL
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // ============================================
    // ACTIVE LINK HIGHLIGHTING
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const currentLocation = location.pathname;
        const menuLinks = document.querySelectorAll('.sidebar-menu-link');

        menuLinks.forEach(link => {
            if (link.getAttribute('href') === currentLocation) {
                link.classList.add('active');
            }
        });
    });
</script>

</body>
</html>
