document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('[data-form="login"]');
    if (loginForm) {
        const usernameInput = loginForm.querySelector('input[name="username"]');
        const passwordInput = loginForm.querySelector('input[name="password"]');
        const submitBtn = loginForm.querySelector('button[type="submit"]');

        const toggle = loginForm.querySelector('[data-toggle="password"]');
        if (toggle && passwordInput) {
            toggle.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggle.textContent = isPassword ? 'Hide' : 'Show';
            });
        }

        function updateButtonState() {
            if (!submitBtn) return;
            const filled = usernameInput.value.trim() !== '' && passwordInput.value.trim() !== '';
            submitBtn.disabled = !filled;
        }

        if (usernameInput && passwordInput) {
            usernameInput.addEventListener('input', updateButtonState);
            passwordInput.addEventListener('input', updateButtonState);
            updateButtonState();
        }
    }
});