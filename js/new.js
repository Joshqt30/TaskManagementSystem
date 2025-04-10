document.addEventListener('DOMContentLoaded', function () {
  var toggleBtn = document.getElementById('toggleBtn');
  var sidebar = document.getElementById('sidebar');

  if (!document.getElementById('transitionOverlay')) {
    document.body.insertAdjacentHTML('afterbegin', '<div id="transitionOverlay"></div>');
  }

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


  // Initialize the chart only if the canvas element exists
  // and Chart.js library is loaded
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

    // OTP Auto-Advance Functionality
    const otpInputs = document.querySelectorAll('.otp-box');
    
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            // Allow only digits
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }

            // Auto-focus next input
            if (index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            // Handle backspace
            if (e.key === 'Backspace' && index > 0 && !e.target.value) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // ✅ Add this block at the VERY BOTTOM of your JS file (before the last closing });
    document.querySelectorAll('form.otp-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        const otpValues = Array.from(form.querySelectorAll('.otp-box'))
                            .map(input => input.value)
                            .join('');
        
        if (otpValues.length === 4) {
          e.preventDefault
          if (confirm("Checking OTP...")) {
            form.submit();
          }
        } else {
          alert("Please fill all OTP fields!");
        }
      });
    });  

          // Password validation
    document.querySelector("form").addEventListener("submit", function(e) {
      const password = document.querySelector("input[name='password']");
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,20}$/;
      
      if (!regex.test(password.value)) {
          e.preventDefault();
          alert("Password must contain:\n- 8-20 characters\n- 1 uppercase\n- 1 lowercase\n- 1 number");
      }
    });

});
