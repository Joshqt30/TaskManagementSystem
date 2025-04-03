document.addEventListener('DOMContentLoaded', function() {
  var toggleBtn = document.getElementById('toggleBtn');
  var sidebar = document.getElementById('sidebar');

  // Siguraduhing meron ang transition overlay
  if (!document.getElementById('transitionOverlay')) {
    document.body.insertAdjacentHTML('afterbegin', '<div id="transitionOverlay"></div>');
  }

  const overlay = document.getElementById('transitionOverlay');

  const currentPage = window.location.pathname.split("/").pop();
  document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
    const linkPage = link.getAttribute('href');
    if (linkPage === currentPage) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }

    // Transition effect bago mag-navigate
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      overlay.style.opacity = 1;
      document.body.classList.add('page-transition');

      // Maikling delay (200ms) bago mag-navigate
      setTimeout(() => {
        window.location.href = url;
      }, 200);
    });
  });

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
