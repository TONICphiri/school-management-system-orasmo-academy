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
  <title>Contact - <?=$setting['school_name']?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/contact.css">
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

  <!-- CONTACT FORM -->
  <section id="contact" class="container">
    <form method="post" action="req/contact.php" class="contact-form">
      <h3>Contact Us</h3>

      <?php if (isset($_GET['error'])) { ?>
        <div class="alert alert-danger"><?=$_GET['error']?></div>
      <?php } ?>
      <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success"><?=$_GET['success']?></div>
      <?php } ?>

      <label>Email address</label>
      <input type="email" name="email" required>

      <label>Full Name</label>
      <input type="text" name="full_name" required>

      <label>Message</label>
      <textarea name="message" rows="4" required></textarea>

      <button type="submit">Send</button>
    </form>
  </section>

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

  <!-- TOGGLE MENU JS -->
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
