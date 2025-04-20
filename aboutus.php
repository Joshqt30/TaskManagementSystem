<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORGanize+ | Task Management</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
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

    :root {
  --sidebar-width: 260px;
  --header-height: 55px;
  --transition-speed: 0.3s;
  /* Theme Colors */
  --bg-color: #E3F2F1;
  --header-bg: #DBE8E7;
  --sidebar-bg: #3D5654;
  --card-bg: #FFFFFF;
  --text-color: #3D5654;
  --hover-light: #FFFFFF;
  --active-bg: #E0F7FA;  /* Soft light blue for active link */
  --create-task-color: #3D5654;
  --sidebar-border: #CECCC5;
  --gold-color: #D4AF37;  /* Gold color for icons and borders */
}

/* Global Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
body {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg-color);
  color: var(--text-color);
  height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: var(--header-height);
  background: var(--header-bg);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 15px;
  z-index: 999;
}
.orglogo {
  height: 30px;
  width: 30px;
  margin-top: 4px;
  margin-left: 1px;
  margin-right: 6px;
}
.header-left,
.header-center,
.header-right {
  display: flex;
  align-items: center;
}
.header-title {
  font-family: "Inter Tight", sans-serif;
  font-size: 20px;
  font-weight: 650;
  line-height: 50px;
  color: var(--text-color);
}

/* Center Navigation */
.header-center {
  flex: 1;
  justify-content: center;
}
.header-nav {
  list-style: none;
  display: flex;
  gap: 15px;
}
.header-nav .nav-link {
  color: var(--text-color);
  text-decoration: none;
  font-weight: 600;
  padding: 5px 10px;
}
.header-nav .nav-link:hover {
  background-color: var(--hover-light);
  border-radius: 5px;
}

/* Content Wrapper */
.content-wrapper {
  display: flex;
  width: 100%;
  margin-top: var(--header-height);
  height: calc(100vh - var(--header-height));
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  font-weight: normal;
  width: var(--sidebar-width);
  background-color: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
  transition: width var(--transition-speed) ease-in-out;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-top-right-radius: 20px;
  border-bottom-right-radius: 20px;
  white-space: nowrap; /* Prevent text wrapping */
}
.sidebar.sidebar-hidden {
  width: 0;
}

/* Sidebar Middle: Profile & Navigation (Centered as per original) */
.sidebar-middle {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.sidebar-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  background-color: #425C5A;
  padding: 45px 10px;
  text-align: center;
  margin-bottom: 55px;
  white-space: nowrap;
  transition: all 0.3s ease-in-out;
  overflow: hidden;
}
.sidebar-profile i {
  font-size: 65px;
  color: white;
  border: 2px solid var(--gold-color);  /* Gold border for profile icon */
  border-radius: 50%;
  padding: 5px;
  white-space: nowrap;
}
.user-name {
  margin-top: 15px;
  font-size: 15px;
  color: white;
  font-weight: 100;
  padding-bottom: 10px;
  white-space: nowrap;
}

/* Sidebar Menu */
.sidebar-menu {
  list-style: none;
  margin: 0;
  padding: 0;
  width: 100%;
  white-space: nowrap; /* Prevent text wrapping */
}

/* Reduce spacing between menu items by using uniform margin */
.sidebar-menu .nav-item {
  margin-bottom: 3px; /* Reduced vertical gap */
}
.sidebar-menu .nav-link {
  color: white;
  display: flex;
  align-items: center;
  padding: 8px 15px;
  border-radius: 35px;
  transition: background-color 0.3s ease-in-out, padding 0.3s ease-in-out;
  margin: 2px 10px;  /* Uniform margin: 5px vertical, 10px horizontal */
  white-space: nowrap;
}

.sidebar-menu .nav-link:hover {
  background-color: var(--active-bg);
  color: #3D5654;
  margin: 2px 10px; /* Uniform margin: 5px vertical, 10px horizontal */
  padding: 12px 15px; /* Maintain padding on hover */
}

/* Active and Hover States */
.sidebar-menu .nav-link.active,
.sidebar-menu .nav-link:hover {
  background-color: var(--active-bg);
  color: #3D5654;
  margin: 2px 10px;
  padding: 12px 15px; /* Maintain padding on hover */
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.3); /* Adding shadow on active and hover */
}

/* Sidebar icons: set to gold with fixed width for alignment */
.sidebar-menu .nav-link i {
  color: var(--gold-color);
  width: 20px;
  text-align: center;
}

/* Add a bottom divider line to the menu */
.sidebar-menu::after {
  content: "";
  display: block;
  border-top: 1px solid var(--gold-color);
  margin: 10px 0px;
}

/* Custom Styling for the Burger Toggle Button */
#toggleBtn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  color: black;
}
#toggleBtn:hover {
  opacity: 0.9;
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
        <li class="nav-item"><a class="nav-link" href="main.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
      </ul>
    </div>
    <div class="header-right">
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <button class="btn rounded-circle user-btn text-dark" type="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-user" style="font-size:20px;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="settings.html">Account Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="login.html">Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>






















    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-image">
                <img src="ORGanizepics\AboutUs-image.png" alt="About Us Image">
            </div>
            <div class="hero-content">
                <h1 style ="color: #F59E0B">About Us</h1>
                <p>ORGanize+ is your go-to task management solution, designed to help you stay on top of your work effortlessly. With a user-friendly interface and powerful features, we make productivity simple and effective. Whether you're managing personal tasks or collaborating with a team, ORGanize+ streamlines your workflow so you can focus on what truly matters.</p>
                <p style="color: #F59E0B;"><i>Stay Organized. Achieve More.</i></p>
                <div class="hero-buttons">
                    <button class="btn btn-primary"><a class="nav-link" href="main.php">Explore Features</a></button>
                    <button class="btn btn-outline"><a class="nav-link" href="contact.php">Get in Touch</a></button>
                </div>
            </div>
        </div>
    </section>

<style>

    
.hero {
    background: #3D5654;
    padding: 50px;
    color: white;
}

.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
}

.hero-image {
    flex: 1;
  display: flex;
  justify-content: flex-start;
}

.hero-image img {
  max-width: 100%;
  height: auto;
  border-radius: 10px;
  margin-left: 0; 
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.hero-buttons {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background: #F59E0B;
    color: white;
}

.btn-primary:hover {
    background: #D97706;
}

.btn-outline {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.btn-outline:hover {
    background: rgb(255, 255, 255);
    color: #040501;
}
</style>





















    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <div class="section-header">
                <h2>What We Offer</h2>
                <p>Discover the benefits of using <b>ORGanize+</b> for all your tasks.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                    </div>
                    <img src="ORGanizepics\Features-MultiTask.png" alt="Features-MultiTask" width="80px" height="80px">
                    <h3>Efficient Task Management</h3>
                    <p>Easily create, edit, and organize tasks with clear priorities and deadlines.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                    </div>
                    <img src="ORGanizepics\Features-Collaboration.png" alt="Features-Collaboration" width="80px" height="80px">
                    <h3>Seamless Collaboration</h3>
                    <p>Work together with teammates, assign tasks, and track progress.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                    </div>
                    <img src="ORGanizepics\Features-Notification.png" alt="Features-Notification" width="80px" height="80px">
                    <h3>Smart Reminders & Notifications</h3>
                    <p>Stay on top of your schedule with automated alerts.</p>
                </div>
            </div>
        </div>
    </section>

<style>
  .features {
    background: #E3F2F1;
    padding: 5rem 2rem;
}

.features-container {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.section-header {
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 40px;
    color: #2F4F4F;
    margin-bottom: 5px;
}

.section-header p {
    color: #666;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    transition: transform 0.5s ease;
}

.feature-card {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 20px 30px rgba(8, 7, 7, 0.1);
}
.feature-card:hover{
  transform: scale(1.05);
}

.feature-icon {
    width: 4rem;
    height: 4rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon i {
    font-size: 20px;
    color: #030505;
}

.feature-card h3 {
    margin-bottom: 5px;
    color: #2F4F4F;
}

.feature-card p {
    color: #070303;
}
</style>

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
          <a href="aboutus.php">About Us</a>
          <a href="contact.php">Contact</a>
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
  <script src="js/new.js"></script>
  
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

<style>
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
      gap: 320px;
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
      
      .contact-cards {
        flex-direction: column;
        align-items: center;
      }
      
      .contact-card {
        width: 100%;
        max-width: 350px;
      }
    }
</style>





</body>
</html>
