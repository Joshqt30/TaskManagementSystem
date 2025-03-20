<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Task Management System</title>
  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
  />
  <!-- External CSS -->
  <link rel="stylesheet" href="designs/index.css" />
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <a class="navbar-brand" href="#">ORGanizePLUS</a>
    <button
      class="navbar-toggler"
      type="button"
      data-toggle="collapse"
      data-target="#navbarNav"
      aria-controls="navbarNav"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon" style="color: #fff;"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link" href="#">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Logout</a></li>
      </ul>
    </div>
  </nav>

  <!-- MAIN CONTAINER (Sidebar + Content) -->
  <div class="container-fluid">
    <div class="row">
      <!-- SIDEBAR -->
      <div class="col-12 col-md-3 sidebar p-3">
        <h4 class="text-center">Menu</h4>
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">My Tasks</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Inbox</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Settings</a></li>
        </ul>
      </div>

      <!-- MAIN CONTENT -->
      <div class="col-12 col-md-9 main-content">
        <!-- Placeholder for your future content -->
        <div class="card shadow-sm mt-4">
          <div class="card-body">
            <h2 class="card-title">Welcome to the Homepage</h2>
            <p class="card-text">
              This is a placeholder. You can add your own content or task elements here later.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="footer text-center py-3">
    <div class="container">
      <span>© 2023 Task Management System</span>
    </div>
  </footer>

  <!-- JS scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script
    src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"
  ></script>
  <script
    src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
  ></script>
</body>
</html>
