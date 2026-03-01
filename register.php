<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??'');
  $pwd=$_POST['password']??'';
  $name=trim($_POST['name']??'');
  $weight=$_POST['weight']??null;
  $goal=trim($_POST['goal']??'');
  if($email && $pwd){
    $hash=password_hash($pwd,PASSWORD_DEFAULT);
    $stmt=q("INSERT INTO users(email,password,name,weight,goal) VALUES(?,?,?,?,?)","sssss",[$email,$hash,$name,$weight,$goal]);
    if($stmt){ $_SESSION['uid']=$stmt->insert_id; header('Location: /BruteMode/dashboard.php'); exit; } else { $notice='Registration failed'; }
  }
}
?>
<div class="auth-page">
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo-wrapper">
        <img src="/BruteMode/assets/images/logo.png" alt="BruteMode Logo" class="auth-logo">
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Join BruteMode and start your transformation</p>
      </div>
      
      <?php if($notice){ echo '<div class="alert alert-info">'.$notice.'</div>'; } ?>
      
      <form method="post" class="auth-form">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" placeholder="Enter your name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" placeholder="Create a password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Weight (kg)</label>
          <input class="form-control" name="weight" type="number" step="0.1" placeholder="Optional">
        </div>
        <div class="mb-3">
          <label class="form-label">Fitness Goal</label>
          <input class="form-control" name="goal" placeholder="e.g., Build muscle, Lose weight">
        </div>
        <button class="btn btn-primary w-100 btn-auth">Create Account</button>
      </form>
      
      <div class="auth-footer">
        <span>Already have an account?</span>
        <a href="/BruteMode/login.php" class="auth-link">Login</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
