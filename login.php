<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Orasmo Academy</title>
  <link rel="stylesheet" href="css/login.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="icon" href="logo.ico" />
</head>
<body class="body-login">

  <!-- HEADER -->
  <header class="site-header">
    <div class="container header-container">
      <a href="index.php" class="logo-link">
        <img src="logo.ico" alt="Orasmo Academy Logo" class="logo" />
        <span class="school-name">Orasmo Academy</span>
      </a>
      <nav class="header-nav">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="login.php" class="active">Login</a>
      </nav>
    </div>
  </header>

  <!-- MAIN LOGIN AREA -->
  <main class="black-fill">
    <div class="container d-flex justify-content-center align-items-center flex-column">

      <form class="login" id ="login" method="post" action="req/login.php">

        <div class="text-center">
          <img src="logo.ico" alt="Orasmo Academy Logo" />
        </div>

        <h3>LOGIN</h3>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger" role="alert">
            <?=htmlspecialchars($_GET['error'])?>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label" for="uname">Username</label>
          <input type="text" id="uname" name="uname" required />
        </div>

        <div class="mb-3">
          <label class="form-label" for="pass">Password</label>
          <input type="password" id="pass" name="pass" required />
        </div>

        <div class="mb-3">
          <label class="form-label" for="role">Login As</label>
          <select id="role" name="role" required>
            <option value="1">Admin</option>
            <option value="2">Teacher</option>
            <option value="3">Student</option>
            <option value="4">Registrar Office</option>
          </select>
        </div>

        <button type="submit" class="btn">Login</button>

        <a href="index.php" class="text-link">Home</a>
      </form>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container footer-container">
      <nav class="footer-nav">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="login.php">Login</a>
      </nav>
      <p class="copyright">
        &copy; 2025 Orasmo Academy. All rights reserved.
      </p>
    </div>
  </footer>

</body>
</html>
