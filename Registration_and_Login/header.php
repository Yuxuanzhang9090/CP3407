<?php
  session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="description" content="This is an example of a meta description. This will often
        show up in search results.">
        <meta name=viewpoint content="width=device-width, initial-scale=1">
        <title></title>
        <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <nav>
        <a href="#">
           <img src="", alt="">
        </a>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="#">Explore Restaurants & Menus</a></li>
            <li><a href="#">Contact Us</a></li>
        </ul>
        <div>
            <?php
              if (isset($_SESSION['userId'])) {
                echo '<form action="includes/logout.inc.php" method="post">
                      <button type="submit" name="logout-submit">Logout</button>
                      </form>';
              }
              else {
                echo '<form action="includes/login.inc.php" method="post">
                      <input type="text" name="mailuid" placeholder="Username/Email...">
                      <input type="password" name="pwd" placeholder="Password...">
                      <button type="submit" name="login-submit">Login</button>
                      </form>';
              }
            ?>
            <a href="signup.php">Sign Up</a>
        </div>
    </nav>
  </header>