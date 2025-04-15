document.addEventListener('DOMContentLoaded', function () {
  // Sidebar Toggle Functionality
  const toggleBtn = document.getElementById('toggleBtn');
  const sidebar = document.getElementById('sidebar');
  
  if (toggleBtn && sidebar) {
    // Create transition overlay if needed
    if (!document.getElementById('transitionOverlay')) {
      document.body.insertAdjacentHTML('afterbegin', '<div id="transitionOverlay"></div>');
    }

    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('sidebar-hidden');
    });
  }

  // Chart Initialization
  const initializeChart = () => {
    const chartCanvas = document.getElementById('taskGraph');
    if (chartCanvas && typeof Chart !== 'undefined') {
      const ctx = chartCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: window.chartData || {
          labels: ["To-Do", "In Progress", "Completed"],
          datasets: [{
            data: [0, 0, 0],
            backgroundColor: ["#EA2E2E", "#5BA4E5", "#54D376"]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#000000',
                boxWidth: 12,
                padding: 10
              }
            }
          }
        }
      });
    }
  };
  initializeChart();


  const fetchAndUpdateTasks = async () => {
    try {
      // 1. Use absolute path with cache-busting
      const response = await fetch(`/TaskManagementSystem/api/task_list.php?${Date.now()}`);
      
      // 2. Enhanced error handling
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Server responded with: ${errorText}`);
      }
  
      // 3. Verify response before DOM update
      const responseText = await response.text();
      console.log('Received task data:', responseText); // Debugging
  
      // 4. Safely update DOM
      const taskBody = document.querySelector('.task-body');
      if (!taskBody) {
        throw new Error('Task container not found');
      }
  
      // 5. Preserve existing tasks during update
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = responseText;
      
      // 6. Animate update for better UX
      taskBody.style.opacity = '0';
      setTimeout(() => {
        taskBody.innerHTML = tempDiv.innerHTML;
        taskBody.style.opacity = '1';
        
        // 7. Reinitialize any event listeners here
        initTaskInteractions(); // If you have interactive elements
      }, 300);
  
    } catch (error) {
      console.error('Task update failed:', error);
      alert('Failed to refresh tasks. Please check your connection and try again.');
    }
  };

  // OTP Handling
  const otpInputs = document.querySelectorAll('.otp-box');
  if (otpInputs.length > 0) {
    otpInputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        const value = e.target.value;
        if (!/^\d$/.test(value)) {
          e.target.value = '';
          return;
        }
        if (index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && index > 0 && !e.target.value) {
          otpInputs[index - 1].focus();
        }
      });
    });

    // OTP Form Submission
    document.querySelectorAll('form.otp-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        const otpValues = Array.from(form.querySelectorAll('.otp-box'))
          .map(input => input.value)
          .join('');
        
        if (otpValues.length !== 4) {
          e.preventDefault();
          alert("Please fill all OTP fields!");
          return;
        }

        if (!confirm("Checking OTP...")) {
          e.preventDefault();
          return;
        }

        document.getElementById('otp').value = otpValues;
      });
    });
  }

  // Password Validation
  const passwordForms = document.querySelectorAll('form[data-password-validate="true"]');
  passwordForms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const password = form.querySelector('input[name="password"]');
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,20}$/;
      
      if (password && !regex.test(password.value)) {
        e.preventDefault();
        alert("Password must contain:\n- 8-20 characters\n- 1 uppercase\n- 1 lowercase\n- 1 number");
      }
    });
  });

  // Task Form Handling (MODIFIED)
  const taskForm = document.getElementById('taskForm');
  if (taskForm) {
    taskForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(taskForm);

      // Modify the try-catch block:
      try {
        const response = await fetch('/TaskManagementSystem/api/create_task.php', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
          await fetchAndUpdateTasks();
          // Use vanilla JS to hide modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('createTaskModal'));
          if (modal) modal.hide();
          
          // Reset form after successful submission
          taskForm.reset();
          document.getElementById('collaboratorContainer').innerHTML = `
            <div class="input-group mb-2 collaborator-field">
              <input type="email" name="collaborators[]" 
                    class="form-control" 
                    placeholder="collaborator@example.com"
                    pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
              <button type="button" class="btn btn-outline-success add-collaborator" 
                      onclick="addCollaboratorField(this)">
                <i class="fas fa-plus"></i>
              </button>
            </div>`;
        } else {
          throw new Error(result.message || 'Unknown error occurred');
        }
      } catch (error) {
        console.error('Submission error:', error);
        alert(`Error: ${error.message}`);
      }
    });
  }
});

// Collaborator Field Management (global function)
window.addCollaboratorField = function() {
  const container = document.getElementById('collaboratorContainer');
  if (container) {
    const newField = `
    <div class="input-group mb-2">
      <input type="email" name="collaborators[]" class="form-control" required>
      <button type="button" class="btn btn-sm btn-outline-danger" 
              onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
    container.insertAdjacentHTML('beforeend', newField);
  }
};