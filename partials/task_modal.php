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

          <!-- Collaborators Section -->
          <div class="mb-4">
            <label class="form-label fw-bold">Collaborators</label>
            <div class="alert alert-info" role="alert">
              <i class="fas fa-info-circle me-2"></i>
              Enter email addresses of collaborators
            </div>
            
            <div id="collaboratorContainer">
              <div class="input-group mb-2 collaborator-field">
                <input type="email" name="collaborators[]" 
                      class="form-control" 
                      placeholder="collaborator@example.com"
                      pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
                <button type="button" class="btn btn-outline-success add-collaborator" 
                        onclick="addCollaboratorField(this)">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
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