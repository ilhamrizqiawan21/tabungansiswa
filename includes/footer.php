</main>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // Theme Toggle with local storage
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        // Apply saved theme
        if (currentTheme === 'dark') {
            html.setAttribute('data-bs-theme', 'dark');
            document.body.style.background = '#0f172a';
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            html.setAttribute('data-bs-theme', 'light');
            document.body.style.background = '';
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
        
        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            if (newTheme === 'dark') {
                document.body.style.background = '#0f172a';
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                document.body.style.background = '';
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
    }

    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Format input uang (money format)
        document.querySelectorAll('.money-input').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/[^0-9]/g, '');
                if (value.length > 2) {
                    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }
                this.value = value;
            });
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Active link highlighting (fallback)
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        navLinks.forEach(link => {
            if (link.getAttribute('href') && currentPage.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>