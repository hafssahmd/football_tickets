// Authentication Utilities
document.addEventListener('DOMContentLoaded', function() {
    initializePasswordToggles();
    initializeFormAnimations();
    initializeLoadingStates();
});

// Password toggle functionality
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggleBtn = input.parentElement.querySelector('.password-toggle');
    const icon = toggleBtn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'icon-eye-off';
        toggleBtn.setAttribute('aria-label', 'Masquer le mot de passe');
    } else {
        input.type = 'password';
        icon.className = 'icon-eye';
        toggleBtn.setAttribute('aria-label', 'Afficher le mot de passe');
    }
}

// Initialize password toggles
function initializePasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const toggleBtn = input.parentElement.querySelector('.password-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                togglePassword(input.id);
            });
        }
    });
}

// Form animations
function initializeFormAnimations() {
    const formInputs = document.querySelectorAll('.auth-form input');
    
    formInputs.forEach(input => {
        // Add focus animations
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value.trim() !== '') {
                this.parentElement.classList.add('filled');
            } else {
                this.parentElement.classList.remove('filled');
            }
        });
        
        // Initial state
        if (input.value.trim() !== '') {
            input.parentElement.classList.add('filled');
        }
    });
}

// Loading states for forms
function initializeLoadingStates() {
    const forms = document.querySelectorAll('.auth-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                setButtonLoading(submitBtn, true);
                
                // Reset loading state after 10 seconds (fallback)
                setTimeout(() => {
                    setButtonLoading(submitBtn, false);
                }, 10000);
            }
        });
    });
}

// Button loading state
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.classList.add('loading');
        button.disabled = true;
        button.originalText = button.innerHTML;
        button.innerHTML = 'Traitement...';
    } else {
        button.classList.remove('loading');
        button.disabled = false;
        if (button.originalText) {
            button.innerHTML = button.originalText;
        }
    }
}

// Social login handlers (placeholder)
function initializeSocialLogin() {
    const googleBtn = document.querySelector('.btn-google');
    const facebookBtn = document.querySelector('.btn-facebook');
    
    if (googleBtn) {
        googleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleGoogleLogin();
        });
    }
    
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleFacebookLogin();
        });
    }
}

function handleGoogleLogin() {
    // Placeholder for Google OAuth integration
    console.log('Google login clicked');
    alert('Connexion Google - Fonctionnalité à implémenter');
}

function handleFacebookLogin() {
    // Placeholder for Facebook OAuth integration
    console.log('Facebook login clicked');
    alert('Connexion Facebook - Fonctionnalité à implémenter');
}

// Form validation utilities
function showFormMessage(message, type = 'info') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.form-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type} form-message`;
    messageDiv.innerHTML = `<i class="icon-${type}"></i>${message}`;
    
    // Insert message
    const form = document.querySelector('.auth-form');
    if (form) {
        form.insertAdjacentElement('beforebegin', messageDiv);
        
        // Auto-remove success messages
        if (type === 'success') {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }
}

// Input validation utilities
function validateField(input, validationRules) {
    const value = input.value.trim();
    const errors = [];
    
    // Required validation
    if (validationRules.required && !value) {
        errors.push('Ce champ est obligatoire');
    }
    
    // Min length validation
    if (validationRules.minLength && value.length < validationRules.minLength) {
        errors.push(`Minimum ${validationRules.minLength} caractères requis`);
    }
    
    // Max length validation
    if (validationRules.maxLength && value.length > validationRules.maxLength) {
        errors.push(`Maximum ${validationRules.maxLength} caractères autorisés`);
    }
    
    // Email validation
    if (validationRules.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value && !emailRegex.test(value)) {
            errors.push('Format d\'email invalide');
        }
    }
    
    // Phone validation
    if (validationRules.phone) {
        const phoneRegex = /^(\+212|0)[5-7]\d{8}$/;
        if (value && !phoneRegex.test(value.replace(/\s/g, ''))) {
            errors.push('Format de téléphone invalide');
        }
    }
    
    // Password validation
    if (validationRules.password) {
        if (value.length < 8) {
            errors.push('Le mot de passe doit contenir au moins 8 caractères');
        }
        if (!/(?=.*[a-z])/.test(value)) {
            errors.push('Le mot de passe doit contenir au moins une minuscule');
        }
        if (!/(?=.*[A-Z])/.test(value)) {
            errors.push('Le mot de passe doit contenir au moins une majuscule');
        }
        if (!/(?=.*\d)/.test(value)) {
            errors.push('Le mot de passe doit contenir au moins un chiffre');
        }
    }
    
    return errors;
}

// Accessibility improvements
function improveAccessibility() {
    // Add ARIA labels to password toggle buttons
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.setAttribute('aria-label', 'Afficher/masquer le mot de passe');
        toggle.setAttribute('type', 'button');
    });
    
    // Add ARIA live regions for validation messages
    const validationDivs = document.querySelectorAll('.field-validation');
    validationDivs.forEach(div => {
        div.setAttribute('aria-live', 'polite');
        div.setAttribute('aria-atomic', 'true');
    });
    
    // Improve form labels
    const inputs = document.querySelectorAll('.auth-form input');
    inputs.forEach(input => {
        const label = document.querySelector(`label[for="${input.id}"]`);
        if (!label && input.id) {
            // Create implicit label relationship
            const explicitLabel = input.parentElement.querySelector('label');
            if (explicitLabel) {
                explicitLabel.setAttribute('for', input.id);
            }
        }
    });
}

// Initialize accessibility improvements
document.addEventListener('DOMContentLoaded', function() {
    improveAccessibility();
    initializeSocialLogin();
});

// Error handling for AJAX requests (if needed)
function handleAjaxError(xhr, status, error) {
    console.error('AJAX Error:', error);
    
    let message = 'Une erreur est survenue. Veuillez réessayer.';
    
    if (xhr.status === 400) {
        message = 'Données invalides. Veuillez vérifier votre saisie.';
    } else if (xhr.status === 401) {
        message = 'Session expirée. Veuillez vous reconnecter.';
    } else if (xhr.status === 403) {
        message = 'Accès non autorisé.';
    } else if (xhr.status === 404) {
        message = 'Service non trouvé.';
    } else if (xhr.status >= 500) {
        message = 'Erreur serveur. Veuillez réessayer plus tard.';
    }
    
    showFormMessage(message, 'error');
}

// Utility function to debounce input validation
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