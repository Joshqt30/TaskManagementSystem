<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="createTaskModalLabel">Create New Task</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="taskForm" autocomplete="off">
          <!-- Task Name -->
          <div class="mb-4">
            <label class="form-label fw-bold">Task Name</label>
            <input type="text" name="title" class="form-control form-control-lg" placeholder="Enter task name" required>
          </div>

          <!-- Description -->
          <div class="mb-4">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Add task description"></textarea>
          </div>

          <!-- Due Date -->
          <div class="mb-4">
            <label class="form-label fw-bold">Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
          </div>

            <!-- ← Insert the Status dropdown right here ↓ -->
          <div class="mb-3">
            <label for="createStatus" class="form-label fw-bold">Status</label>
            <select id="createStatus" name="status" class="form-select" required>
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
         <!-- ↑ End Status dropdown -->

          <!-- Collaborators Section -->
          <div class="mb-4">
            <label class="form-label fw-bold">Collaborators</label>
            <div class="alert alert-info" role="alert">
              <i class="fas fa-info-circle me-2"></i>
              Enter email addresses of collaborators
            </div>
            
          <!-- Sa task_modal.php -->
          <div id="collaboratorContainer">
            <div class="input-group mb-2">
              <input type="email" name="collaborators[]" class="form-control" 
                    placeholder="collaborator@example.com" required>
              <button type="button" class="btn btn-outline-danger" 
                      onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-success mt-2" 
                  onclick="addCollaboratorField()">
            <i class="fas fa-plus"></i> Add Collaborator
          </button>
          </div>

          <div class="modal-footer border-top-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-plus me-2"></i>Create Task
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

    <!-- Add Task Detail Modal here -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="taskDetailModalLabel">Task Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="task-meta-row mb-3">
          <div><strong>Status:</strong> <span id="detail-status"></span></div>
          <div><strong>Due Date:</strong> <span id="detail-due-date"></span></div>
        </div>
        <h4 id="detail-title" class="mb-3"></h4>
        <p id="detail-description" class="mb-4"></p>
        
        <!-- Add Collaborators Section Here -->
        <div class="collaborators-section">
          <h6>Collaborators:</h6>
          <div id="detail-collaborators" class="collaborators-list"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Task Modal (ADD THIS NEW MODAL HERE) -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">Edit Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editTaskForm">
        <div class="modal-body">
          <input type="hidden" name="task_id" id="editTaskId">
          <!-- Task Name -->
          <div class="mb-4">
            <label class="form-label fw-bold">Task Name</label>
            <input type="text" name="title" id="editTitle" class="form-control" required>
          </div>
          <!-- Status -->
          <div class="mb-4">
            <label class="form-label fw-bold">Status</label>
            <select name="status" id="editStatus" class="form-select">
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
// Dynamic collaborator fields
function addCollaboratorField(btn) {
  const container = document.getElementById('collaboratorContainer');
  const newField = document.createElement('div');
  newField.className = 'input-group mb-2 collaborator-field';
  newField.innerHTML = `
    <input type="email" name="collaborators[]" 
          class="form-control" 
          placeholder="collaborator@example.com"
          pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
    <div class="btn-group">
      <button type="button" class="btn btn-outline-danger remove-collaborator" 
              onclick="this.parentElement.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
      <button type="button" class="btn btn-outline-success add-collaborator" 
              onclick="addCollaboratorField(this)">
        <i class="fas fa-plus"></i>
      </button>
    </div>
  `;
  
  container.appendChild(newField);
}
</script>

<style>
.collaborator-field .btn-group {
  flex: 0 0 auto;
  width: auto;
}
.collaborator-field .form-control {
  flex: 1 1 auto;
}
.add-collaborator, .remove-collaborator {
  border-radius: 0 0.375rem 0.375rem 0 !important;
}
</style>