// Utility Functions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

function formatMoney(amount) {
    return 'UGX ' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function debounce(fn, delay = 250) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
    };
}

function buildApiUrl(path) {
    const base = window.APP_BASE_URL || '';
    return base + path;
}

// Live Search Class
class LiveSearch {
    constructor(inputId, resultsId, searchUrl, minChars = 2, delay = 300) {
        this.input = document.getElementById(inputId);
        this.results = document.getElementById(resultsId);
        this.searchUrl = searchUrl;
        this.minChars = minChars;
        this.delay = delay;
        this.searchTimeout = null;
        this.currentRequest = null;
        this.selectedIndex = -1;

        if (this.input) {
            this.init();
        }
    }

    init() {
        // Add search input styling and attributes
        this.input.setAttribute('autocomplete', 'off');
        this.input.setAttribute('role', 'combobox');
        this.input.setAttribute('aria-expanded', 'false');

        // Event listeners
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('focus', () => this.handleFocus());
        this.input.addEventListener('blur', () => this.handleBlur());
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Create results container if it doesn't exist
        if (!this.results) {
            this.createResultsContainer();
        }

        // Add CSS if not already added
        this.addStyles();
    }

    createResultsContainer() {
        this.results = document.createElement('div');
        this.results.id = this.input.id + '_results';
        this.results.className = 'live-search-results';
        this.results.setAttribute('role', 'listbox');
        this.results.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-height: 320px;
            overflow-y: auto;
            z-index: 1050;
            display: none;
        `;

        // Make input container relative
        const container = this.input.parentNode;
        if (getComputedStyle(container).position === 'static') {
            container.style.position = 'relative';
        }
        container.appendChild(this.results);
    }

    handleInput(e) {
        const query = e.target.value.trim();
        this.selectedIndex = -1;

        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Cancel previous request
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        if (query.length < this.minChars) {
            this.hideResults();
            return;
        }

        // Show loading state
        this.showLoading();

        // Start new search with delay
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.delay);
    }

    handleFocus() {
        const query = this.input.value.trim();
        if (query.length >= this.minChars && this.results.children.length > 0) {
            this.showResults();
        }
    }

    handleBlur() {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            this.hideResults();
        }, 200);
    }

    handleKeydown(e) {
        const items = this.results.querySelectorAll('.search-result-item:not(.loading):not(.no-results)');

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection(items);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection(items);
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                    items[this.selectedIndex].click();
                }
                break;
            case 'Escape':
                this.hideResults();
                this.input.blur();
                break;
        }
    }

    updateSelection(items) {
        // Remove all active states
        items.forEach(item => item.classList.remove('active'));

        // Add active to selected item
        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
            items[this.selectedIndex].classList.add('active');
            items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    async performSearch(query) {
        try {
            const controller = new AbortController();
            this.currentRequest = controller;

            const url = `${this.searchUrl}${this.searchUrl.includes('?') ? '&' : '?'}q=${encodeURIComponent(query)}`;
            const response = await fetch(url, {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            this.displayResults(data, query);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                this.showError('Search failed. Please try again.');
            }
        } finally {
            this.currentRequest = null;
        }
    }

    showLoading() {
        this.results.innerHTML = `
            <div class="search-result-item loading d-flex align-items-center p-3">
                <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="text-muted">Searching...</span>
            </div>
        `;
        this.showResults();
    }

    showError(message) {
        this.results.innerHTML = `
            <div class="search-result-item error p-3 text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
            </div>
        `;
        this.showResults();
    }

    displayResults(data, query) {
        this.selectedIndex = -1;

        if (!data.success || !data.members || data.members.length === 0) {
            this.results.innerHTML = `
                <div class="search-result-item no-results p-3 text-center text-muted">
                    <i class="bi bi-search me-2"></i>
                    <span>No members found for "<strong>${this.escapeHtml(query)}</strong>"</span>
                </div>
            `;
            this.showResults();
            return;
        }

        const html = data.members.map((member, index) => `
            <div class="search-result-item" 
                 data-member-id="${member.member_id}" 
                 data-index="${index}"
                 role="option">
                <div class="d-flex align-items-center p-3">
                    <div class="member-avatar me-3">
                        <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark">
                            ${this.highlightMatch(member.full_name, query)}
                        </div>
                        <div class="text-muted small">
                            <span class="me-3">
                                <i class="bi bi-hash me-1"></i>
                                ${this.highlightMatch(member.membership_no, query)}
                            </span>
                            <span class="me-3">
                                <i class="bi bi-telephone me-1"></i>
                                ${this.highlightMatch(member.phone, query)}
                            </span>
                            ${member.email ? `
                                <span>
                                    <i class="bi bi-envelope me-1"></i>
                                    ${this.highlightMatch(member.email, query)}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-arrow-right-circle"></i>
                    </div>
                </div>
            </div>
        `).join('');

        this.results.innerHTML = html;

        // Add click and hover handlers
        this.results.querySelectorAll('.search-result-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                const memberId = item.dataset.memberId;
                const memberData = data.members.find(m => m.member_id == memberId);
                this.selectMember(memberData);
            });

            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelection(this.results.querySelectorAll('.search-result-item'));
            });
        });

        this.showResults();
    }

    highlightMatch(text, query) {
        if (!query || !text) return this.escapeHtml(text);

        const escapedText = this.escapeHtml(text);
        const escapedQuery = this.escapeHtml(query);
        const regex = new RegExp(`(${this.escapeRegex(escapedQuery)})`, 'gi');

        return escapedText.replace(regex, '<mark class="bg-warning">$1</mark>');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    selectMember(member) {
        // Override this method in subclasses
        console.log('Selected member:', member);
        this.hideResults();
    }

    showResults() {
        this.results.style.display = 'block';
        this.input.setAttribute('aria-expanded', 'true');
    }

    hideResults() {
        this.results.style.display = 'none';
        this.input.setAttribute('aria-expanded', 'false');
        this.selectedIndex = -1;
    }

    addStyles() {
        if (document.getElementById('live-search-styles')) return;

        const style = document.createElement('style');
        style.id = 'live-search-styles';
        style.textContent = `
            .live-search-results {
                font-size: 0.9rem;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 0 0 0.375rem 0.375rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .search-result-item {
                cursor: pointer;
                transition: all 0.15s ease;
                border-bottom: 1px solid #f8f9fa;
            }

            .search-result-item:last-child {
                border-bottom: none;
            }

            .search-result-item:hover,
            .search-result-item.active {
                background-color: #f8f9fa;
                transform: translateX(2px);
            }

            .search-result-item.loading,
            .search-result-item.error,
            .search-result-item.no-results {
                cursor: default;
                transform: none !important;
            }

            .search-result-item mark {
                background-color: #fff3cd;
                padding: 0.1em 0.2em;
                border-radius: 0.2em;
                font-weight: 600;
            }

            .member-avatar {
                width: 45px;
                text-align: center;
            }

            .avatar-circle {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                font-size: 0.8rem;
            }

            .live-search-results::-webkit-scrollbar {
                width: 6px;
            }

            .live-search-results::-webkit-scrollbar-track {
                background: #f1f1f1;
            }

            .live-search-results::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }

            .live-search-results::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }

            /* Loading animation */
            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
            }

            /* Responsive adjustments */
            @media (max-width: 576px) {
                .live-search-results {
                    font-size: 0.85rem;
                }

                .member-avatar {
                    width: 40px;
                }

                .avatar-circle {
                    width: 30px;
                    height: 30px;
                    font-size: 0.7rem;
                }

                .search-result-item .small {
                    font-size: 0.75rem !important;
                }
            }
        `;

        document.head.appendChild(style);
    }
}

// Specialized Member Search for Deposit/Forms
class MemberFormSearch extends LiveSearch {
    constructor(inputId = 'membership_no', apiUrl = '../api/ajax_handler.php?action=search_member') {
        super(inputId, null, apiUrl, 2, 200);
        this.selectedMember = null;
    }

    selectMember(member) {
        this.selectedMember = member;
        this.input.value = member.membership_no;
        this.hideResults();

        // Trigger custom event
        this.input.dispatchEvent(new CustomEvent('memberSelected', {
            detail: member,
            bubbles: true
        }));

        // Show member info if containers exist
        this.displayMemberInfo(member);

        // Load member accounts if on deposit page
        if (document.getElementById('account_id')) {
            // If search returned a matched_account (e.g., searched by account number), remember it
            window.__preselectedAccountNumber = member.matched_account && member.matched_account.account_number ? member.matched_account.account_number : null;
            // Populate holder name field if present
            const holderInput = document.getElementById('holder_name');
            if (holderInput) holderInput.value = member.full_name || '';

            this.loadMemberAccounts(member.member_id);
        }
    }

    displayMemberInfo(member) {
        const memberInfoCard = document.getElementById('memberInfoCard');
        const memberInfo = document.getElementById('memberInfo');

        if (memberInfo) {
            memberInfo.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <strong class="text-muted">Full Name:</strong><br>
                            <span class="fw-bold">${member.full_name}</span>
                        </div>
                        <div>
                            <strong class="text-muted">Phone:</strong><br>
                            <span>${member.phone}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <strong class="text-muted">Email:</strong><br>
                            <span>${member.email || 'Not provided'}</span>
                        </div>
                        <div>
                            <strong class="text-muted">Status:</strong><br>
                            <span class="badge bg-success">Active Member</span>
                        </div>
                    </div>
                </div>
            `;
        }

        if (memberInfoCard) {
            memberInfoCard.style.display = 'block';
            memberInfoCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    async loadMemberAccounts(memberId) {
        const feedback = document.getElementById('account_feedback');
        const accountSelect = document.getElementById('account_id');

        if (!accountSelect) return;

        if (feedback) {
            feedback.textContent = 'Loading accounts...';
            feedback.className = 'form-text text-info';
        }

        try {
            const response = await fetch(`../api/ajax_handler.php?action=get_member_accounts&member_id=${memberId}`);
            const data = await response.json();

            // If the API returned member info, populate holder and membership fields for assurance
            if (data.member) {
                const holderInput = document.getElementById('holder_name');
                if (holderInput) holderInput.value = data.member.full_name || '';
                const membershipInput = document.getElementById('membership_no');
                if (membershipInput && data.member.membership_no) membershipInput.value = data.member.membership_no;
            }

            if (data.success && data.accounts && data.accounts.length) {
                accountSelect.innerHTML = '<option value="">Select an account</option>';

                data.accounts.forEach(account => {
                    const option = document.createElement('option');
                    option.value = account.account_id;
                    option.textContent = `${account.account_number} (${account.account_type}) - ${formatMoney(account.balance)}`;
                    option.dataset.accountNumber = account.account_number;
                    accountSelect.appendChild(option);
                });

                // Auto-select if only one account
                if (data.accounts.length === 1) {
                    accountSelect.value = data.accounts[0].account_id;
                    accountSelect.dispatchEvent(new Event('change'));
                }

                // If a preselected account number exists from the search result, select it
                if (window.__preselectedAccountNumber) {
                    for (const opt of accountSelect.options) {
                        if (opt.dataset && opt.dataset.accountNumber === window.__preselectedAccountNumber) {
                            accountSelect.value = opt.value;
                            accountSelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                    // clear the preselection after applying
                    window.__preselectedAccountNumber = null;
                }

                if (feedback) {
                    feedback.textContent = `Found ${data.accounts.length} active account(s)`;
                    feedback.className = 'form-text text-success';
                }
            } else {
                accountSelect.innerHTML = '<option value="">No accounts found</option>';
                if (feedback) {
                    feedback.textContent = data.message || 'No active savings accounts found for this member.';
                    feedback.className = 'form-text text-warning';
                }
            }
        } catch (error) {
            console.error('Error loading accounts:', error);
            if (feedback) {
                feedback.textContent = 'Error loading accounts. Please try again.';
                feedback.className = 'form-text text-danger';
            }
        }
    }
}

// Global Navigation Search
class GlobalMemberSearch extends LiveSearch {
    constructor() {
        super(
            'global_member_search',
            null,
            '/sacco/api/ajax_handler.php?action=search_member',
            2,
            150
        );
    }

    selectMember(member) {
        const APP_URL = window.APP_URL || '/sacco';
        window.location.href = `${APP_URL}/members/view.php?id=${member.member_id}`;
    }
}

// Legacy function for backward compatibility
function searchMember() {
    const input = document.getElementById('membership_no');
    if (input && input.value.trim()) {
        input.dispatchEvent(new Event('input'));
    }
}

// Member Search Functions for backward compatibility
function searchMemberOld(query, callback) {
    if (query.length < 2) {
        callback({ success: true, members: [] });
        return;
    }
    fetch(buildApiUrl('/api/ajax_handler.php?action=search_member&q=' + encodeURIComponent(query)))
        .then(response => response.json())
        .then(data => { if (callback) callback(data); })
        .catch(error => console.error('Search error:', error));
}

function fetchNotifications() {
    fetch(buildApiUrl('/api/ajax_handler.php?action=get_notifications'))
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (badge && data.count >= 0) {
                badge.textContent = data.count;
            }
        })
        .catch(() => {});
}

// Main Application Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize live search based on current page
    const currentPath = window.location.pathname;

    // Member form search (deposit, withdraw, loan application, etc.)
    if (document.getElementById('membership_no')) {
        window.memberSearch = new MemberFormSearch();
    }

    // Global navigation search
    if (document.getElementById('global_member_search')) {
        window.globalSearch = new GlobalMemberSearch();
    }

    // Enhanced Sidebar Navigation
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    // Mobile sidebar toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.contains(event.target) && 
                (!sidebarToggle || !sidebarToggle.contains(event.target))) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('show');
        }
    });

    // Persist collapsed state for navigation menus
    const collapseElements = document.querySelectorAll('.collapse');
    collapseElements.forEach(function(collapse) {
        const key = 'collapse_' + collapse.id;

        // Restore state from localStorage
        if (localStorage.getItem(key) === 'show') {
            collapse.classList.add('show');
            const trigger = document.querySelector(`[data-bs-target="#${collapse.id}"]`);
            if (trigger) {
                trigger.classList.remove('collapsed');
                trigger.setAttribute('aria-expanded', 'true');
            }
        }

        // Save state on change
        collapse.addEventListener('shown.bs.collapse', function() {
            localStorage.setItem(key, 'show');
        });

        collapse.addEventListener('hidden.bs.collapse', function() {
            localStorage.setItem(key, 'hidden');
        });
    });

    // Highlight current page in navigation
    const navLinks = document.querySelectorAll('.menu-link, .submenu-link');
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href !== '#') {
            let linkPath = href;
            if (href.includes('/sacco/')) {
                linkPath = href.split('/sacco/')[1] || '';
            }

            if (currentPath.includes(linkPath) && linkPath !== '') {
                link.classList.add('active');

                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    parentCollapse.classList.add('show');
                    const trigger = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (trigger) {
                        trigger.classList.remove('collapsed');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        }
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-hide success alerts
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });

    // Initialize tooltips and popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize notifications
    if (typeof fetchNotifications === 'function') {
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    }

    // Populate account_number and holder_name when account changes
    const accountSelect = document.getElementById('account_id');
    if (accountSelect) {
        accountSelect.addEventListener('change', function() {
            const opt = accountSelect.options[accountSelect.selectedIndex];
            const acctNo = opt && opt.dataset ? opt.dataset.accountNumber || '' : '';
            const acctField = document.getElementById('account_number');
            if (acctField) acctField.value = acctNo;

            const holderField = document.getElementById('holder_name');
            if (holderField && window.memberSearch && window.memberSearch.selectedMember) {
                holderField.value = window.memberSearch.selectedMember.full_name || '';
            }
        });
    }
});

// Export functions for global use
window.formatMoney = formatMoney;
window.confirmDelete = confirmDelete;
window.searchMember = searchMember;
window.LiveSearch = LiveSearch;
window.MemberFormSearch = MemberFormSearch;
