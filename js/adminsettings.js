document.addEventListener("DOMContentLoaded", () => {


    document.querySelector(".back-button").addEventListener("click", () => {
        history.back();
      });
    
    // GENERAL INFO LOGIC
    const generalUpdateBtn = document.getElementById("general-update-btn");
    const generalEditForm = document.getElementById("general-edit-form");
    const generalCancelBtn = document.getElementById("general-cancel-btn");
    const generalSaveBtn = document.getElementById("general-save-btn");
  
    generalUpdateBtn.addEventListener("click", () => {
      generalEditForm.style.display = "block";
      generalUpdateBtn.style.display = "none";
    });
  
    generalCancelBtn.addEventListener("click", () => {
      generalEditForm.style.display = "none";
      generalUpdateBtn.style.display = "inline-block";
    });
  
    generalSaveBtn.addEventListener("click", async () => {
      const username = document.getElementById("name-input").value.trim();
  
      if (!username) return alert("Username is required");
  
      const response = await fetch("api/updateadmingeneral.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ username })
      });
  
      const result = await response.json();
  
      if (result.success) {
        document.getElementById("name-display").textContent = username;
        generalEditForm.style.display = "none";
        generalUpdateBtn.style.display = "inline-block";
        alert("General info updated!");
      } else {
        alert(result.error || "Something went wrong");
      }
    });
  
    // SECURITY INFO LOGIC
    const securityUpdateBtn = document.getElementById("security-update-btn");
    const securityEditForm = document.getElementById("security-edit-form");
    const securityCancelBtn = document.getElementById("security-cancel-btn");
    const securitySaveBtn = document.getElementById("security-save-btn");

    const passwordInput = document.getElementById('password-input');
    const confirmPasswordInput = document.getElementById('confirm-password-input');
    const togglePassword = document.getElementById('toggle-password');
    const toggleConfirmPassword = document.getElementById('toggle-confirm-password');

    // Toggle both at once
    const toggleBoth = () => {
    const isPwd = passwordInput.type === 'password';
    const newType = isPwd ? 'text' : 'password';

    passwordInput.type = newType;
    confirmPasswordInput.type = newType;

    togglePassword.textContent = isPwd ? '🙈' : '👁️';
    toggleConfirmPassword.textContent = isPwd ? '🙈' : '👁️';
    };

    togglePassword.addEventListener('click', toggleBoth);
    toggleConfirmPassword.addEventListener('click', toggleBoth);

    
    securityUpdateBtn.addEventListener("click", () => {
      securityEditForm.style.display = "block";
      securityUpdateBtn.style.display = "none";
    });
  
    securityCancelBtn.addEventListener("click", () => {
      securityEditForm.style.display = "none";
      securityUpdateBtn.style.display = "inline-block";
    });
    
  
    securitySaveBtn.addEventListener("click", () => {
        const email = document.getElementById("email-input").value.trim();
        const password = document.getElementById("password-input").value;
        const confirmPassword = document.getElementById("confirm-password-input").value;
      
        if (!email) return alert("Email is required");
        if (password && password !== confirmPassword) return alert("Passwords do not match");
      
        // Show the modal
        document.getElementById("confirm-modal").style.display = "flex";
      
        // Handle confirm click
        document.getElementById("modal-confirm").onclick = async () => {
          const response = await fetch("api/updateadminsec.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body: JSON.stringify({ email, password })
          });
      
          const result = await response.json();
      
          if (result.success) {
            document.getElementById("email-display").textContent = email;
            securityEditForm.style.display = "none";
            securityUpdateBtn.style.display = "inline-block";

            // CLEAR password fields here:
            passwordInput.value = "";
            confirmPasswordInput.value = "";

            
            alert("Security info updated!");
          } else {
            alert(result.error || "Something went wrong");
          }
      
          document.getElementById("confirm-modal").style.display = "none";
        };
      
        // Cancel just hides modal
        document.getElementById("modal-cancel").onclick = () => {
          document.getElementById("confirm-modal").style.display = "none";
        };
      });
      
  });
  