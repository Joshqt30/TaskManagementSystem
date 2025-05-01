<?php
// Start the session
session_start();
date_default_timezone_set('UTC');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include the database configuration
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo
  ->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
  ->execute([ $_SESSION['user_id'] ]);
// Fetch user information
try {
    $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    $username = $user['username'] ?: $user['email'];
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Fetch contacts (users with whom the logged-in user has a conversation)
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.email
        FROM users u
        INNER JOIN messages m 
        ON (u.id = m.sender_id OR u.id = m.receiver_id)
        WHERE u.id != ? 
        AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id']
    ]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching contacts: " . $e->getMessage());
}

// Fetch messages for the selected contact (if any)
$messages = [];
$selected_contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
$selected_contact_name = '';

if ($selected_contact_id) {
    try {
        // Get contact name
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$selected_contact_id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        $selected_contact_name = $contact ? ($contact['username'] ?: $contact['email']) : '';

        // Get messages
        $stmt = $pdo->prepare("
            SELECT content, file_path, created_at, sender_id
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $selected_contact_id,
            $selected_contact_id,
            $_SESSION['user_id']
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching messages: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title>ORGanize+ | Inbox</title>
  
  <!-- Google Fonts (Inter & Inter Tight) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/main.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />

  <!-- Inline Chat UI CSS -->
  <style>
    .chat-container {
      display: flex;
      height: calc(100vh - 80px);
      background-color: #f5f7fa;
      position: relative;
      overflow: hidden;
    }

    .contacts-sidebar {
      width: 300px;
      background-color: #e9ecef;
      border-right: 1px solid #dee2e6;
      overflow-y: auto;
      padding: 15px;
      z-index: 10;
      position: relative;
      flex-shrink: 0;
    }

    .contacts-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .contacts-header h2 {
      font-size: 1.5rem;
      font-family: 'Inter', sans-serif;
      color: #343a40;
    }

    .add-contact-btn {
      background: none;
      border: none;
      font-size: 1.2rem;
      color: #28a745;
      cursor: pointer;
    }

    .search-contacts {
      margin-bottom: 15px;
    }

    .search-contacts input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 20px;
      font-family: 'Inter', sans-serif;
    }

    .contact-list {
      position: relative;
      z-index: 6;
    }

    .contact-list .contact {
      display: flex;
      align-items: center;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s;
      text-decoration: none;
    }

    .contact-list .contact:hover,
    .contact-list .contact.active {
      background-color: #d1e7dd;
    }

    .contact img,
    .contact .avatar-placeholder {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-right: 10px;
      flex-shrink: 0;
    }

    .contact .avatar-placeholder {
      background-color: #6c757d;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
    }

    .contact span {
      font-family: 'Inter', sans-serif;
      color: #343a40;
    }

    .chat-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      position: relative;
      min-width: 0;
    }

    .chat-header {
      padding: 15px;
      background-color: #ffffff;
      border-bottom: 1px solid #dee2e6;
      font-family: 'Inter', sans-serif;
      font-size: 1.2rem;
      color: #343a40;
      z-index: 3;
      position: relative;
    }

    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      overflow-x: hidden;
      background-color: #f5f7fa;
      display: flex;
      flex-direction: column;
      gap: 10px;
      position: relative;
      z-index: 2;
      contain: content;
    }

    .chat-messages .message {
      max-width: 70%;
      padding: 12px 18px;
      border-radius: 15px;
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      word-wrap: break-word;
      position: relative;
      overflow: visible;
      box-sizing: border-box;
    }

    .chat-messages .message.sent {
      background-color: #28a745;
      color: white;
      align-self: flex-end;
      border-bottom-right-radius: 5px;
    }

    .chat-messages .message.received {
      background-color: #ffffff;
      color: #343a40;
      align-self: flex-start;
      border-bottom-left-radius: 5px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .chat-messages .message.image-message {
      max-width: 80%;
      padding: 12px;
      display: flex;
      flex-direction: column;
    }

    .chat-messages .message.image-message img {
      max-width: 250px;
      max-height: 250px;
      width: 100%;
      height: auto;
      object-fit: contain;
      border-radius: 8px;
      margin-bottom: 5px;
    }

    .chat-messages .message.file-message {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .chat-messages .message.file-message a {
      color: #007bff;
      text-decoration: none;
      word-break: break-all;
    }

    .chat-messages .message small {
      display: block;
      font-size: 0.7rem;
      opacity: 0.7;
      margin-top: 5px;
    }

    .chat-input {
      padding: 15px;
      background-color: #ffffff;
      border-top: 1px solid #dee2e6;
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
      z-index: 3;
    }

    .chat-input form {
      display: contents;
      width: 100%;
    }

    .chat-input input[type="text"] {
      flex: 1;
      padding: 10px;
      border: 1px solid #ced4da;
      border-radius: 20px;
      font-family: 'Inter', sans-serif;
    }

    .chat-input input[type="file"] {
      display: none;
    }

    .chat-input label {
      cursor: pointer;
      color: #6c757d;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .chat-input button {
      background-color: #28a745;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      cursor: pointer;
      transition: background-color 0.2s;
      flex-shrink: 0;
    }

    .chat-input button:hover {
      background-color: #218838;
    }

    /* Modal styles */
    #addContactModal .modal-content {
      font-family: 'Inter', sans-serif;
    }

    #addContactModal .modal-header {
      border-bottom: 1px solid #dee2e6;
    }

    #addContactModal .modal-body {
      padding: 20px;
    }

    #search-users {
      width: 100%;
      padding: 10px;
      border: 1px solid #ced4da;
      border-radius: 20px;
      margin-bottom: 15px;
    }

    #user-results {
      max-height: 300px;
      overflow-y: auto;
    }

    .user-result {
      display: flex;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
    }

    .user-result:hover {
      background-color: #f8f9fa;
    }

    .user-result .avatar-placeholder {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #6c757d;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
      margin-right: 10px;
    }

    .user-result span {
      flex: 1;
    }

    .user-result button {
      background-color: #28a745;
      border: none;
      color: white;
      padding: 5px 10px;
      border-radius: 5px;
    }

    .user-result button:hover {
      background-color: #218838;
    }

    /* Improved mobile styles */
    @media (max-width: 768px) {
      .contacts-sidebar {
        width: 250px;
      }

      .chat-messages {
        padding: 10px;
      }

      .chat-messages .message {
        max-width: 80%;
      }

      .chat-messages .message.image-message {
        max-width: 90%;
      }

      .chat-messages .message.image-message img {
        max-width: 200px;
        max-height: 200px;
      }

      .chat-input input[type="text"] {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      .chat-container {
        position: relative;
      }

      .contacts-sidebar {
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 10;
        display: none;
        background-color: #e9ecef;
      }

      .contacts-sidebar.active {
        display: block;
      }

      .chat-area {
        width: 100%;
      }

      .chat-messages .message {
        max-width: 85%;
      }

      .chat-messages .message.image-message {
        max-width: 95%;
      }
      
      .mobile-back-btn {
        display: inline-block;
        margin-right: 10px;
      }
      
      .no-messages-placeholder {
        text-align: center;
        color: #6c757d;
        margin-top: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-left d-flex align-items-center">
      <button id="toggleBtn" class="btn" type="button">
        <i class="fa-solid fa-bars"></i>  
      </button>
      <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo" />
      <span class="header-title">ORGanize+</span>
    </div>

    <div class="header-right">
      <div class="dropdown">
        <button class="btn rounded-circle user-btn text-dark" type="button" data-bs-toggle="dropdown">
          <i class="fa-solid fa-user" style="font-size:20px;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="settings.php">Account Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a>
        </ul>
      </div>
    </div>
  </header>

  <!-- Wrapper: Sidebar + Main Content -->
  <div class="content-wrapper">
    <!-- Sidebar -->
    <nav class="sidebar sidebar-expanded" id="sidebar">
      <div class="sidebar-middle">
      <div class="sidebar-profile">
      <?php if (!empty($user['profile_pic'])) : ?>
       <img src="uploads/profile_pics/<?= htmlspecialchars($user['profile_pic']) ?>"
            class="sidebar-profile-pic" 
            alt="Profile Picture">
      <?php else : ?>
        <i class="fa-solid fa-user-circle"></i>
      <?php endif; ?>
      <div class="user-name">
        <?= htmlspecialchars($user['username']) ?>
      </div>
    </div>
        <ul class="nav flex-column sidebar-menu">
          <li class="nav-item">
            <a href="main.php" class="nav-link">
              <i class="fa-solid fa-house me-2"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a href="mytasks.php" class="nav-link">
              <i class="fa-solid fa-check-circle me-2"></i> My Tasks
            </a>
          </li>
          <li class="nav-item">
            <a href="inbox.php" class="nav-link active">
              <i class="fa-solid fa-message me-2"></i> Inbox
            </a>
          </li>
          <li class="nav-item">
            <a href="calendar.php" class="nav-link">
              <i class="fa-solid fa-calendar me-2"></i> Calendar
            </a>
          </li>
          <li class="nav-item">
            <a href="Statistics.php" class="nav-link">
              <i class="fa-solid fa-chart-pie me-2"></i> Tasks Statistics
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content (Chat UI) -->
    <main class="main-content">
      <div class="chat-container">
        <!-- Contacts Sidebar -->
        <aside class="contacts-sidebar" id="contacts-sidebar">
          <div class="contacts-header">
            <h2>Chats</h2>
            <button class="add-contact-btn" title="Add Contact" data-bs-toggle="modal" data-bs-target="#addContactModal">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>
          <div class="search-contacts">
            <input type="text" placeholder="Search chats..." id="search-contacts" />
          </div>
          <div class="contact-list" id="contact-list">
            <?php foreach ($contacts as $contact): ?>
              <?php
                $contact_name = $contact['username'] ?: $contact['email'];
                $initials = strtoupper(substr($contact_name, 0, 2));
                $is_active = $selected_contact_id == $contact['id'] ? 'active' : '';
              ?>
              <a href="inbox.php?contact_id=<?php echo $contact['id']; ?>" class="contact <?php echo $is_active; ?>">
                <div class="avatar-placeholder"><?php echo htmlspecialchars($initials); ?></div>
                <span><?php echo htmlspecialchars($contact_name); ?></span>
              </a>
            <?php endforeach; ?>
            <?php if (empty($contacts)): ?>
              <p>No contacts found.</p>
            <?php endif; ?>
          </div>
        </aside>

        <!-- Chat Area -->
        <div class="chat-area">
          <div class="chat-header">
            <?php if ($selected_contact_id && isset($_GET['contact_id'])): ?>
              <span class="d-md-none mobile-back-btn" id="mobile-back-btn">
                <i class="fa-solid fa-arrow-left"></i>
              </span>
            <?php endif; ?>
            <?php echo $selected_contact_id ? htmlspecialchars($selected_contact_name) : 'Select a chat'; ?>
          </div>
          <div class="chat-messages" id="chat-messages">
            <?php if (empty($messages) && $selected_contact_id): ?>
              <div class="no-messages-placeholder">
                <p>No messages yet. Start the conversation!</p>
              </div>
            <?php else: ?>
              <?php foreach ($messages as $message): ?>
                <?php
                  $is_sent = $message['sender_id'] == $_SESSION['user_id'];
                  $message_class = $is_sent ? 'sent' : 'received';
                  $has_file = !empty($message['file_path']);
                  $has_content = !empty($message['content']);
                  
                  // Determine file type
                  $file_ext = $has_file ? strtolower(pathinfo($message['file_path'], PATHINFO_EXTENSION)) : '';
                  $is_image = $has_file && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);
                  
                  // Add appropriate message type classes
                  $message_type_class = '';
                  if ($is_image) {
                    $message_type_class = 'image-message';
                  } elseif ($has_file) {
                    $message_type_class = 'file-message';
                  }
                ?>
                
                <?php if ($has_content): ?>
                <!-- Text message -->
                <div class="message <?php echo $message_class; ?>">
                  <p><?php echo htmlspecialchars($message['content']); ?></p>
                  <small><?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                </div>
                <?php endif; ?>
                
                <?php if ($has_file): ?>
                <!-- File/Image message (separate from text) -->
                <div class="message <?php echo $message_class; ?> <?php echo $message_type_class; ?>">
                  <?php if ($is_image): ?>
                    <img src="<?php echo htmlspecialchars($message['file_path']); ?>" alt="Shared image" loading="lazy" />
                  <?php else: ?>
                    <a href="<?php echo htmlspecialchars($message['file_path']); ?>" target="_blank" download>
                      <i class="fa-solid fa-file"></i> <?php echo htmlspecialchars(basename($message['file_path'])); ?>
                    </a>
                  <?php endif; ?>
                  <small><?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                </div>
                <?php endif; ?>
                
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <?php if ($selected_contact_id): ?>
            <div class="chat-input">
              <form id="chat-form" enctype="multipart/form-data">
                <input type="hidden" name="receiver_id" value="<?php echo $selected_contact_id; ?>" />
                <input type="text" name="content" placeholder="Type a message..." autocomplete="off" />
                <label for="file-upload">
                  <i class="fa-solid fa-paperclip"></i>
                </label>
                <input id="file-upload" type="file" name="file" />
                <button type="submit" title="Send Message">
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Add Contact Modal -->
      <div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="addContactModalLabel">Add New Contact</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="text" id="search-users" placeholder="Search users..." />
              <div id="user-results"></div>
            </div>
          </div>
        </div>
      </div>

      
    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to logout?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <form action="logout.php" method="POST">
              <button type="submit" class="btn btn-danger">Yes, Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>

  <!-- Bootstrap JS and Popper.js -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  
  <!-- Socket.IO Client -->
  <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
  
  <!-- Pass user ID and contact ID to JavaScript -->
  <script>
    window.currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    window.currentContactId = <?php echo json_encode($selected_contact_id); ?>;
    
    console.log('User ID:', window.currentUserId, 'Contact ID:', window.currentContactId);
    
    // Initialize Socket.IO with reconnection options
    const socket = io('http://localhost:3000', {
      reconnection: true,
      reconnectionAttempts: 5,
      reconnectionDelay: 1000
    });
    
    socket.on('connect', () => {
      console.log('Socket.IO connected:', socket.id);
    });
    
    socket.on('connect_error', (error) => {
      console.error('Socket.IO connection error:', error);
    });
    
    socket.on('disconnect', (reason) => {
      console.log('Socket.IO disconnected:', reason);
    });
    
    // Join the conversation room when the page loads
    if (window.currentUserId && window.currentContactId) {
      console.log('Joining conversation room with:', { user_id: window.currentUserId, contact_id: window.currentContactId });
      socket.emit('join_conversation', {
        user_id: window.currentUserId,
        contact_id: window.currentContactId
      });
    }
    
    // Listen for new messages
    socket.on('new_message', (message) => {
      console.log('Received new_message:', message);
      const chatMessages = document.getElementById('chat-messages');
      const isSent = message.sender_id == window.currentUserId;
      const messageClass = isSent ? 'sent' : 'received';
      const hasContent = message.content && message.content.trim() !== '';
      const hasFile = message.file_path && message.file_path.trim() !== '';
      
      // Create the message element
      const messageDiv = document.createElement('div');
      messageDiv.className = 'message ' + messageClass;
      
      // Handle text message
      if (hasContent) {
        const textP = document.createElement('p');
        textP.textContent = message.content;
        messageDiv.appendChild(textP);
      }
      
      // Handle file/image message
      if (hasFile) {
        const fileExt = message.file_path.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExt);
        
        if (isImage) {
          messageDiv.className += ' image-message';
          const img = document.createElement('img');
          img.src = message.file_path;
          img.alt = 'Shared image';
          img.loading = 'lazy';
          messageDiv.appendChild(img);
        } else {
          messageDiv.className += ' file-message';
          const fileLink = document.createElement('a');
          fileLink.href = message.file_path;
          fileLink.target = '_blank';
          fileLink.download = true;
          fileLink.innerHTML = `<i class="fa-solid fa-file"></i> ${message.file_path.split('/').pop()}`;
          messageDiv.appendChild(fileLink);
        }
      }
      
      // Add timestamp
      const timeSmall = document.createElement('small');
      const messageTime = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      timeSmall.textContent = messageTime;
      messageDiv.appendChild(timeSmall);
      
      // Append to chat messages and scroll to bottom
      chatMessages.appendChild(messageDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
      console.log('Appended message to chat:', messageDiv.outerHTML);
    });
    
    document.addEventListener('DOMContentLoaded', function() {
      // Scroll chat to bottom
      const chatMessages = document.getElementById('chat-messages');
      if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
      
      // Mobile back button functionality
      const mobileBackBtn = document.getElementById('mobile-back-btn');
      const contactsSidebar = document.getElementById('contacts-sidebar');
      
      if (mobileBackBtn) {
        mobileBackBtn.addEventListener('click', function() {
          if (window.innerWidth <= 576) {
            contactsSidebar.classList.add('active');
          }
        });
      }
      
      // Toggle sidebar on mobile when selecting contact
      const contactLinks = document.querySelectorAll('.contact');
      contactLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth <= 576) {
            contactsSidebar.classList.remove('active');
          }
        });
      });
      
      // Search chats functionality
      const searchInput = document.getElementById('search-contacts');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          const contacts = document.querySelectorAll('.contact');
          
          contacts.forEach(contact => {
            const name = contact.querySelector('span').textContent.toLowerCase();
            if (name.includes(searchTerm)) {
              contact.style.display = 'flex';
            } else {
              contact.style.display = 'none';
            }
          });
        });
      }
      
      // File upload preview
      const fileInput = document.getElementById('file-upload');
      if (fileInput) {
        fileInput.addEventListener('change', function() {
          if (this.files.length > 0) {
            const fileName = this.files[0].name;
            alert(`Selected file: ${fileName}`);
          }
        });
      }

      // Add Contact Modal functionality
      const searchUsersInput = document.getElementById('search-users');
      const userResults = document.getElementById('user-results');
      let searchTimeout;

      searchUsersInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          const searchTerm = this.value.trim();
          if (searchTerm.length === 0) {
            userResults.innerHTML = '';
            return;
          }

          fetch('add_contact.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=search&search_term=${encodeURIComponent(searchTerm)}`
          })
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text();
          })
          .then(text => {
            try {
              const data = JSON.parse(text);
              userResults.innerHTML = '';
              if (data.error) {
                userResults.innerHTML = `<p class="text-danger">${data.error}</p>`;
                console.error('Server error:', data.error);
                return;
              }

              if (data.users.length === 0) {
                userResults.innerHTML = '<p>No users found.</p>';
                return;
              }

              data.users.forEach(user => {
                const userName = user.username || user.email;
                const initials = userName.substring(0, 2).toUpperCase();
                const userDiv = document.createElement('div');
                userDiv.className = 'user-result';
                userDiv.innerHTML = `
                  <div class="avatar-placeholder">${initials}</div>
                  <span>${userName}</span>
                  <button data-id="${user.id}" class="add-user-btn">Add</button>
                `;
                userResults.appendChild(userDiv);
              });

              // Add event listeners to "Add" buttons
              document.querySelectorAll('.add-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                  const contactId = this.getAttribute('data-id');
                  fetch('add_contact.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&contact_id=${contactId}`
                  })
                  .then(response => {
                    if (!response.ok) {
                      throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                  })
                  .then(data => {
                    if (data.error) {
                      userResults.innerHTML = `<p class="text-danger">${data.error}</p>`;
                      console.error('Error adding contact:', data.error);
                      return;
                    }

                    if (data.success) {
                      // Add the new contact to the list
                      const contactList = document.getElementById('contact-list');
                      const contact = data.contact;
                      const contactName = contact.username || contact.email;
                      const initials = contactName.substring(0, 2).toUpperCase();
                      const contactLink = document.createElement('a');
                      contactLink.href = `inbox.php?contact_id=${contact.id}`;
                      contactLink.className = 'contact';
                      contactLink.innerHTML = `
                        <div class="avatar-placeholder">${initials}</div>
                        <span>${contactName}</span>
                      `;
                      // Remove "No contacts found" message if present
                      const noContactsMessage = contactList.querySelector('p');
                      if (noContactsMessage) {
                        noContactsMessage.remove();
                      }
                      contactList.appendChild(contactLink);

                      // Close the modal
                      const modal = bootstrap.Modal.getInstance(document.getElementById('addContactModal'));
                      modal.hide();

                      // Redirect to the new conversation
                      window.location.href = `inbox.php?contact_id=${contact.id}`;
                    }
                  })
                  .catch(error => {
                    userResults.innerHTML = `<p class="text-danger">Error adding contact: ${error.message}</p>`;
                    console.error('Fetch error (add contact):', error);
                  });
              });
            });
          } catch (e) {
            console.error('JSON parse error:', e);
            console.log('Raw response:', text);
            userResults.innerHTML = `<p class="text-danger">Error parsing response: ${e.message}</p>`;
          }
        })
        .catch(error => {
          userResults.innerHTML = `<p class="text-danger">Error searching users: ${error.message}</p>`;
          console.error('Fetch error (search users):', error);
        });
      }, 300);
    });

    // Clear search results when modal is closed
    document.getElementById('addContactModal').addEventListener('hidden.bs.modal', function() {
      searchUsersInput.value = '';
      userResults.innerHTML = '';
    });
  });
</script>
  
  <!-- Custom JS -->
  <script src="js/new.js"></script>
  <script src="js/chat.js?nocache=<?php echo time(); ?>"></script>
</body>
</html>