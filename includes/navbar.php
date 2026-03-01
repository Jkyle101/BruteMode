<?php
require_once __DIR__ . '/../config/database.php';
$uid = auth_user();
$doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$candidates = [
  '/BruteMode/assets/images/logo.png',
  '/BruteMode/assets/images/bmlogo.png',
  '/BruteMode/assets/logo.png'
];
$logoUrl = null;
foreach($candidates as $u){
  $fs = $doc . $u;
  if(file_exists($fs)){ $logoUrl = $u; break; }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bm-navbar border-bottom border-secondary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="/BruteMode/dashboard.php">
      <?php if($logoUrl){ ?>
        <img src="<?php echo $logoUrl; ?>" alt="BruteMode" class="bm-logo me-2">
      <?php } ?>
      <span class="bm-brand-text">BruteMode</span>
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if($uid){ ?>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/workouts/view_workout.php">Workouts</a></li>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/exercises/list_exercises.php">Exercises</a></li>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/habits/habit_tracker.php">Habits</a></li>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/body/body_logs.php">Body</a></li>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/achievements/badges.php">Badges</a></li>
        <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/modules/coach/coach.php">Coach</a></li>
        <?php } ?>
      </ul>
      <ul class="navbar-nav">
        <?php if($uid){ $u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid])); ?>
          <li class="nav-item"><span class="nav-link bm-nav-stat">Rank: <?php echo sanitize($u['rank']); ?> · Points: <?php echo (int)$u['points']; ?></span></li>
          <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/profile.php">Profile</a></li>
          <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/logout.php">Logout</a></li>
        <?php } else { ?>
          <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link bm-nav-link" href="/BruteMode/register.php">Register</a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
