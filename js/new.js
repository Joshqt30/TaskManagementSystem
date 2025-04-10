document.addEventListener('DOMContentLoaded', function () {
  var toggleBtn = document.getElementById('toggleBtn');
  var sidebar = document.getElementById('sidebar');

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

    link.addEventListener('click', function (e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      overlay.style.opacity = 1;
      document.body.classList.add('page-transition');
      setTimeout(() => {
        window.location.href = url;
      }, 200);
    });
  });

  toggleBtn.addEventListener('click', function () {
    sidebar.classList.toggle('sidebar-hidden');
  });

  function verifyOTP() {
    const otp1 = document.getElementById('otp1').value;
    const otp2 = document.getElementById('otp2').value;
    const otp3 = document.getElementById('otp3').value;
    const otp4 = document.getElementById('otp4').value;

    if (otp1 && otp2 && otp3 && otp4) {
      const otp = otp1 + otp2 + otp3 + otp4;
      console.log('OTP Entered: ' + otp);
      document.getElementById('otp').value = otp;
      document.getElementById('otpForm').submit();
    } else {
      alert('Please enter all OTP digits');
    }
  }

  var chartCanvas = document.getElementById('taskGraph');
  if (chartCanvas) {
    var ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ["Completed", "In Progress", "To Do"],
        datasets: [{
          data: [5, 5, 5],
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

  // ✅ OTP INPUT AUTO NAVIGATION & VALIDATION
  const otpInputs = document.querySelectorAll('.otp-box');

  otpInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      const value = e.target.value;
      if (!/^\d$/.test(value)) {
        e.target.value = ''; // Reset if non-numeric input
        return;
      }
  
      if (index < otpInputs.length - 1) {
        otpInputs[index + 1].focus(); // Focus next field
      }
    });
  
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && index > 0) {
        otpInputs[index - 1].focus(); // Focus previous field if Backspace is pressed
      }
    });
  });
  

});
