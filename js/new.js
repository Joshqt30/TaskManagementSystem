// === TASK INTERACTIONS FUNCTIONS ===

// Initialize Task Interactions
function initTaskInteractions() {
  // Click handler for task details
    document.querySelectorAll('.task-item').forEach(task => {
    task.addEventListener('click', function(e) {
      const taskId = this.dataset.taskId;
      fetchTaskDetails(taskId);
    });
  });


// Edit Task Handler - Populate All Fields
document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', async (e) => {
    const taskId = e.target.dataset.id;
    
    try {
      const response = await fetch(`/TaskManagementSystem/api/get_task.php?id=${taskId}`);
      const task = await response.json();

        // Add these 2 lines - START
        const statusDropdown = document.getElementById('editStatus');
        statusDropdown.dataset.originalValue = task.status; // Store original value
        // Add these 2 lines - END

      // Add this block - START
      // Show/hide collaborator section based on ownership
      const collaboratorSection = document.getElementById('collaboratorSection');
      if (task.is_owner) {
        collaboratorSection.style.display = 'block';
      } else {
        collaboratorSection.style.display = 'none';
      }
      // Add this block - END

      // Populate basic fields
      document.getElementById('editTaskId').value = task.id;
      document.getElementById('editTitle').value = task.title;
      document.getElementById('editDescription').value = task.description;
      document.getElementById('editDueDate').value = task.due_date.split(' ')[0]; // Remove time
      document.getElementById('editStatus').value = task.status;

      // Populate collaborators
      const container = document.getElementById('editCollaboratorContainer');
      container.innerHTML = ''; 
      
      if (task.is_owner) {
        // *** only owners get those input fields! ***
        task.collaborators.forEach(collab => {
          addCollaboratorField('edit', collab.email);
        });
      }
      // Show modal
      new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    } catch (error) {
      alert('Error loading task');
    }
  });
});

// Delete Handler
// Replace your delete handler with:
document.querySelectorAll('.delete-btn').forEach(button => {
  button.addEventListener('click', async (e) => {
    if (!confirm('Are you sure you want to delete this task?')) 
      return;

    const taskId = button.dataset.id;
    try {
      const res = await fetch('/TaskManagementSystem/api/delete_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ taskId })
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || 'Delete failed');
      // On success, remove the row from the table:
      button.closest('tr').remove();
    } catch (err) {
      alert('Error deleting task: ' + err.message);
    }
  });
});


}



async function fetchTaskDetails(taskId) {
  try {
    const response = await fetch(`/TaskManagementSystem/api/get_task.php?id=${taskId}`);
    const task = await response.json();

    // Populate basic task details
    document.getElementById('detail-title').textContent = task.title;
    document.getElementById('detail-description').textContent = task.description;
    document.getElementById('detail-status').textContent = task.status.replace('_', ' ');
    document.getElementById('detail-due-date').textContent = task.due_date;

    // Populate collaborators
    const collaboratorsContainer = document.getElementById('detail-collaborators');
    collaboratorsContainer.innerHTML = '';
    
    if (task.collaborators && task.collaborators.length > 0) {
      task.collaborators.forEach(collaborator => {
        const collaboratorElement = document.createElement('div');
        collaboratorElement.className = 'collaborator-item d-flex align-items-center mb-2';
        collaboratorElement.innerHTML = `
          <i class="fas fa-user me-2"></i>
          <span class="email">${collaborator.email}</span>
          <span class="badge ${collaborator.status === 'accepted' ? 'bg-success' : 'bg-warning'} ms-2">
            ${collaborator.status}
          </span>
        `;
        collaboratorsContainer.appendChild(collaboratorElement);
      });
    } else {
      collaboratorsContainer.innerHTML = '<div class="text-muted">No collaborators</div>';
    }

    new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
    
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to load task details');
  }
}



// === GLOBAL FUNCTION: FETCH AND UPDATE TASKS ===
  const fetchAndUpdateTasks = async () => {
    try {
        const response = await fetch(`/TaskManagementSystem/api/task_list.php?${Date.now()}`);
        if (!response.ok) throw new Error('Failed to fetch tasks');
        
        // Palitan ang DOM element na "task-body" sa "task-lists"
        const tasksHTML = await response.text();
        document.querySelector('.task-lists').innerHTML = tasksHTML; // Baguhin dito
        
        initTaskInteractions();
        
    } catch (error) {
        console.error('Task update error:', error);
        alert('Failed to refresh tasks');
    }
  };


// === OTHER INTERACTIONS AND INITIALIZATIONS ===
  document.addEventListener('DOMContentLoaded', function () {
      // Sidebar Toggle Functionality
      const toggleBtn = document.getElementById('toggleBtn');
      const sidebar = document.getElementById('sidebar');
      if (toggleBtn && sidebar) {
        if (!document.getElementById('transitionOverlay')) {
          document.body.insertAdjacentHTML('afterbegin', '<div id="transitionOverlay"></div>');
        }
        toggleBtn.addEventListener('click', function () {
          sidebar.classList.toggle('sidebar-hidden');

          // Initialize task interactions on page load
          initTaskInteractions();

        });
      }


      document.getElementById('editTaskForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const dueDateInput = document.getElementById('editDueDate');
        const dueDate = new Date(dueDateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (dueDate < today) {
          const proceed = confirm('⚠️ The deadline is in the past.\nAre you sure you want to update this task?');
          if (!proceed) return;
        }
        
        const formData = new FormData(e.target);
        const collaboratorInputs = [...document.querySelectorAll('#editCollaboratorContainer input[name="collaborators[]"]')];
        const collaborators = collaboratorInputs.map(input => input.value.trim());
      
        const validCollaborators = [];
        const invalidCollaborators = [];
      
        // Clear previous feedback
        collaboratorInputs.forEach(input => {
          input.classList.remove('is-invalid');
          const existingFeedback = input.nextElementSibling;
          if (existingFeedback && existingFeedback.classList.contains('invalid-feedback')) {
            existingFeedback.remove();
          }
        });
      
        // Validate collaborators
        for (let i = 0; i < collaborators.length; i++) {
          const email = collaborators[i];
          const input = collaboratorInputs[i];
      
          if (!email) continue;
      
          const res = await fetch(`/TaskManagementSystem/api/check_user.php?email=${encodeURIComponent(email)}`);
          const data = await res.json();
      
          if (data.exists) {
            validCollaborators.push(email);
          } else {
            invalidCollaborators.push(email);
            input.classList.add('is-invalid');
      
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'User not found';
            input.parentNode.appendChild(feedback);
          }
        }
      
        if (invalidCollaborators.length > 0) {
          alert('Please fix invalid emails before submitting.');
          return;
        }
      
        formData.append('validCollaborators', JSON.stringify(validCollaborators));
      
        const statusDropdown = document.getElementById('editStatus');
        const originalStatus = statusDropdown.dataset.originalValue;
      
        try {
          const response = await fetch('/TaskManagementSystem/api/update_task.php', {
            method: 'POST',
            body: formData
          });
      
          if (!response.ok) {
            statusDropdown.value = originalStatus;
            const errorData = await response.json();
            throw new Error(errorData.message || 'Update failed');
          }
      
          location.reload();
        } catch (error) {
          alert('Error updating task: ' + error.message);
          console.error('Update error:', error);
        }
      });      
      

   // Chart Initialization
   const initializeChart = () => {
    const chartCanvas = document.getElementById('taskGraph');
    if (chartCanvas && typeof Chart !== 'undefined') {
      const ctx = chartCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: window.chartData || {
          labels: ["To-Do", "In Progress", "Completed", "Expired"],
          datasets: [{
            data: [0, 0, 0, 0],
            backgroundColor: ["#EA2E2E", "#5BA4E5", "#54D376", "#999999"]
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

   // Tab Switching Functionality
   document.querySelectorAll('.task-tabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Remove active class from all tabs
      document.querySelectorAll('.task-tabs .nav-link').forEach(t => {
        t.classList.remove('active');
      });

      // Add active to clicked tab
      this.classList.add('active');
      
      // Hide all task groups
      document.querySelectorAll('.task-group').forEach(group => {
        group.style.display = 'none';
    });

      // Show selected group
      const status = this.dataset.status;
        const targetGroup = document.querySelector(`.${status}-group`);
        if (targetGroup) {
            targetGroup.style.display = 'block';
            targetGroup.style.opacity = 0;
            let opacity = 0;
            const animation = setInterval(() => {
                if (opacity >= 1) clearInterval(animation);
                targetGroup.style.opacity = opacity;
                opacity += 0.1;
            }, 50);
        }
    });
});

  // Initialize task interactions on page load
  initTaskInteractions();

  // Task Form Handling (MODIFIED)
  // Task Form Handling (MODIFIED)
  const taskForm = document.getElementById('taskForm');
  if (taskForm) {
    taskForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const dueDateInput = document.getElementById('dueDate');
      const dueDate = new Date(dueDateInput.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Strip time
      
      if (dueDate < today) {
        const proceed = confirm('⚠️ The deadline is in the past.\nAre you sure you want to create this task?');
        if (!proceed) return; // Cancel submission
      }      
      
      const formData = new FormData(taskForm);
      const collaboratorInputs = [...document.querySelectorAll('input[name="collaborators[]"]')];
      const collaborators = collaboratorInputs.map(input => input.value.trim());

      const validCollaborators = [];
      const invalidCollaborators = [];

      // Clear previous feedback
      collaboratorInputs.forEach(input => {
        input.classList.remove('is-invalid');
        const existingFeedback = input.nextElementSibling;
        if (existingFeedback && existingFeedback.classList.contains('invalid-feedback')) {
          existingFeedback.remove();
        }
      });

      // Check each collaborator email
      for (let i = 0; i < collaborators.length; i++) {
        const email = collaborators[i];
        const input = collaboratorInputs[i];

        if (!email) continue;

        const res = await fetch(`/TaskManagementSystem/api/check_user.php?email=${encodeURIComponent(email)}`);
        const data = await res.json();

        if (data.exists) {
          validCollaborators.push(email);
        } else {
          invalidCollaborators.push(email);
          input.classList.add('is-invalid');

          const feedback = document.createElement('div');
          feedback.className = 'invalid-feedback';
          feedback.textContent = 'User not found';
          input.parentNode.appendChild(feedback);
        }
      }

      // ⛔ Stop if any invalid
      if (invalidCollaborators.length > 0) {
        alert('Please fix invalid emails before submitting.');
        return;
      }

      formData.append('validCollaborators', JSON.stringify(validCollaborators));

      // Continue with form submission (fetch to create_task.php)
      try {
        const response = await fetch('/TaskManagementSystem/api/create_task.php', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const result = await response.json();

        if (result.success) {
          // Success actions
          if (location.pathname.endsWith('mytasks.php')) {
            location.reload();
          } else {
            await fetchAndUpdateTasks();
          }

          const modal = bootstrap.Modal.getInstance(document.getElementById('createTaskModal'));
          if (modal) modal.hide();

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

          location.reload();
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

// Modified Collaborator Function (Works for Both Modals)
window.addCollaboratorField = function(mode = 'create', email = '') {
  const containerId = mode === 'edit' ? 'editCollaboratorContainer' : 'collaboratorContainer';
  const container = document.getElementById(containerId); 
  
  const newField = `
    <div class="input-group mb-2">
      <input type="email" name="collaborators[]" 
            class="form-control" 
            placeholder="collaborator@example.com"
            value="${email}"
            pattern="[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}"
  >
      <button type="button" class="btn btn-outline-danger" 
              onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>
  `;
  
  container.insertAdjacentHTML('beforeend', newField);
};