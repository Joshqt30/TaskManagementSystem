<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ | Contact Us</title>
  
  <!-- Google Fonts (Inter & Inter Tight) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/transition.css" />
  <link rel="stylesheet" href="designs/main.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />

  <style>
    
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    
    body {
      background-color: #f0f7f7;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      padding-top: 60px; /* Add space for header */
    }

   
    .header {
      background-color: #DBE8E7;
      border-bottom: 1px solid #e0e0e0;
      height: 60px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
    }

    .header-title {
      font-size: 20px !important;
    }
    
    .header-nav .nav-link {
      color: #455a64;
    }
    
    .header-nav .nav-link.active {
      font-weight: 600;
      color: #00796b;
    }
    
    .login-link {
      color: #455a64;
      text-decoration: none;
      font-weight: 600;
      padding: 8px 15px;
      border-radius: 4px;
      transition: background-color 0.3s;
    }
    
    .login-link:hover {
      background-color: rgba(0, 121, 107, 0.1);
      color: #00796b;
    }

   
    .main-content {
      flex: 1 0 auto; 
      width: 100%;
      padding: 20px;
    }

    .contact-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      text-align: center;
    }
    
    .contact-title {
      margin-bottom: 40px;
      color: #455a64;
      font-weight: 600;
      font-size: 28px;
    }
    
    .contact-cards {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-bottom: 60px;
    }
    
    .contact-card {
      background-color: #e0f2e9;
      border-radius: 10px;
      padding: 20px;
      flex: 1;
      max-width: 320px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .contact-icon {
      width: 50px;
      height: 50px;
      background-color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
    }
    
    .contact-icon i {
      color: #455a64;
      font-size: 20px;
    }
    
    .contact-label {
      font-weight: 600;
      margin-bottom: 10px;
      color: #455a64;
    }
    
    .contact-value {
      color: #455a64;
      line-height: 1.5;
    }
    
    .faq-section {
      margin: 0 auto 60px;
      max-width: 800px;
    }
    
    .faq-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .faq-subheader {
      font-size: 20px;
      color: #455a64;
      margin-bottom: 30px;
      text-align: center;
    }
    
    .faq-item {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 15px;
      background-color: white;
      overflow: hidden;
    }
    
    .faq-question {
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      font-weight: 500;
      color: #455a64;
    }
    
    .faq-question i {
      font-size: 16px;
      transition: transform 0.3s;
    }
    
    .faq-answer {
      padding: 0 20px 15px;
      display: none;
      color: #546e7a;
    }
    
    .faq-question.active + .faq-answer {
      display: block;
    }
    
    .faq-question.active i {
      transform: rotate(45deg);
    }
    
    
    .orglogo {
      height: 32px;
      margin-right: 10px;
    }
    
   
    footer {
      background-color: #425C5A;
      color: white;
      padding: 30px 0;
      width: 100%;
      flex-shrink: 0; 
      margin-top: auto; 
    }
    
    .footer-container {
      display: flex;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .footer-logo {
      display: flex;
      align-items: center;
    }
    
    .footer-logo img {
      height: 40px;
      margin-right: 10px;
    }
    
    .footer-links {
      display: flex;
      gap: 40px;
    }
    
    .footer-column {
      text-align: left;
    }
    
    .footer-column h5 {
      margin-bottom: 15px;
      font-size: 16px;
    }
    
    .footer-column a {
      display: block;
      color: white;
      margin-bottom: 8px;
      text-decoration: none;
      font-size: 14px;
    }
    
    .footer-column p {
      font-size: 14px;
      line-height: 1.5;
    }
    
    .footer-bottom {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #e0e0e0;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      padding-top: 20px;
    }
    
    @media (max-width: 992px) {
      .contact-cards {
        flex-wrap: wrap;
      }
      
      .contact-card {
        min-width: 250px;
      }
    }
    
    @media (max-width: 768px) {
      body {
        padding-top: 60px; 
      }
      
      .footer-container {
        flex-direction: column;
        gap: 30px;
        text-align: center;
      }
      
      .footer-logo {
        justify-content: center;
      }
      
      .footer-links {
        flex-direction: column;
        gap: 30px;
      }
      
      .footer-column {
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-left d-flex align-items-center">
      <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo" />
      <span class="header-title">ORGanize+</span>
    </div>  
    <div class="header-center">
      <ul class="nav header-nav">
        <li class="nav-item"><a class="nav-link" href="aboutus.html">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="landing.php">Back to Home</a></li>
      </ul>
    </div>
    <div class="header-right">
      <div class="d-flex align-items-center">
        <a href="login.php" class="login-link">Login</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="contact-container">
      <h2 class="contact-title">Contact Us</h2>
      
      <div class="contact-cards">
        <div class="contact-card">
          <div class="contact-icon">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <div class="contact-label">Office Address</div>
          <div class="contact-value">Novaliches, Bayan Quezon City, Philippines</div>
        </div>
        
        <div class="contact-card">
          <div class="contact-icon">
            <i class="fa-solid fa-phone"></i>
          </div>
          <div class="contact-label">Mobile Number</div>
          <div class="contact-value">+63 994 816 5889</div>
        </div>
        
        <div class="contact-card">
          <div class="contact-icon">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div class="contact-label">Email Address</div>
          <div class="contact-value">organizeplusmail@gmail.com</div>
        </div>
      </div>
      
      <div class="faq-section">
        <h3 class="contact-title">FAQs</h3>
        <h4 class="faq-subheader">How can we help?</h4>
        
        <div class="faq-item">
          <div class="faq-question">
            How do I create an account on ORGanize+?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Click on "Sign Up" on our login page and follow the instructions to create your account. You'll need to provide your email and create a password.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Is ORGanize+ free to use?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Yes, You just need to create an account and use without any monthly subscription.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            How do I create and assign tasks?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Navigate to the "My Tasks" section, click on the "+ Create Task" button, fill in the task details, and assign it to team members if needed.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Can I set priorities and deadlines?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Yes, when creating or editing a task, you can set priority levels and deadlines to help organize your workflow efficiently.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Does ORGanize+ support recurring tasks?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Yes?
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Can I invite my team to ORGanize+?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Yes?
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            I forgot my password. How can I reset it?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            On the login page, click "Forgot Password" and enter your email address. You'll receive instructions to reset your password.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Is my data safe with ORGanize+?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            Yes, we employ industry-standard security measures to protect your data. All information is encrypted and stored securely.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            How can I contact support?
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="faq-answer">
            You can contact our support team via email at organizeplusmail@gmail.com or use the contact information provided on this page.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="footer-container">
      <div class="footer-logo">
        <img src="ORGanizepics/white-layer-50 (1).png" alt="ORGanize+ Logo">
        <span>ORGanize+</span>
      </div>
      
      <div class="footer-links">
        <div class="footer-column">
          <h5>Help</h5>
          <a href="#">About Us</a>
          <a href="#">Get In Touch</a>
        </div>
        
        <div class="footer-column">
          <h5>Contact Us</h5>
          <p>432 Queen's Highway 1100<br>Queen City Weekday Square (Magic)<br>organizeplusmail@gmail.com</p>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>All Rights Reserved. © ORGanize+, 2025</p>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // For FAQ's part
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
      question.addEventListener('click', () => {
       
        question.classList.toggle('active');
        
        
        faqQuestions.forEach(item => {
          if (item !== question && item.classList.contains('active')) {
            item.classList.remove('active');
          }
        });
      });
    });
  </script>
</body>
</html>