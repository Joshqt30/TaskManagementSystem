<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
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
            <input type="date" id="dueDate" name="due_date" class="form-control" required>
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

<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <!-- Header -->
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="taskDetailModalLabel">
          <i class="fa fa-info-circle me-2"></i>Task Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="row gx-4 gy-3">
          <!-- Metadata Column -->
          <div class="col-md-4">
            <div class="card mb-3">
              <div class="card-body py-2">
                <p class="mb-2"><strong>Status:</strong>
                  <span id="detail-status" class="badge"></span>
                </p>
                <p class="mb-0"><strong>Due Date:</strong>
                  <span id="detail-due-date"></span>
                </p>
              </div>
            </div>
          </div>

          <!-- Content Column -->
          <div class="col-md-8">
            <h4 id="detail-title" class="fw-bold mb-2"></h4>
            <p id="detail-description" class="text-muted"></p>
          </div>
        </div>

        <hr/>

    <!-- Collaborators -->
    <h6 class="fw-semibold mb-2">
      <i class="fa fa-users me-1 text-secondary"></i>
      Team
      <small class="text-muted ms-2">(Owner + Collaborators)</small>
    </h6>
    <ul id="detail-collaborators" class="list-group list-group-flush">
      <!-- Template for collaborator item -->
      <template id="collaboratorTemplate">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <span class="collaborator-label badge me-2"></span>
            <span class="collaborator-email"></span>
          </div>
          <!-- Add owner badge if needed -->
          <span class="owner-badge badge bg-success" style="display: none;">Owner</span>
        </li>
      </template>
    </ul>
      </div>

      <!-- Footer -->
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>


<!-- Edit Task Modal -->
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

          <!-- Description -->
          <div class="mb-4">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
          </div>

          <!-- Due Date -->
          <div class="mb-4">
            <label class="form-label fw-bold">Due Date</label>
            <input type="date" name="due_date" id="editDueDate" class="form-control" required>
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

          <!-- Add Owner Display Here -->
          <div class="mb-3">
            <label class="form-label fw-bold">Task Owner</label>
            <div class="form-control-plaintext ps-2">
              <i class="fas fa-crown text-warning me-2"></i>
              <span id="editOwnerEmail"></span>
            </div>
          </div>

          <!-- Collaborators Section -->
          <div class="mb-4" id="collaboratorSection" style="display: none;">
            <label class="form-label fw-bold">Collaborators</label>
            <div id="editCollaboratorContainer"></div>
            <button type="button" class="btn btn-sm btn-success mt-2" onclick="addCollaboratorField('edit')">
              <i class="fas fa-plus"></i> Add Collaborator
            </button>
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
function addCollaboratorField(mode = 'create') {
  const container = (mode === 'edit') 
    ? document.getElementById('editCollaboratorContainer') 
    : document.getElementById('collaboratorContainer');

  const newField = document.createElement('div');
  newField.className = 'input-group mb-2 collaborator-field';
  newField.innerHTML = `
    <input type="email" name="collaborators[]" 
          class="form-control" 
          placeholder="collaborator@example.com"
          pattern="[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}"
          required
    >
    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
      <i class="fas fa-times"></i>
    </button>
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