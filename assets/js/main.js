// Confirm before delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format currency
function formatMoney(amount) {
    return 'UGX ' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Search member with AJAX
function searchMember(query, callback) {
    if (query.length < 3) return;
    
    fetch(`/sacco_system/api/ajax_handler.php?action=search_member&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(error => console.error('Error:', error));
}

// Auto-refresh notifications (every 30 seconds)
setInterval(function() {
    fetch('/sacco_system/api/ajax_handler.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                // Update notification badge
                document.querySelector('.notification-badge').textContent = data.count;
            }
        });
}, 30000);

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add validation to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
