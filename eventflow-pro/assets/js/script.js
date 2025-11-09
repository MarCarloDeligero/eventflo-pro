// EventFlow Pro - Main JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize all components
    initImageUploadPreview();
    initFormValidations();
    initAutoSave();
    initSearchFilters();
    initTooltips();
    initCountdownTimers();
    initSmoothScrolling();
}

// Image upload preview
function initImageUploadPreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const previewId = this.getAttribute('data-preview') || 'image-preview';
            const preview = document.getElementById(previewId);
            const img = preview.querySelector('img') || document.createElement('img');
            
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.className = 'img-fluid rounded shadow-sm';
                img.style.maxHeight = '200px';
                
                if (!preview.contains(img)) {
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }
                
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });
}

// Form validations
function initFormValidations() {
    const forms = document.querySelectorAll('form[needs-validation]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });
    });
}

function validateField(field) {
    const isValid = field.checkValidity();
    const feedback = field.parentNode.querySelector('.invalid-feedback') || createFeedbackElement(field);
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        feedback.style.display = 'none';
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        feedback.textContent = getValidationMessage(field);
        feedback.style.display = 'block';
    }
}

function createFeedbackElement(field) {
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    field.parentNode.appendChild(feedback);
    return feedback;
}

function getValidationMessage(field) {
    if (field.validity.valueMissing) {
        return 'This field is required.';
    }
    if (field.validity.typeMismatch) {
        return 'Please enter a valid value.';
    }
    if (field.validity.tooShort) {
        return `Please enter at least ${field.minLength} characters.`;
    }
    if (field.validity.tooLong) {
        return `Please enter no more than ${field.maxLength} characters.`;
    }
    if (field.validity.rangeUnderflow) {
        return `Value must be greater than or equal to ${field.min}.`;
    }
    if (field.validity.rangeOverflow) {
        return `Value must be less than or equal to ${field.max}.`;
    }
    return 'Please correct this field.';
}

// Auto-save for forms
function initAutoSave() {
    const autoSaveForms = document.querySelectorAll('form[auto-save]');
    
    autoSaveForms.forEach(form => {
        let saveTimeout;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    autoSaveForm(form);
                }, 1000);
            });
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    const saveIndicator = form.querySelector('.auto-save-indicator') || createSaveIndicator(form);
    
    // Show saving indicator
    saveIndicator.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    saveIndicator.classList.remove('d-none', 'text-success', 'text-danger');
    saveIndicator.classList.add('text-primary');
    
    // Simulate API call (replace with actual API call)
    setTimeout(() => {
        saveIndicator.innerHTML = '<i class="fas fa-check me-1"></i>Saved';
        saveIndicator.classList.remove('text-primary');
        saveIndicator.classList.add('text-success');
        
        // Hide after 2 seconds
        setTimeout(() => {
            saveIndicator.classList.add('d-none');
        }, 2000);
    }, 500);
}

function createSaveIndicator(form) {
    const indicator = document.createElement('div');
    indicator.className = 'auto-save-indicator small mt-2 d-none';
    form.appendChild(indicator);
    return indicator;
}

// Search and filters
function initSearchFilters() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value, this.dataset.searchType);
            }, 300);
        });
    });
}

function performSearch(query, type = 'events') {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer) return;
    
    // Show loading
    resultsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Searching...</p>
        </div>
    `;
    
    // Simulate API call
    setTimeout(() => {
        // This would be replaced with actual API call
        const results = simulateSearchResults(query, type);
        displaySearchResults(results, resultsContainer);
    }, 500);
}

function simulateSearchResults(query, type) {
    // Mock data - replace with actual API response
    return [
        { id: 1, title: 'Tech Conference 2024', type: 'event' },
        { id: 2, title: 'Web Development Workshop', type: 'event' },
        { id: 3, title: 'JavaScript Meetup', type: 'event' }
    ].filter(item => 
        item.title.toLowerCase().includes(query.toLowerCase())
    );
}

function displaySearchResults(results, container) {
    if (results.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-search fa-2x text-muted mb-3"></i>
                <p class="text-muted">No results found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = results.map(item => `
        <div class="search-result-item p-3 border-bottom">
            <h6 class="mb-1">${escapeHtml(item.title)}</h6>
            <small class="text-muted">${item.type}</small>
        </div>
    `).join('');
}

// Tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Countdown timers for events
function initCountdownTimers() {
    const countdownElements = document.querySelectorAll('.countdown-timer');
    
    countdownElements.forEach(element => {
        const targetDate = new Date(element.dataset.targetDate).getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;
            
            if (distance < 0) {
                element.innerHTML = 'Event started';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            element.innerHTML = `
                <div class="countdown-item">
                    <span class="countdown-number">${days}</span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${hours}</span>
                    <span class="countdown-label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${minutes}</span>
                    <span class="countdown-label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${seconds}</span>
                    <span class="countdown-label">Seconds</span>
                </div>
            `;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
}

// Smooth scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility functions
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}


// API functions
class EventFlowAPI {
    static async request(endpoint, options = {}) {
        const baseURL = '/eventflow-pro/api';
        const url = baseURL + endpoint;
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    // Event methods
    static async getEvents(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        return this.request('/events?' + queryString);
    }
    
    static async getEvent(id) {
        return this.request(`/events/${id}`);
    }
    
    static async createEvent(eventData) {
        return this.request('/events', {
            method: 'POST',
            body: JSON.stringify(eventData)
        });
    }
    
    static async updateEvent(id, eventData) {
        return this.request(`/events/${id}`, {
            method: 'PUT',
            body: JSON.stringify(eventData)
        });
    }
    
    static async deleteEvent(id) {
        return this.request(`/events/${id}`, {
            method: 'DELETE'
        });
    }
    
    // Registration methods
    static async registerForEvent(eventId, data = {}) {
        return this.request(`/events/${eventId}/register`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    static async cancelRegistration(registrationId) {
        return this.request(`/registrations/${registrationId}/cancel`, {
            method: 'POST'
        });
    }
    
    // User methods
    static async getProfile() {
        return this.request('/users/profile');
    }
    
    static async updateProfile(profileData) {
        return this.request('/users/profile', {
            method: 'PUT',
            body: JSON.stringify(profileData)
        });
    }
}

// Export for global access
window.EventFlowAPI = EventFlowAPI;