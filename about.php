<?php 
include "DB_connection.php";
include "data/setting.php";
$setting = getSetting($conn);

if ($setting != 0) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About - <?=$setting['school_name']?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/about.css">
  <link rel="icon" href="logo.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="body-home">

  <!-- NAVIGATION -->
  <nav class="navbar">
    <div class="navbar-brand">
      <a href="index.php"><img src="logo.ico" alt="Logo"></a>
    </div>
    <div class="nav-toggle" onclick="toggleMenu()">☰</div>
    <div class="nav-links" id="navLinks">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="login.php">Login</a>
    </div>
  </nav>

  <!-- ABOUT SECTION -->
  <section id="about" class="about-section">
    <div class="card-horizontal">
      <div class="card-image">
        <img src="logo.ico" alt="School Logo">
      </div>
      <div class="card-body">
        <h3>About Us</h3>
        <p><?=$setting['about']?></p>
        <p><small><strong><?=$setting['school_name']?></strong></small></p>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer-nav">
    <div class="container text-center text-light">
      &copy; <?=$setting['current_year']?> <?=$setting['school_name']?>. All rights reserved.
    </div>
  </footer>

  <!-- TOGGLE JS -->
  <script>
    function toggleMenu() {
      document.getElementById("navLinks").classList.toggle("show");
    }
  </script>

</body>
</html>
<?php } else {
  header("Location: login.php");
  exit;
} ?>
