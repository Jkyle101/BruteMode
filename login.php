<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??'');
  $pwd=$_POST['password']??'';
  $u = fetch_one(q("SELECT * FROM users WHERE email=?","s",[$email]));
  if($u && password_ok($pwd,$u['password'])){ $_SESSION['uid']=$u['id']; header('Location: /BruteMode/dashboard.php'); exit; } else { $notice='Invalid credentials'; }
}
?>
<div class="auth-page">
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo-wrapper">
        <img src="/BruteMode/assets/images/logo.png" alt="BruteMode Logo" class="auth-logo">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Sign in to continue your fitness journey</p>
      </div>
      
      <?php if($notice){ echo '<div class="alert alert-info">'.$notice.'</div>'; } ?>
      
      <form method="post" class="auth-form">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" placeholder="Enter your password" required>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>
          <a href="#" class="auth-link">Forgot password?</a>
        </div>
        <button class="btn btn-primary w-100 btn-auth">Login</button>
      </form>
      
      <div class="auth-footer">
        <span>Don't have an account?</span>
        <a href="/BruteMode/register.php" class="auth-link">Create account</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
