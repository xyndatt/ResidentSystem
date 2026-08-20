// ========================================
// RESIDENT INFORMATION SYSTEM - MAIN JAVASCRIPT
// ========================================

// ========================================
// FORM VALIDATION FUNCTIONS
// ========================================

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid email
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number format
 * @param {string} phone - Phone number to validate
 * @returns {boolean} - True if valid phone
 */
function validatePhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

/**
 * Validate age
 * @param {number} age - Age to validate
 * @returns {boolean} - True if valid age
 */
function validateAge(age) {
    return age >= 0 && age <= 150;
}

/**
 * Calculate age from birthday
 * @param {string} birthday - Birthday in YYYY-MM-DD format
 * @returns {number} - Age in years
 */
function calculateAge(birthday) {
    const today = new Date();
    const birthDate = new Date(birthday);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

/**
 * Auto-calculate age when birthday changes
 */
function setupAgeCalculation() {
    const birthdayInputs = document.querySelectorAll('input[name="birthday"], input[name="children_birthday"]');
    
    birthdayInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value) {
                const age = calculateAge(this.value);
                const ageInput = this.closest('.form-group')?.querySelector('input[name="age"]') ||
                                this.closest('.form-group')?.nextElementSibling?.querySelector('input[name="age"]');
                if (ageInput) {
                    ageInput.value = age;
                }
            }
        });
    });
}

/**
 * Validate form fields
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} - True if form is valid
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            
            // Additional validation based on field type
            if (field.type === 'email' && !validateEmail(field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            }
            
            if (field.name === 'contact_number' && !validatePhone(field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// ========================================
// IMAGE UPLOAD VALIDATION
// ========================================

/**
 * Validate image file
 * @param {File} file - File to validate
 * @returns {object} - Validation result with success and message
 */
function validateImageFile(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        return {
            success: false,
            message: 'Only JPG, JPEG, and PNG files are allowed.'
        };
    }
    
    if (file.size > maxSize) {
        return {
            success: false,
            message: 'File size must not exceed 5MB.'
        };
    }
    
    return {
        success: true,
        message: 'File is valid.'
    };
}

/**
 * Preview image before upload
 */
function setupImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][name*="photo"], input[type="file"][name*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const validation = validateImageFile(this.files[0]);
                
                if (!validation.success) {
                    alert(validation.message);
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview') || 
                                  input.closest('.form-group')?.querySelector('img');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

// ========================================
// DYNAMIC FORM FIELDS
// ========================================

/**
 * Add dynamic child field
 */
function addChildField() {
    const childrenContainer = document.getElementById('childrenContainer');
    if (!childrenContainer) return;
    
    const childCount = childrenContainer.querySelectorAll('.child-item').length + 1;
    
    const childHTML = `
        <div class="child-item card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Child ${childCount}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeChildField(this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Child Name</label>
                    <input type="text" name="child_name[]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="child_birthday[]" class="form-control">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="child_age[]" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="child_gender[]" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>
    `;
    
    childrenContainer.insertAdjacentHTML('beforeend', childHTML);
    setupAgeCalculation();
}

/**
 * Remove dynamic child field
 */
function removeChildField(button) {
    button.closest('.child-item').remove();
}

// ========================================
// MODAL FUNCTIONS
// ========================================

/**
 * Open modal
 * @param {string} modalId - ID of modal to open
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 * @param {string} modalId - ID of modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * Close modal when clicking outside of it
 */
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Format date to readable format
 * @param {string} dateString - Date string in YYYY-MM-DD format
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Show notification
 * @param {string} message - Message to display
 * @param {string} type - Type of notification (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    const alertClass = `alert alert-${type}`;
    const alertHTML = `<div class="${alertClass}" role="alert">${message}</div>`;
    
    const container = document.querySelector('.page-header') || document.querySelector('.main-content');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            container.querySelector('.alert')?.remove();
        }, 5000);
    }
}

/**
 * Custom confirmation modal system (blue glass theme)
 * Replaces native browser confirm() with styled modal
 */
let _confirmModalResolve = null;

function _injectConfirmModal() {
    if (document.getElementById('confirmModal')) return;
    const html = `
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-icon" id="confirmModalIcon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 id="confirmModalTitle">Are you sure?</h3>
            <p id="confirmModalMessage">This action cannot be undone.</p>
            <div class="confirm-modal-actions">
                <button class="btn btn-cancel" id="confirmModalCancel">Cancel</button>
                <button class="btn btn-confirm-destructive" id="confirmModalOk">Confirm</button>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('confirmModalCancel').addEventListener('click', function() {
        _hideConfirmModal(false);
    });
    document.getElementById('confirmModalOk').addEventListener('click', function() {
        _hideConfirmModal(true);
    });
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) _hideConfirmModal(false);
    });
}

function _hideConfirmModal(confirmed) {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.classList.remove('active');
    if (_confirmModalResolve) {
        _confirmModalResolve(confirmed);
        _confirmModalResolve = null;
    }
}

/**
 * Show a styled confirmation modal
 * @param {string} title - Modal title
 * @param {string} message - Modal description
 * @param {object} options - { type: 'destructive'|'warning'|'primary', confirmText: string, icon: string }
 * @returns {Promise<boolean>} - Resolves true if confirmed, false if cancelled
 */
function showConfirmModal(title, message, options = {}) {
    _injectConfirmModal();
    const modal = document.getElementById('confirmModal');
    const iconEl = document.getElementById('confirmModalIcon');
    const titleEl = document.getElementById('confirmModalTitle');
    const msgEl = document.getElementById('confirmModalMessage');
    const btnOk = document.getElementById('confirmModalOk');
    const type = options.type || 'destructive';

    titleEl.textContent = title;
    msgEl.textContent = message;
    btnOk.textContent = options.confirmText || 'Confirm';

    iconEl.className = 'confirm-modal-icon ' + type;
    iconEl.innerHTML = '<i class="bi ' + (options.icon || (type === 'destructive' ? 'bi-trash' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-question-circle')) + '"></i>';

    btnOk.className = 'btn ' + (type === 'destructive' ? 'btn-confirm-destructive' : type === 'warning' ? 'btn-confirm-destructive' : 'btn-confirm-primary');

    modal.classList.add('active');

    return new Promise(function(resolve) {
        _confirmModalResolve = resolve;
    });
}

/**
 * Confirm action (legacy wrapper for backward compatibility)
 * @param {string} message - Confirmation message
 * @returns {boolean} - True if confirmed
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Print page
 */
function printPage() {
    window.print();
}

/**
 * Export table to CSV
 * @param {string} tableId - ID of table to export
 * @param {string} filename - Name of CSV file
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 * @param {string} csv - CSV content
 * @param {string} filename - Name of file
 */
function downloadCSV(csv, filename) {
    const link = document.createElement('a');
    const blob = new Blob([csv], { type: 'text/csv' });
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// ========================================
// SEARCH AND FILTER FUNCTIONS
// ========================================

/**
 * Filter table by search term
 * @param {string} inputId - ID of search input
 * @param {string} tableId - ID of table to filter
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// ========================================
// SIDEBAR TOGGLE
// ========================================

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// ========================================
// LOADING INDICATOR
// ========================================

/**
 * Show loading indicator
 */
function showLoading() {
    const loader = document.getElementById('loader') || document.createElement('div');
    loader.id = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;';
    document.body.appendChild(loader);
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.remove();
    }
}

// ========================================
// TOAST NOTIFICATION SYSTEM
// ========================================

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of toast (success, error, warning, info)
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
        document.body.appendChild(container);
    }

    const icons = {
        success: '&#10004;',
        error: '&#10008;',
        warning: '&#9888;',
        info: '&#8505;'
    };

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    const toast = document.createElement('div');
    toast.style.cssText = 'pointer-events:auto;display:flex;align-items:center;gap:10px;padding:12px 20px;border-radius:6px;color:#fff;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.15);opacity:0;transition:opacity 0.3s ease;background-color:' + (colors[type] || colors.info) + ';';
    toast.innerHTML = '<span style="font-size:18px;">' + (icons[type] || icons.info) + '</span><span>' + message + '</span>';

    container.appendChild(toast);

    requestAnimationFrame(function() {
        toast.style.opacity = '1';
    });

    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 4000);
}

// ========================================
// DUPLICATE SUBMISSION PREVENTION
// ========================================

/**
 * Prevent duplicate form submission
 * @param {HTMLElement} button - The submit button to disable
 */
function preventDuplicateSubmit(button) {
    if (!button) return;
    button.dataset.originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = 'Processing...';
}

// ========================================
// PASSWORD STRENGTH CHECKER
// ========================================

/**
 * Check password strength
 * @param {string} password - Password to evaluate
 * @returns {object} - { score: 0-4, label: string, class: string }
 */
function checkPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    if (score > 4) score = 4;

    const levels = {
        0: { label: 'Weak', class: 'strength-weak' },
        1: { label: 'Weak', class: 'strength-weak' },
        2: { label: 'Fair', class: 'strength-fair' },
        3: { label: 'Good', class: 'strength-good' },
        4: { label: 'Strong', class: 'strength-strong' }
    };

    return { score: score, label: levels[score].label, class: levels[score].class };
}

/**
 * Setup password strength indicators — disabled per user request.
 * Weak/Fair/Good/Strong labels removed from the UI.
 */
function setupPasswordStrength() {
    // Strength labels (Weak/Fair/Good/Strong) removed per user request.
}

// ========================================
// PASSWORD SHOW/TOGGLE
// ========================================

/**
 * Setup password show/hide toggles for all password inputs
 */
function setupPasswordToggles() {
    var passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function(input) {
        // Skip if already inside a .password-wrapper (login page has its own toggle markup)
        if (input.parentNode.classList.contains('password-wrapper')) return;

        var wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';

        wrapper.appendChild(toggleBtn);

        toggleBtn.addEventListener('click', function() {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });
}

// ========================================
// SEARCH AUTOCOMPLETE
// ========================================

/**
 * Setup search autocomplete on an input
 * @param {string} inputId - ID of the search input
 * @param {string} searchUrl - URL to fetch suggestions from
 */
function setupSearchAutocomplete(inputId, searchUrl) {
    var input = document.getElementById(inputId);
    if (!input) return;

    var debounceTimer = null;
    var activeIndex = -1;

    input.parentNode.style.position = 'relative';

    var dropdown = document.createElement('div');
    dropdown.className = 'autocomplete-dropdown';
    dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ddd;border-top:none;border-radius:0 0 4px 4px;display:none;z-index:9999;max-height:250px;overflow-y:auto;box-shadow:0 4px 8px rgba(0,0,0,0.1);';
    input.parentNode.appendChild(dropdown);

    function closeDropdown() {
        dropdown.style.display = 'none';
        activeIndex = -1;
    }

    function updateActive(items) {
        items.forEach(function(item, i) {
            item.style.background = i === activeIndex ? '#e9ecef' : '#fff';
        });
    }

    input.addEventListener('keyup', function(e) {
        var query = this.value.trim();
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
            var items = dropdown.querySelectorAll('.autocomplete-item');
            if (items.length === 0) return;
            if (e.key === 'ArrowDown') {
                activeIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
            } else if (e.key === 'ArrowUp') {
                activeIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                input.value = items[activeIndex].textContent;
                closeDropdown();
                return;
            }
            updateActive(items);
            return;
        }

        clearTimeout(debounceTimer);
        if (query.length < 2) {
            closeDropdown();
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch(searchUrl + '?q=' + encodeURIComponent(query))
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    dropdown.innerHTML = '';
                    activeIndex = -1;
                    if (!data || data.length === 0) {
                        closeDropdown();
                        return;
                    }
                    data.forEach(function(item) {
                        var div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:14px;';
                        div.textContent = typeof item === 'string' ? item : (item.label || item.name || item.value || '');
                        div.addEventListener('mouseenter', function() {
                            this.style.background = '#e9ecef';
                        });
                        div.addEventListener('mouseleave', function() {
                            this.style.background = '#fff';
                        });
                        div.addEventListener('click', function() {
                            input.value = div.textContent;
                            closeDropdown();
                        });
                        dropdown.appendChild(div);
                    });
                    dropdown.style.display = 'block';
                })
                .catch(function() {
                    closeDropdown();
                });
        }, 300);
    });

    input.addEventListener('blur', function() {
        setTimeout(closeDropdown, 200);
    });

    document.addEventListener('click', function(e) {
        if (!input.parentNode.contains(e.target)) {
            closeDropdown();
        }
    });
}

// ========================================
// CONFIRMATION MODAL
// ========================================

/**
 * Show a confirmation modal
 * @param {string} message - Message to display
 * @param {function} onConfirm - Callback when confirmed
 * @param {string} title - Modal title
 */
function showConfirmModal(message, onConfirm, title = 'Confirm Action') {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10001;display:flex;align-items:center;justify-content:center;';

    var modal = document.createElement('div');
    modal.style.cssText = 'background:#fff;border-radius:8px;padding:24px;max-width:420px;width:90%;box-shadow:0 8px 24px rgba(0,0,0,0.2);';

    modal.innerHTML =
        '<h5 style="margin:0 0 12px;font-size:18px;">' + title + '</h5>' +
        '<p style="margin:0 0 20px;color:#555;">' + message + '</p>' +
        '<div style="display:flex;justify-content:flex-end;gap:10px;">' +
            '<button type="button" class="confirm-cancel-btn" style="padding:8px 16px;border:1px solid #ccc;border-radius:4px;background:#fff;cursor:pointer;">Cancel</button>' +
            '<button type="button" class="confirm-ok-btn" style="padding:8px 16px;border:none;border-radius:4px;background:#dc3545;color:#fff;cursor:pointer;">Confirm</button>' +
        '</div>';

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    overlay.querySelector('.confirm-cancel-btn').addEventListener('click', function() {
        overlay.remove();
    });

    overlay.querySelector('.confirm-ok-btn').addEventListener('click', function() {
        onConfirm();
        overlay.remove();
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
}

// ========================================
// FORM SECTION TOGGLE
// ========================================

/**
 * Toggle visibility of a form section with slide animation
 * @param {string} sectionId - ID of the section to toggle
 */
function toggleFormSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    if (section.style.maxHeight && section.style.maxHeight !== '0px') {
        section.style.overflow = 'hidden';
        section.style.transition = 'max-height 0.3s ease';
        section.style.maxHeight = '0px';
        setTimeout(function() {
            section.style.display = 'none';
        }, 300);
    } else {
        section.style.display = '';
        section.style.overflow = 'hidden';
        section.style.transition = 'max-height 0.3s ease';
        section.style.maxHeight = section.scrollHeight + 'px';
        setTimeout(function() {
            section.style.overflow = '';
            section.style.maxHeight = '';
        }, 300);
    }
}

// ========================================
// DATATABLE SEARCH WITH HIGHLIGHTING
// ========================================

/**
 * Setup enhanced table search with highlighting and result count
 * @param {string} inputId - ID of search input
 * @param {string} tableId - ID of table to search
 */
function setupTableSearch(inputId, tableId) {
    var input = document.getElementById(inputId);
    var table = document.getElementById(tableId);
    if (!input || !table) return;

    var resultCount = document.createElement('small');
    resultCount.className = 'table-search-count';
    resultCount.style.cssText = 'margin-left:10px;color:#6c757d;font-size:13px;';
    input.parentNode.appendChild(resultCount);

    var emptyState = document.createElement('tr');
    emptyState.className = 'empty-state-row';
    emptyState.style.display = 'none';
    emptyState.innerHTML = '<td colspan="100" style="text-align:center;padding:20px;color:#6c757d;">No results found.</td>';

    var tbody = table.querySelector('tbody');
    if (tbody) {
        tbody.appendChild(emptyState);
    }

    input.addEventListener('keyup', function() {
        var searchTerm = this.value.toLowerCase();
        var rows = table.querySelectorAll('tbody tr:not(.empty-state-row)');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var text = row.innerText.toLowerCase();
            var match = text.includes(searchTerm);
            row.style.display = match ? '' : 'none';

            if (searchTerm && match) {
                var cells = row.querySelectorAll('td');
                cells.forEach(function(cell) {
                    var original = cell.innerHTML;
                    if (!cell.dataset.originalText) {
                        cell.dataset.originalText = original;
                    }
                    var regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                    cell.innerHTML = cell.dataset.originalText.replace(regex, '<mark style="background:#fff3cd;padding:0 2px;">$1</mark>');
                });
            } else if (!searchTerm && cell && cell.dataset && cell.dataset.originalText) {
                row.querySelectorAll('td').forEach(function(cell) {
                    if (cell.dataset.originalText) {
                        cell.innerHTML = cell.dataset.originalText;
                    }
                });
            }

            if (match) visibleCount++;
        });

        if (searchTerm) {
            resultCount.textContent = visibleCount + ' result' + (visibleCount !== 1 ? 's' : '') + ' found';
        } else {
            resultCount.textContent = '';
        }

        emptyState.style.display = visibleCount === 0 && searchTerm ? '' : 'none';
    });
}

// ========================================
// FORMAT PHONE NUMBER
// ========================================

/**
 * Auto-format phone number input as (XXX) XXX-XXXX
 * @param {HTMLInputElement} input - Phone number input element
 */
function formatPhoneNumber(input) {
    if (!input) return;

    input.addEventListener('input', function() {
        var digits = this.value.replace(/\D/g, '');
        if (digits.length > 10) digits = digits.substring(0, 10);

        var formatted = '';
        if (digits.length > 0) {
            formatted = '(' + digits.substring(0, 3);
        }
        if (digits.length >= 3) {
            formatted += ') ' + digits.substring(3, 6);
        }
        if (digits.length >= 6) {
            formatted += '-' + digits.substring(6, 10);
        }

        this.value = formatted;
    });
}

// ========================================
// INITIALIZATION
// ========================================

/**
 * Initialize all functions on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    setupAgeCalculation();
    setupImagePreview();
    setupPasswordToggles();
    setupPasswordStrength();

    // Setup form validation
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Please fill in all required fields correctly.', 'warning');
            }
        });
    });

    // Setup input change listeners to remove invalid class
    var inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});

// ========================================
// SPA Navigation System
// ========================================

var _spaContainer = null;
var _spaCurrentUrl = '';
var _spaInitialized = false;

function initSPA(containerId) {
    _spaContainer = document.getElementById(containerId);
    if (!_spaContainer) return;

    // Intercept ALL internal SPA links (data-spa AND any link inside spa-content)
    document.addEventListener('click', function(e) {
        // Check for data-spa link
        var link = e.target.closest('a[data-spa]');
        if (!link) {
            // Also check for links inside spa-content that point to .php files
            var innerLink = e.target.closest('#spa-content a');
            if (innerLink) {
                var href = innerLink.getAttribute('href');
                if (href && href.indexOf('.php') > -1 && href.indexOf('logout') === -1) {
                    link = innerLink;
                }
            }
        }
        if (!link) return;

        var href = link.getAttribute('href');
        if (!href || href.indexOf('logout') > -1) return;

        e.preventDefault();
        if (href === _spaCurrentUrl) return;
        navigateSPA(href);
    });

    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.spaUrl) {
            loadSPAPage(e.state.spaUrl, false);
        }
    });

    // Load initial page content
    var currentPath = window.location.pathname;
    var page = currentPath.split('/').pop();
    if (page && page !== 'index.php' && page !== '') {
        // We're on a direct page URL, load it as partial
        loadSPAPage(page + window.location.search, false);
    } else {
        // We're on index.php, load dashboard
        loadSPAPage('dashboard.php', true);
    }
}

function navigateSPA(url) {
    loadSPAPage(url, true);
}

function loadSPAPage(url, pushState) {
    if (!_spaContainer) return;

    // Show loading
    _spaContainer.innerHTML = '<div class="spa-loading"><div class="spinner"></div><p>Loading...</p></div>';
    _spaContainer.classList.remove('spa-fade-in');

    // Ensure partial parameter
    var separator = url.indexOf('?') > -1 ? '&' : '?';
    var partialUrl = url + separator + 'partial=1';

    _spaCurrentUrl = url;

    fetch(partialUrl)
        .then(function(response) {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(function(html) {
            _spaContainer.innerHTML = html;
            _spaContainer.classList.add('spa-fade-in');

            // Update URL
            if (pushState) {
                var fullUrl = url.split('?')[0];
                history.pushState({ spaUrl: url }, '', fullUrl);
            }

            // Update active sidebar link
            updateActiveLink(url);

            // Update page title
            updatePageTitle(url);

            // Re-initialize JS components
            reinitSPAComponents();

            // Scroll to top
            window.scrollTo(0, 0);
        })
        .catch(function(err) {
            _spaContainer.innerHTML = '<div class="spa-loading"><p style="color:#dc3545;">Failed to load page. Please try again.</p></div>';
            console.error('SPA load error:', err);
        });
}

function updateActiveLink(url) {
    var page = url.split('?')[0].split('/').pop();
    var links = document.querySelectorAll('.sidebar-menu a[data-spa]');
    links.forEach(function(link) {
        var linkPage = link.getAttribute('href').split('?')[0].split('/').pop();
        if (linkPage === page) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function updatePageTitle(url) {
    var page = url.split('?')[0].replace('.php', '').replace('_', ' ');
    page = page.charAt(0).toUpperCase() + page.slice(1);
    var titles = {
        'dashboard': 'Dashboard',
        'personal info': 'Personal Information',
        'family info': 'Family Information',
        'references': 'References',
        'photo upload': 'Photo Upload',
        'print profile': 'Print Profile',
        'change password': 'Change Password',
        'surveys': 'Health Surveys',
        'take survey': 'Take Survey'
    };
    document.title = (titles[page] || page) + ' - Resident Information System';
}

function reinitSPAComponents() {
    setupAgeCalculation();
    setupImagePreview();
    setupPasswordToggles();
    setupPasswordStrength();
    setupSPAForms();

    // Re-init table filters and autocomplete
    var filterInputs = _spaContainer.querySelectorAll('[data-filter-table]');
    filterInputs.forEach(function(input) {
        var tableId = input.getAttribute('data-filter-table');
        if (tableId) filterTable(input.id, tableId);
    });

    var autoInputs = _spaContainer.querySelectorAll('[data-autocomplete-url]');
    autoInputs.forEach(function(input) {
        var url = input.getAttribute('data-autocomplete-url');
        if (url) setupSearchAutocomplete(input.id, url);
    });

    // Setup form validation
    var forms = _spaContainer.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Please fill in all required fields correctly.', 'warning');
            }
        });
    });

    // Setup input change listeners
    var inputs = _spaContainer.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });

    // Handle toasts from loaded content (script tags don't execute in innerHTML)
    var toastContainer = _spaContainer.querySelector('#toast-container');
    if (toastContainer) {
        var toasts = toastContainer.querySelectorAll('.toast-alert');
        toasts.forEach(function(toast) {
            var toastType = toast.getAttribute('data-type') || toast.className.replace('toast-alert', '').trim() || 'success';
            var toastMsg = toast.textContent.trim();
            if (toastMsg) {
                showToast(toastMsg, toastType);
            }
        });
    }
}

// ========================================
// AJAX Form Submission for SPA
// ========================================

function setupSPAForms() {
    if (!_spaContainer) return;

    // Intercept ALL forms in SPA content
    var forms = _spaContainer.querySelectorAll('form');
    forms.forEach(function(form) {
        // Skip forms that opt out
        if (form.hasAttribute('data-no-ajax')) return;
        form.removeEventListener('submit', handleAjaxForm);
        form.addEventListener('submit', handleAjaxForm);
    });
}

function handleAjaxForm(e) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    var originalText = btn ? btn.textContent : '';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
    }

    var formData = new FormData(form);

    // Build URL: use form action and append ?partial=1
    var formUrl = form.getAttribute('action') || window.location.pathname.split('/').pop();
    var separator = formUrl.indexOf('?') > -1 ? '&' : '?';
    var ajaxUrl = formUrl.split('?')[0] + separator + 'partial=1';

    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.text(); })
    .then(function(html) {
        // Check if response contains a success message
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        // Extract toast messages from the response (displayToasts() won't auto-execute in innerHTML)
        var toastContainer = tempDiv.querySelector('#toast-container');
        var toastsExtracted = [];
        if (toastContainer) {
            var toastEls = toastContainer.querySelectorAll('.toast-alert');
            toastEls.forEach(function(t) {
                var tType = t.getAttribute('data-type') || 'success';
                var tMsg = t.textContent.trim();
                if (tMsg) {
                    toastsExtracted.push({ message: tMsg, type: tType });
                }
            });
        }

        var hasSuccess = tempDiv.querySelector('.alert-success') || tempDiv.querySelector('.toast-alert');

        if (hasSuccess || toastsExtracted.length > 0) {
            // Show extracted toasts using JS showToast, then reload the page content
            if (toastsExtracted.length > 0) {
                toastsExtracted.forEach(function(t) {
                    showToast(t.message, t.type);
                });
            } else {
                showToast(hasSuccess.textContent.trim(), 'success');
            }
            setTimeout(function() {
                navigateSPA(_spaCurrentUrl);
            }, 1000);
        } else {
            // Replace content with response (shows errors + form)
            _spaContainer.innerHTML = html;
            _spaContainer.classList.add('spa-fade-in');
            reinitSPAComponents();
        }
    })
    .catch(function(err) {
        showNotification('An error occurred. Please try again.', 'error');
        console.error('AJAX form error:', err);
    })
    .finally(function() {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
}

// ========================================
// Child field add/remove (SPA-compatible)
// ========================================

function addChildField() {
    var container = document.getElementById('childrenContainer');
    if (!container) return;
    var index = container.children.length;
    var html = '<div class="form-group child-entry" style="display:grid;grid-template-columns:1fr 120px 100px 40px;gap:0.5rem;align-items:end;">' +
        '<div><label>Child Name</label><input type="text" name="child_name[]" class="form-control" required></div>' +
        '<div><label>Birthday</label><input type="date" name="child_birthday[]" class="form-control"></div>' +
        '<div><label>Gender</label><select name="child_gender[]" class="form-control"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>' +
        '<div><button type="button" class="btn btn-danger" onclick="removeChildField(this)" style="margin-top:1.5rem;"><i class="bi bi-trash"></i></button></div>' +
        '</div>';
    container.insertAdjacentHTML('beforeend', html);
}

function removeChildField(btn) {
    var entry = btn.closest('.child-entry');
    if (entry) entry.remove();
}
