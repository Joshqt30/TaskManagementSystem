// my js file

document.addEventListener('DOMContentLoaded', function() {
  var toggleBtn = document.getElementById('toggleBtn');
  var sidebar = document.getElementById('sidebar');

  // Toggle sidebar width
  toggleBtn.addEventListener('click', function() {
    sidebar.classList.toggle('sidebar-hidden');
  });

  // Initialize Chart.js example
  var chartCanvas = document.getElementById('taskGraph');
  if (chartCanvas) {
    var ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ["Completed", "In Progress", "To Do"],
        datasets: [{
          data: [5, 5, 5], // Adjust task data if backend is ready
          backgroundColor: ["#54D376", "#5BA4E5", "#EA2E2E"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true, 
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
});
