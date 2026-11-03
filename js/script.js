// ============================
// BAKE TRACK - Main JavaScript
// ============================

// Toggle Dark Mode
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    document.querySelectorAll('.theme-toggle').forEach(button => {
        button.setAttribute('aria-pressed', String(isDark));
    });
}

// Load Dark Mode Preference
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img[src^="uploads/products/"]').forEach(image => {
        image.addEventListener('error', function() {
            if (this.dataset.fallbackApplied) return;
            this.dataset.fallbackApplied = 'true';
            this.src = 'uploads/products/default.svg';
        });
    });

    if (document.body.dataset.defaultTheme !== 'true' && localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }

    document.querySelectorAll('.theme-toggle').forEach(button => {
        button.setAttribute('aria-pressed', String(document.body.classList.contains('dark-mode')));
        button.addEventListener('click', toggleDarkMode);
    });

    document.querySelectorAll('input[name="darkmode"]').forEach(input => {
        input.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
            localStorage.setItem('darkMode', String(this.checked));
        });
    });
    
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    document.querySelectorAll('.menu-toggle').forEach(toggle => {
        const menu = document.getElementById(toggle.getAttribute('aria-controls'));
        const setOpen = open => {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            if (menu) menu.classList.toggle('is-open', open);
        };

        toggle.addEventListener('click', function() {
            setOpen(this.getAttribute('aria-expanded') !== 'true');
        });
        menu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => setOpen(false));
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });
        document.addEventListener('click', event => {
            if (toggle.getAttribute('aria-expanded') === 'true' && !toggle.contains(event.target) && !menu?.contains(event.target)) {
                setOpen(false);
            }
        });
    });

    document.querySelectorAll('.dashboard-container').forEach(container => {
        const toggle = container.querySelector('.dashboard-menu-toggle');
        const sidebar = container.querySelector('.sidebar');
        if (!toggle || !sidebar) return;

        const setOpen = open => {
            container.classList.toggle('is-nav-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            if (open) sidebar.querySelector('a')?.focus();
            else toggle.focus();
        };

        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => setOpen(false));
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && container.classList.contains('is-nav-open')) {
                setOpen(false);
            }
        });
        document.addEventListener('click', event => {
            if (container.classList.contains('is-nav-open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
                setOpen(false);
            }
        });
        window.matchMedia('(min-width: 769px)').addEventListener('change', () => {
            if (container.classList.contains('is-nav-open')) setOpen(false);
        });
    });

    if (document.body.dataset.liveOrders === 'true') {
        window.setInterval(() => window.location.reload(), 10000);
    }
});

// Utility Functions
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// Product Search
function filterProducts() {
    const input = document.getElementById('searchProduct');
    if (!input) return;
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name') || card.querySelector('h4')?.textContent?.toLowerCase() || '';
        card.style.display = name.includes(filter) ? '' : 'none';
    });
}

// Tab Switching
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';
    const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (btn) btn.classList.add('active');
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let valid = true;
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#E74C3C';
            valid = false;
        } else {
            input.style.borderColor = '#e0e0e0';
        }
    });
    return valid;
}

// AJAX Functions
function fetchData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}

function postData(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => callback(data))
    .catch(error => console.error('Error:', error));
}

// Order Functions
function updateOrderStatus(orderId, status) {
    if (confirm(`Are you sure you want to update order #${orderId} to ${status}?`)) {
        window.location.href = `?action=update_order&order_id=${orderId}&status=${status}`;
    }
}

function viewReceipt(orderId) {
    window.location.href = `?action=view_receipt&order_id=${orderId}`;
}

// Review Functions
function setRating(rating) {
    document.getElementById('reviewRating').value = rating;
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star' + i);
        if (star) {
            star.textContent = i <= rating ? '★' : '☆';
        }
    }
}

// Inventory Chart Helper
function createInventoryChart(ctx, data, labels) {
    if (typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Inventory',
                data: data || [],
                borderColor: '#8B6914',
                backgroundColor: 'rgba(139, 105, 20, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href')?.slice(1);
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Dynamic Year in Footer
document.addEventListener('DOMContentLoaded', function() {
    const yearElements = document.querySelectorAll('.current-year');
    const year = new Date().getFullYear();
    yearElements.forEach(el => el.textContent = year);
});