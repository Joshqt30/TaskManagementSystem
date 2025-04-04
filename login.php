<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="designs/login.css">
</head>
<body>


    <header>
        <img src="ORGanizepics/layers.png" class="ic">
        <h2>ORGanize+</h2>
        
        
    </header>


    <div class="container">
        <h1>Login</h1>
         
        <div class="log-con"> 
            <input type="text" class="log" placeholder="Username" required>
            <img src="ORGanizepics/user.png" class="ics">
        </div>
             
        <div class="log-con"> 
            <input type="password" class="log" placeholder="Password" required>
            <img src="ORGanizepics/padlock.png" class="ics">
        </div>
    
        
        <label class="check">
        <input type="checkbox"> Remember me
        </label>
        <a href="#" class="forgot">Forgot password?</a>
        <br>
        
        <!-- Imma change this once backend logic starts -->
        <a href="main.html">
            <button>Login</button>
          </a>
          
        <p>don't have an account?    <a href="register.php" > Sign up</a> </p>


    </div>



</body>
</html>