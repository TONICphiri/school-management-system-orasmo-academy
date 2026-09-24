<?php 
include "DB_connection.php";
include "data/setting.php";
$setting = getSetting($conn);

if ($setting != 0):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Welcome to <?=$setting['school_name']?></title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/index.css" />
  <link rel="icon" href="logo.ico" />
</head>
<body class="body-home">

  <div class="page-wrapper">

    <!-- NAVIGATION -->
    <nav class="navbar">
      <a class="navbar-brand" href="index.php">
        <img src="logo.ico" alt="School Logo" />
      </a>
      <div class="nav-toggle" onclick="toggleMenu()">☰</div>
      <div class="nav-links" id="navLinks">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="login.php">Login</a>
      </div>
    </nav>

    <!-- MAIN CONTENT WITH BACKGROUND -->
    <main class="content-background">
      <div class="container">
        <section class="welcome-text">
          <img src="logo.ico" alt="School Logo" />
          <h4>Welcome to <?=$setting['school_name']?></h4>
          <p><?=$setting['slogan']?></p>
        </section>
      </div>
    </main>

    <!-- FOOTER -->
	<footer class="footer-nav">
  <div class="container">
    <div class="footer-links">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="login.php">Login</a>
    </div>
    <p>&copy; <?=$setting['current_year']?> <?=$setting['school_name']?>. All rights reserved.</p>
  </div>
</footer>

  </div>

  <script>
    function toggleMenu() {
      document.getElementById("navLinks").classList.toggle("show");
    }
  </script>

</body>
</html>

<?php else:
  header("Location: login.php");
  exit;
endif; 
?>
