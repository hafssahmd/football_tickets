// Register Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const emailInput = document.getElementById('email');
    
    // Real-time validation
    if (emailInput) {
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', clearEmailValidation);
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword();
            if (confirmPasswordInput.value) {
                validatePasswordMatch();
            }
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    }
    
    // Form submission validation
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Email validation
    function validateEmail() {
        const email = emailInput.value.trim();
        const validationDiv = document.getElementById('email-validation');
        
        if (!email) {
            showFieldError(emailInput, validationDiv, '');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailInput, validationDiv, 'Format d\'email invalide');
            return false;
        }
        
        showFieldSuccess(emailInput, validationDiv, 'Email valide');
        return true;
    }
    
    function clearEmailValidation() {
        const validationDiv = document.getElementById('email-validation');
        clearFieldValidation(emailInput, validationDiv);
    }
    
    // Password validation
    function validatePassword() {
        const password = passwordInput.value;
        const strengthDiv = document.getElementById('password-strength');
        
        if (!password) {
            strengthDiv.className = 'password-strength';
            return false;
        }
        
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strengthDiv, strength);
        
        return password.length >= 8;
    }
    
    function calculatePasswordStrength(password) {
        let score = 0;
        
        // Length
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        
        // Complexity
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        if (score <= 2) return 'weak';
        if (score <= 3) return 'fair';
        if (score <= 4) return 'good';
        return 'strong';
    }
    
    function updatePasswordStrength(strengthDiv, strength) {
        strengthDiv.className = `password-strength visible ${strength}`;
        
        // Add strength bar if it doesn't exist
        if (!strengthDiv.querySelector('.password-strength-bar')) {
            strengthDiv.innerHTML = '<div class="password-strength-bar"></div>';
        }
    }
    
    // Password match validation
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const validationDiv = document.getElementById('confirm-password-validation');
        
        if (!confirmPassword) {
            clearFieldValidation(confirmPasswordInput, validationDiv);
            return false;
        }
        
        if (password !== confirmPassword) {
            showFieldError(confirmPasswordInput, validationDiv, 'Les mots de passe ne correspondent pas');
            return false;
        }
        
        showFieldSuccess(confirmPasswordInput, validationDiv, 'Les mots de passe correspondent');
        return true;
    }
    
    // Field validation helpers
    function showFieldError(input, validationDiv, message) {
        input.classList.add('error');
        input.classList.remove('success');
        if (validationDiv) {
            validationDiv.textContent = message;
            validationDiv.className = 'field-validation error';
        }
    }
    
    function showFieldSuccess(input, validationDiv, message) {
        input.classList.remove('error');
        input.classList.add('success');
        if (validationDiv) {
            validationDiv.textContent = message;
            validationDiv.className = 'field-validation success';
        }
    }
    
    function clearFieldValidation(input, validationDiv) {
        input.classList.remove('error', 'success');
        if (validationDiv) {
            validationDiv.textContent = '';
            validationDiv.className = 'field-validation';
        }
    }
    
    // Full form validation
    function validateForm() {
        let isValid = true;
        const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
        
        // Check required fields
        requiredFields.forEach(fieldName => {
            const field = document.getElementsByName(fieldName)[0];
            if (field && !field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else if (field) {
                field.classList.remove('error');
            }
        });
        
        // Validate email
        if (emailInput && !validateEmail()) {
            isValid = false;
        }
        
        // Validate password
        if (passwordInput && passwordInput.value.length < 8) {
            passwordInput.classList.add('error');
            isValid = false;
        }
        
        // Validate password match
        if (confirmPasswordInput && !validatePasswordMatch()) {
            isValid = false;
        }
        
        // Check terms acceptance
        const termsCheckbox = document.getElementsByName('terms')[0];
        if (termsCheckbox && !termsCheckbox.checked) {
            const termsGroup = termsCheckbox.closest('.form-group');
            if (termsGroup) {
                termsGroup.classList.add('error');
                setTimeout(() => termsGroup.classList.remove('error'), 3000);
            }
            isValid = false;
        }
        
        if (!isValid) {
            // Show error message
            showFormError('Veuillez corriger les erreurs dans le formulaire');
        }
        
        return isValid;
    }
    
    // Show form-level error
    function showFormError(message) {
        // Remove existing error alerts
        const existingAlerts = document.querySelectorAll('.alert-error');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new error alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-error';
        alertDiv.innerHTML = `<i class="icon-error"></i>${message}`;
        
        // Insert after the form header
        const authHeader = document.querySelector('.auth-header');
        if (authHeader) {
            authHeader.insertAdjacentElement('afterend', alertDiv);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});

// Phone number formatting (optional enhancement)
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('212')) {
        value = value.substring(3);
    }
    
    if (value.length >= 9) {
        value = value.substring(0, 9);
        value = value.replace(/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/, '+212 $1 $2 $3 $4 $5');
    } else if (value.length >= 6) {
        value = value.replace(/(\d{1})(\d{2})(\d{2})(\d+)/, '+212 $1 $2 $3 $4');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{1})(\d{2})(\d+)/, '+212 $1 $2 $3');
    } else if (value.length >= 1) {
        value = '+212 ' + value;
    }
    
    input.value = value;
}

// Add phone formatting to phone input
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }
});