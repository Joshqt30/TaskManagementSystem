document.getElementById('toggleBtn').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('closed');

    const mainContent = document.getElementById('mainContent');
    if (sidebar.classList.contains('closed')) {
        mainContent.style.marginLeft = '0';
        mainContent.style.width = '100%';
    } else {
        mainContent.style.marginLeft = '250px';
        mainContent.style.width = 'calc(100% - 250px)';
    }
});


// Task Modal Functions
function openTaskModal() {
    document.getElementById('taskModal').style.display = 'block';
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

document.getElementById('logoutBtn').addEventListener('click', function() {
    alert("Logging out..."); 
    window.location.href = "login.html"; // Redirect to login page
});

document.getElementById('toggleBtn').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('active');
});
