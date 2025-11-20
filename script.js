class AuthApp {
    constructor() {
        this.currentForm = 'login';
        this.csrfToken = document.querySelector('input[name="csrf_token"]').value;
        this.isAnimating = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.showForm(this.currentForm, false);
        console.log('AuthApp initialized - Forms ready');
    }

    bindEvents() {
        console.log('Binding events...');

        // Form switching
        document.querySelectorAll('.switch-form a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetForm = e.target.getAttribute('data-form');
                console.log('Switch clicked, target:', targetForm);
                if (targetForm && targetForm !== this.currentForm) {
                    this.showForm(targetForm, true);
                }
            });
        });

        // Password toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', (e) => {
                this.togglePassword(e.target);
            });
        });

        // Form submissions
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        if (signupForm) {
            signupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSignup();
            });
        }

        // Slider dots
        document.querySelectorAll('.slider-dot').forEach(dot => {
            dot.addEventListener('click', (e) => {
                const targetForm = e.target.getAttribute('data-form');
                if (targetForm && targetForm !== this.currentForm) {
                    this.showForm(targetForm, true);
                }
            });
        });

        console.log('All events bound successfully');
    }

    showForm(formName, animate = true) {
        if (this.isAnimating) return;
        
        console.log('Switching to form:', formName);
        this.isAnimating = true;

        const previousForm = this.currentForm;
        this.currentForm = formName;

        const previousFormElement = document.getElementById(`${previousForm}Form`);
        const targetFormElement = document.getElementById(`${formName}Form`);

        if (!targetFormElement) {
            console.error('Target form not found:', formName);
            this.isAnimating = false;
            return;
        }

        if (animate) {
            // Hide previous form with slide out animation
            if (previousFormElement) {
                previousFormElement.style.transform = 'translateX(-100%)';
                previousFormElement.style.opacity = '0';
                
                setTimeout(() => {
                    previousFormElement.classList.remove('active');
                    previousFormElement.style.visibility = 'hidden';
                    
                    // Show new form with slide in animation
                    targetFormElement.style.visibility = 'visible';
                    targetFormElement.style.transform = 'translateX(100%)';
                    targetFormElement.classList.add('active');
                    
                    setTimeout(() => {
                        targetFormElement.style.transform = 'translateX(0)';
                        targetFormElement.style.opacity = '1';
                        this.updateSliderDots();
                        this.isAnimating = false;
                    }, 50);
                }, 300);
            }
        } else {
            // No animation - initial setup
            this.hideAllForms();
            targetFormElement.classList.add('active');
            targetFormElement.style.visibility = 'visible';
            targetFormElement.style.transform = 'translateX(0)';
            targetFormElement.style.opacity = '1';
            this.updateSliderDots();
            this.isAnimating = false;
        }

        this.clearMessages();
        this.clearAllErrors();
    }

    hideAllForms() {
        document.querySelectorAll('.form').forEach(form => {
            form.classList.remove('active');
            form.style.visibility = 'hidden';
            form.style.opacity = '0';
            form.style.transform = 'translateX(100%)';
        });
    }

    updateSliderDots() {
        document.querySelectorAll('.slider-dot').forEach(dot => {
            dot.classList.remove('active');
            if (dot.getAttribute('data-form') === this.currentForm) {
                dot.classList.add('active');
            }
        });
    }

    togglePassword(button) {
        const input = button.closest('.input-group').querySelector('input');
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        button.textContent = type === 'password' ? 'Show' : 'Hide';
    }

    async handleLogin() {
        console.log('Login attempt started');
        
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('.btn');
        const formData = new FormData(form);
        
        const data = {
            username: formData.get('username').trim(),
            password: formData.get('password'),
            remember_me: formData.get('remember_me') === 'on',
            csrf_token: this.csrfToken
        };

        console.log('Login data:', data);

        this.clearMessages();
        this.clearAllErrors();

        // Validate required fields
        if (!data.username || !data.password) {
            this.showError('Username and password are required');
            return;
        }

        if (!this.validateForm('login')) {
            return;
        }

        try {
            this.setButtonLoading(submitBtn, true);
            
            console.log('Sending login request to: ../api/auth/login.php');
            
            const response = await fetch('../api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            console.log('Login response status:', response.status);
            console.log('Login response ok:', response.ok);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Login API response:', result);

            if (result.success) {
                this.showSuccess('Login successful! Redirecting to dashboard...');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else {
                this.showError(result.message || 'Login failed. Please try again.');
                this.setButtonLoading(submitBtn, false);
            }
        } catch (error) {
            console.error('Login error details:', error);
            console.error('Error name:', error.name);
            console.error('Error message:', error.message);
            
            let errorMessage = 'Network error. Please try again.';
            if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Cannot connect to server. Please check if the server is running.';
            } else if (error.message.includes('HTTP error')) {
                errorMessage = 'Server error. Please try again later.';
            }
            
            this.showError(errorMessage);
            this.setButtonLoading(submitBtn, false);
        }
    }

    async handleSignup() {
        console.log('Signup attempt started');
        
        const form = document.getElementById('signupForm');
        const submitBtn = form.querySelector('.btn');
        const formData = new FormData(form);
        
        const data = {
            username: formData.get('username').trim(),
            email: formData.get('email').trim(),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password'),
            csrf_token: this.csrfToken
        };

        console.log('Signup data:', data);

        this.clearMessages();
        this.clearAllErrors();

        // Validate required fields
        if (!data.username) {
            this.showError('Username is required');
            return;
        }
        if (!data.email) {
            this.showError('Email is required');
            return;
        }
        if (!data.password) {
            this.showError('Password is required');
            return;
        }
        if (!data.confirm_password) {
            this.showError('Please confirm your password');
            return;
        }

        if (data.password !== data.confirm_password) {
            this.showError('Passwords do not match');
            return;
        }

        if (!this.validateForm('signup')) {
            return;
        }

        try {
            this.setButtonLoading(submitBtn, true);
            
            console.log('Sending signup request to: ../api/auth/signup.php');
            
            const response = await fetch('../api/auth/signup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            console.log('Signup response status:', response.status);
            console.log('Signup response ok:', response.ok);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Signup API response:', result);

            if (result.success) {
                this.showSuccess('🎉 Registration successful! Redirecting to login...');
                
                // Clear the signup form
                form.reset();
                
                // Wait 2 seconds then switch to login form
                setTimeout(() => {
                    this.showForm('login', true);
                    this.setButtonLoading(submitBtn, false);
                    
                    // Show success message on login form
                    setTimeout(() => {
                        this.showSuccess('✅ Registration successful! Please login with your credentials.');
                    }, 500);
                    
                }, 2000);
                
            } else {
                this.showError(result.message || 'Registration failed. Please try again.');
                this.setButtonLoading(submitBtn, false);
            }
        } catch (error) {
            console.error('Signup error details:', error);
            console.error('Error name:', error.name);
            console.error('Error message:', error.message);
            
            let errorMessage = 'Network error. Please try again.';
            if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Cannot connect to server. Please check: 1) XAMPP is running, 2) Apache is started, 3) File paths are correct';
            } else if (error.message.includes('HTTP error')) {
                errorMessage = 'Server error (' + error.message + '). Check PHP error logs.';
            }
            
            this.showError(errorMessage);
            this.setButtonLoading(submitBtn, false);
        }
    }

    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.setAttribute('data-original-text', originalText);
            button.innerHTML = '<span class="loading-spinner"></span> Processing...';
        } else {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
        }
    }

    validateForm(formType) {
        let isValid = true;
        const form = document.getElementById(`${formType}Form`);

        if (!form) {
            console.error('Form not found:', `${formType}Form`);
            return false;
        }

        // Username validation
        if (formType === 'signup' || formType === 'login') {
            const username = form.querySelector('input[name="username"]');
            if (username && username.value.trim() && !this.validateUsername(username.value.trim())) {
                this.markFieldError(username, 'Username must be 3-50 characters (letters, numbers, underscores)');
                isValid = false;
            }
        }

        // Email validation (signup only)
        if (formType === 'signup') {
            const email = form.querySelector('input[name="email"]');
            if (email && email.value.trim() && !this.validateEmail(email.value.trim())) {
                this.markFieldError(email, 'Please enter a valid email address');
                isValid = false;
            }
        }

        // Password validation
        const password = form.querySelector('input[name="password"]');
        if (password && password.value && !this.validatePassword(password.value)) {
            this.markFieldError(password, 'Password must be at least 8 characters long');
            isValid = false;
        }

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        switch (field.name) {
            case 'username':
                if (value && !this.validateUsername(value)) {
                    isValid = false;
                    message = 'Username must be 3-50 characters (letters, numbers, underscores)';
                }
                break;
            case 'email':
                if (value && !this.validateEmail(value)) {
                    isValid = false;
                    message = 'Please enter a valid email address';
                }
                break;
            case 'password':
                if (value && !this.validatePassword(value)) {
                    isValid = false;
                    message = 'Password must be at least 8 characters long';
                }
                break;
            case 'confirm_password':
                const password = field.closest('form').querySelector('input[name="password"]');
                if (password && value !== password.value) {
                    isValid = false;
                    message = 'Passwords do not match';
                }
                break;
        }

        if (!isValid && value) {
            this.markFieldError(field, message);
        } else if (isValid && value) {
            this.markFieldSuccess(field);
        } else {
            this.clearFieldError(field);
        }
    }

    validateUsername(username) {
        return /^[a-zA-Z0-9_]{3,50}$/.test(username);
    }

    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    validatePassword(password) {
        return password.length >= 8;
    }

    markFieldError(field, message) {
        field.classList.add('input-error');
        
        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    markFieldSuccess(field) {
        field.classList.add('input-success');
        field.classList.remove('input-error');
        this.clearFieldError(field);
    }

    clearFieldError(field) {
        field.classList.remove('input-error');
        field.classList.remove('input-success');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    clearAllErrors() {
        document.querySelectorAll('.input-error, .input-success').forEach(field => {
            field.classList.remove('input-error', 'input-success');
        });
        document.querySelectorAll('.field-error').forEach(error => error.remove());
    }

    showError(message) {
        const currentForm = document.getElementById(`${this.currentForm}Form`);
        const errorElement = currentForm.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            currentForm.querySelector('.success-message').style.display = 'none';
        }
    }

    showSuccess(message) {
        const currentForm = document.getElementById(`${this.currentForm}Form`);
        const successElement = currentForm.querySelector('.success-message');
        if (successElement) {
            successElement.textContent = message;
            successElement.style.display = 'block';
            currentForm.querySelector('.error-message').style.display = 'none';
        }
    }

    clearMessages() {
        document.querySelectorAll('.form').forEach(form => {
            const errorElement = form.querySelector('.error-message');
            const successElement = form.querySelector('.success-message');
            if (errorElement) errorElement.style.display = 'none';
            if (successElement) successElement.style.display = 'none';
        });
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing AuthApp...');
    new AuthApp();
});