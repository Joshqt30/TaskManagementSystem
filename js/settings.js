// Navigation
document.querySelector('.back-button').addEventListener('click', () => {
    window.location.href = 'main.php';
  });
  
  // General Info Elements
  const generalUpdateBtn       = document.getElementById('general-update-btn');
  const generalEditForm        = document.getElementById('general-edit-form');
  const generalCancelBtn       = document.getElementById('general-cancel-btn');
  const generalSaveBtn         = document.getElementById('general-save-btn');
  const nameDisplay            = document.getElementById('name-display');
  const nameInput              = document.getElementById('name-input');
  const generalSuccessMessage  = document.getElementById('general-success');
  
  // Security Elements
  const securityUpdateBtn      = document.getElementById('security-update-btn');
  const securityEditForm       = document.getElementById('security-edit-form');
  const securityCancelBtn      = document.getElementById('security-cancel-btn');
  const securitySaveBtn        = document.getElementById('security-save-btn');
  const emailDisplay           = document.getElementById('email-display');
  const passwordDisplay        = document.getElementById('password-display');
  const emailInput             = document.getElementById('email-input');
  const passwordInput          = document.getElementById('password-input');
  const confirmPasswordInput   = document.getElementById('confirm-password-input');
  
  const togglePassword         = document.getElementById('toggle-password');
  const toggleConfirmPassword  = document.getElementById('toggle-confirm-password');
  
  const securitySuccessMessage = document.getElementById('security-success');
  
  
  // — Live “passwords match” feedback —
  confirmPasswordInput.addEventListener('input', () => {
    if (!confirmPasswordInput.value) {
      confirmPasswordInput.style.borderColor = '#ccc';
    } else if (confirmPasswordInput.value !== passwordInput.value) {
      confirmPasswordInput.style.borderColor = 'red';
    } else {
      confirmPasswordInput.style.borderColor = '#ccc';
    }
  });
  
  
  // — General Info Handlers —
  generalUpdateBtn.addEventListener('click', () => {
    generalUpdateBtn.style.display        = 'none';
    generalEditForm.style.display         = 'block';
    nameInput.value                       = nameDisplay.textContent;
    nameInput.focus();
  });
  
  generalCancelBtn.addEventListener('click', () => {
    generalEditForm.style.display         = 'none';
    generalUpdateBtn.style.display        = 'inline-block';
  });
  
  generalSaveBtn.addEventListener('click', async () => {
    const username = nameInput.value.trim();
  
    try {
      const res = await fetch('api/update_general.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username })
      });
      const result = await res.json();
  
      if (result.success) {
        nameDisplay.textContent           = username;
        generalEditForm.style.display     = 'none';
        generalUpdateBtn.style.display    = 'inline-block';
        generalSuccessMessage.style.display = 'block';
        setTimeout(() => generalSuccessMessage.style.display = 'none', 2000);
      } else {
        alert(result.error || 'Failed to update general info');
      }
    } catch (err) {
      console.error(err);
      alert("Something went wrong while updating general info.");
    }
  });
  
  
  // — Password‐field Toggles —
  // Both icons simply toggle the two password fields together
  const toggleBoth = () => {
    const isPwd = passwordInput.type === 'password';
    const newType = isPwd ? 'text' : 'password';
  
    passwordInput.type               = newType;
    confirmPasswordInput.type        = newType;
    togglePassword.textContent       = isPwd ? '🙈' : '👁️';
    toggleConfirmPassword.textContent = isPwd ? '🙈' : '👁️';
  };
  
  togglePassword.addEventListener('click', toggleBoth);
  toggleConfirmPassword.addEventListener('click', toggleBoth);
  
  
  // — Security Info Handlers —
  securityUpdateBtn.addEventListener('click', () => {
    securityUpdateBtn.style.display   = 'none';
    securityEditForm.style.display    = 'block';
    emailInput.value                  = emailDisplay.textContent;
    passwordInput.value               = '';
    confirmPasswordInput.value        = '';
    confirmPasswordInput.style.borderColor = '#ccc';
    emailInput.focus();
  });
  
  securityCancelBtn.addEventListener('click', () => {
    securityEditForm.style.display    = 'none';
    securityUpdateBtn.style.display   = 'inline-block';
  });
  
  securitySaveBtn.addEventListener('click', async () => {
    if (passwordInput.value && passwordInput.value !== confirmPasswordInput.value) {
      alert("Passwords do not match");
      return;
    }
  
    const newEmail = emailInput.value.trim();
    const oldEmail = emailDisplay.textContent.trim();
  
    if (newEmail !== oldEmail) {
      if (!confirm("You changed your email. Are you sure?")) return;
    }
  
    try {
      const res = await fetch('api/update_security.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: newEmail, password: passwordInput.value || null })
      });
      const result = await res.json();
  
      if (result.success) {
        emailDisplay.textContent        = newEmail;
        if (passwordInput.value) {
          passwordDisplay.textContent   = '••••••••••';
        }
        securityEditForm.style.display  = 'none';
        securityUpdateBtn.style.display = 'inline-block';
        securitySuccessMessage.style.display = 'block';
        setTimeout(() => securitySuccessMessage.style.display = 'none', 2000);
      } else {
        alert(result.error || 'Failed to update security info');
      }
    } catch (err) {
      console.error(err);
      alert("Something went wrong while updating security info.");
    }
  });
  