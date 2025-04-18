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


// Edit Task Handler (NEW)
document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', async (e) => {
    e.stopPropagation(); // Add this to prevent task detail opening
    const taskId = e.target.dataset.id;
    
    try {
      const response = await fetch(`/TaskManagementSystem/api/get_task.php?id=${taskId}`);
      const task = await response.json();

      // Populate edit modal
      document.getElementById('editTaskId').value = task.id;
      document.getElementById('editTitle').value = task.title;
      document.getElementById('editStatus').value = task.status;

      // Show modal
      new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    } catch (error) {
      alert('Error loading task');
    }
  });
});

// Delete Handler
document.querySelectorAll('.delete-btn').forEach(button => {
  button.addEventListener('click', async (e) => {
    if (!confirm('Are you sure you want to delete this task?')) return;
    
    const taskId = e.target.dataset.id;
    
    try {
      const response = await fetch(`/TaskManagementSystem/api/delete_task.php?id=${taskId}`, {
        method: 'DELETE'
      });

      if (response.ok) {
        e.target.closest('tr').remove(); // Remove from UI
      } else {
        throw new Error('Delete failed');
      }
    } catch (error) {
      alert('Error deleting task');
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


      // Edit Form Submission
document.getElementById('editTaskForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  try {
    const response = await fetch('/TaskManagementSystem/api/update_task.php', {
      method: 'POST',
      body: formData
    });

    if (response.ok) {
      location.reload(); // Refresh the task list
    } else {
      throw new Error('Update failed');
    }
  } catch (error) {
    alert('Error updating task');
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
  const taskForm = document.getElementById('taskForm');
  if (taskForm) {
    taskForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(taskForm);
      const collaborators = [...document.querySelectorAll('input[name="collaborators[]"]')]
                          .map(input => input.value);

  for (let [key, val] of formData.entries()) {
  console.log(key, val);
  }

          // I-validate ang emails bago ipadala
    const validCollaborators = [];
    for (const email of collaborators) {
      const response = await fetch(`/TaskManagementSystem/api/check_user.php?email=${encodeURIComponent(email)}`);
      const { exists } = await response.json();
      if (exists) validCollaborators.push(email);
    }

    formData.append('validCollaborators', JSON.stringify(validCollaborators));

      try {
        const response = await fetch('/TaskManagementSystem/api/create_task.php', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
          // Refresh both tasks and chart
          if (location.pathname.endsWith('mytasks.php')) {
            // If we're on mytasks.php, just reload the page
            location.reload();
          } else {
            // Otherwise, update the task list dynamically
            await fetchAndUpdateTasks();
          }
          
          // Hide modal using vanilla JS
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
