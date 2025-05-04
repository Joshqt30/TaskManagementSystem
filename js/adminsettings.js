// Navigation
document.querySelector(".back-button").addEventListener("click", () => {
  window.location.href = 'admin.php';
});

// Add these variables at the top with other element declarations
const confirmationModal = document.getElementById('confirmationModal');
const modalConfirm = document.getElementById('modalConfirm');
const modalCancel = document.getElementById('modalCancel');
const modalMessage = document.getElementById('modalMessage');

// Profile Picture Elements
const profilePicForm = document.querySelector('.profile-picture-section form');
const profilePicInput = document.getElementById('profile-pic-input');
const profilePreview = document.getElementById('profile-preview');

// General Info Elements
const generalUpdateBtn = document.getElementById('general-update-btn');
const generalEditForm = document.getElementById('general-edit-form');
const generalCancelBtn = document.getElementById('general-cancel-btn');
const generalSaveBtn = document.getElementById('general-save-btn');
const nameDisplay = document.getElementById('name-display');
const nameInput = document.getElementById('name-input');
const generalSuccessMessage = document.getElementById('general-success');

// Security Elements
const securityUpdateBtn = document.getElementById('security-update-btn');
const securityEditForm = document.getElementById('security-edit-form');
const securityCancelBtn = document.getElementById('security-cancel-btn');
const securitySaveBtn = document.getElementById('security-save-btn');
const emailDisplay = document.getElementById('email-display');
const passwordDisplay = document.getElementById('password-display');
const emailInput = document.getElementById('email-input');
const passwordInput = document.getElementById('password-input');
const confirmPasswordInput = document.getElementById('confirm-password-input');
const togglePassword = document.getElementById('toggle-password');
const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
const securitySuccessMessage = document.getElementById('security-success');

const input = document.getElementById('profile-pic-input');
input.addEventListener('change', async function () {
  const file = this.files[0];
  if (!file) return;

  if (!confirm("Upload this as your profile picture?")) {
    this.value = '';
    return;
  }

  // 1) Show preview immediately
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('profile-preview').innerHTML = `
      <button class="remove-profile-btn" id="removeProfileBtn">✕</button>
      <img src="${e.target.result}" class="profile-preview-img" />
    `;
  };
  reader.readAsDataURL(file);

  // 2) Upload in the background
  const form = new FormData();
  form.append('profile_pic', file);

  try {
    const res = await fetch('settings.php', { method: 'POST', body: form });
    window.location.reload(); // Add this line here
    if (!res.ok) throw new Error('Upload failed');
  } catch (err) {
    alert('Upload error: ' + err.message);
    console.error(err);
  }

  // 3) Clean URL and reset input
  window.history.replaceState({}, '', window.location.pathname);
  this.value = '';
});




// Modified remove profile picture handler
document.body.addEventListener('click', (e) => {
  if (e.target.closest('#removeProfileBtn')) {
    e.preventDefault();
    
    showModal("Are you sure you want to remove your profile picture?", async () => {
      try {
        const res = await fetch('api/remove_profile_pic.php', {
          method: 'POST',
        });
        
        const result = await res.json(); // Parse response first
        
        if (!res.ok) {
          throw new Error(result.details || result.error || 'Server error');
        }

        if (result.success) {
          document.getElementById('profile-pic-input').value = '';
          document.querySelector('.profile-preview').innerHTML = `
            <i class="fa-solid fa-user-circle default-profile"></i>
          `;
          // Optional: Soft refresh instead of hard reload
          window.location.reload();
        }
        
      } catch (err) {
        console.error('Remove error:', err);
        alert(`Error: ${err.message}`);
      }
    });
  }
});

// — Password Match Validation —
confirmPasswordInput.addEventListener('input', () => {
  confirmPasswordInput.style.borderColor = 
      confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value 
      ? 'red' 
      : '#ccc';
});

// — General Info Handlers —
generalUpdateBtn.addEventListener('click', () => {
  generalUpdateBtn.style.display = 'none';
  generalEditForm.style.display = 'block';
  nameInput.value = nameDisplay.textContent;
  nameInput.focus();
});

generalCancelBtn.addEventListener('click', () => {
  generalEditForm.style.display = 'none';
  generalUpdateBtn.style.display = 'inline-block';
});

// — Password Visibility Toggle —
const togglePasswordVisibility = () => {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  confirmPasswordInput.type = isPassword ? 'text' : 'password';
  togglePassword.textContent = isPassword ? '🙈' : '👁️';
  toggleConfirmPassword.textContent = isPassword ? '🙈' : '👁️';
};

togglePassword.addEventListener('click', togglePasswordVisibility);
toggleConfirmPassword.addEventListener('click', togglePasswordVisibility);

// — Security Info Handlers —
securityUpdateBtn.addEventListener('click', () => {
  securityUpdateBtn.style.display = 'none';
  securityEditForm.style.display = 'block';
  emailInput.value = emailDisplay.textContent;
  passwordInput.value = '';
  confirmPasswordInput.value = '';
  emailInput.focus();
});

securityCancelBtn.addEventListener('click', () => {
  securityEditForm.style.display = 'none';
  securityUpdateBtn.style.display = 'inline-block';
});


// Add these functions at the bottom of your existing code
function showModal(message, callback) {
  modalMessage.textContent = message;
  confirmationModal.style.display = 'block';
  
  const handleResponse = (confirmed) => {
      confirmationModal.style.display = 'none';
      modalConfirm.removeEventListener('click', confirmHandler);
      modalCancel.removeEventListener('click', cancelHandler);
      if (confirmed && typeof callback === 'function') {
          callback();
      }
  };

  const confirmHandler = () => handleResponse(true);
  const cancelHandler = () => handleResponse(false);

  modalConfirm.addEventListener('click', confirmHandler);
  modalCancel.addEventListener('click', cancelHandler);
}

// Modify the generalSaveBtn click handler
generalSaveBtn.addEventListener('click', () => {
  const username = nameInput.value.trim();
  if (username === nameDisplay.textContent.trim()) {
      alert("No changes detected");
      return;
  }
  
  showModal("Are you sure you want to update your profile information?", async () => {
      try {
          const res = await fetch('api/update_general.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ username })
          });
          
          const result = await res.json();

          if (result.success) {
              nameDisplay.textContent = username;
              generalEditForm.style.display = 'none';
              generalUpdateBtn.style.display = 'inline-block';
              generalSuccessMessage.style.display = 'block';
              setTimeout(() => generalSuccessMessage.style.display = 'none', 2000);
          } else {
              alert(result.error || 'Failed to update general info');
          }
      } catch (err) {
          console.error('Update error:', err);
          alert("Update failed. Please try again.");
      }
  });
});

// — Security Info Handlers —
securitySaveBtn.addEventListener('click', () => {
  const newEmail = emailInput.value.trim();
  const newPassword = passwordInput.value.trim();
  const currentEmail = emailDisplay.textContent.trim();
  const emailChanged = newEmail !== currentEmail;
  const passwordChanged = newPassword.length > 0;

  // No changes detected
  if (!emailChanged && !passwordChanged) {
    alert("No changes detected");
    return;
  }

  // Password mismatch check
  if (newPassword && newPassword !== confirmPasswordInput.value.trim()) {
    alert("Passwords do not match");
    return;
  }

  // Show appropriate modals
  const message = emailChanged 
    ? "You're changing your email. Are you sure?" 
    : "Are you sure you want to update security settings?";
  
  showModal(message, () => proceedWithSecurityUpdate());
});

async function proceedWithSecurityUpdate() {
  const newEmail = emailInput.value.trim();
  const newPassword = passwordInput.value.trim();

  // Check again in case of race conditions
  const emailChanged = newEmail !== emailDisplay.textContent.trim();
  const passwordChanged = newPassword.length > 0;
  if (!emailChanged && !passwordChanged) {
    alert("No changes detected");
    return;
  }

  try {
    const res = await fetch('api/update_security.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: newEmail,
        password: newPassword || null
      })
    });

    const result = await res.json();

    if (result.success) {
      // Update UI only if server confirms success
      emailDisplay.textContent = newEmail;
      if (passwordChanged) {
        passwordDisplay.textContent = '••••••••••';
      }
      securityEditForm.style.display = 'none';
      securityUpdateBtn.style.display = 'inline-block';
      securitySuccessMessage.style.display = 'block';
      setTimeout(() => securitySuccessMessage.style.display = 'none', 2000);
    } else {
      alert(result.error || 'Security update failed');
    }
  } catch (err) {
    console.error('Security update error:', err);
    alert("Update failed. Please try again.");
  }
}