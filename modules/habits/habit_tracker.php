<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $date=$_POST['date']??date('Y-m-d');
  $water=(int)($_POST['water_ml']??0);
  $sleep=(float)($_POST['sleep_hours']??0);
  $protein=(int)($_POST['protein_g']??0);
  $existing = fetch_one(q("SELECT id FROM habits WHERE user_id=? AND date=?","is",[$uid,$date]));
  if($existing){ q("UPDATE habits SET water_ml=?,sleep_hours=?,protein_g=? WHERE id=?","iddi",[$water,$sleep,$protein,$existing['id']]); }
  else { q("INSERT INTO habits(user_id,date,water_ml,sleep_hours,protein_g) VALUES(?,?,?,?,?)","isidi",[$uid,$date,$water,$sleep,$protein]); add_points($uid,1,'Habit'); }
  $_SESSION['new_badges']=check_unlock_badges($uid);
  $notice='Saved';
}
$today=date('Y-m-d');
$h=fetch_one(q("SELECT * FROM habits WHERE user_id=? AND date=?","is",[$uid,$today]));

// Daily goals
$goals = ['water' => 3000, 'sleep' => 8, 'protein' => 150];
$water = (int)($h['water_ml'] ?? 0);
$sleep = (float)($h['sleep_hours'] ?? 0);
$protein = (int)($h['protein_g'] ?? 0);
$waterPct = min(100, round($water / $goals['water'] * 100));
$sleepPct = min(100, round($sleep / $goals['sleep'] * 100));
$proteinPct = min(100, round($protein / $goals['protein'] * 100));

// Get weekly data
$weekData = fetch_all(q("SELECT date, water_ml, sleep_hours, protein_g FROM habits WHERE user_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) ORDER BY date", "i", [$uid]));
?>
<div class="container py-3">
  <?php if($notice): ?>
    <div class="habit-alert"><span>✓</span> <?php echo $notice; ?></div>
  <?php endif; ?>
  
  <div class="habits-header">
    <div class="habits-title">
      <h2>Daily Habits</h2>
      <p>Track your nutrition and wellness goals</p>
    </div>
    <form method="post" class="date-picker-form">
      <input type="date" class="form-control" name="date" value="<?php echo $today; ?>">
    </form>
  </div>

  <div class="habits-grid">
    <div class="habit-card water-card">
      <div class="habit-card-header">
        <div class="habit-icon">💧</div>
        <div class="habit-title">Water Intake</div>
      </div>
      <div class="progress-ring-container">
        <svg class="progress-ring" viewBox="0 0 120 120">
          <circle class="progress-bg" cx="60" cy="60" r="52"/>
          <circle class="progress-fill water-fill" cx="60" cy="60" r="52" style="stroke-dashoffset: <?php echo 327 - (327 * $waterPct / 100); ?>"/>
        </svg>
        <div class="progress-value">
          <span class="value"><?php echo $water; ?></span>
          <span class="unit">ml</span>
        </div>
      </div>
      <div class="goal-text">Goal: <?php echo $goals['water']; ?>ml</div>
      <div class="quick-add">
        <button class="quick-btn" onclick="adjustHabit('water', 250)">+250ml</button>
        <button class="quick-btn" onclick="adjustHabit('water', 500)">+500ml</button>
      </div>
    </div>

    <div class="habit-card sleep-card">
      <div class="habit-card-header">
        <div class="habit-icon">😴</div>
        <div class="habit-title">Sleep</div>
      </div>
      <div class="progress-ring-container">
        <svg class="progress-ring" viewBox="0 0 120 120">
          <circle class="progress-bg" cx="60" cy="60" r="52"/>
          <circle class="progress-fill sleep-fill" cx="60" cy="60" r="52" style="stroke-dashoffset: <?php echo 327 - (327 * $sleepPct / 100); ?>"/>
        </svg>
        <div class="progress-value">
          <span class="value"><?php echo $sleep; ?></span>
          <span class="unit">hrs</span>
        </div>
      </div>
      <div class="goal-text">Goal: <?php echo $goals['sleep']; ?>hrs</div>
      <div class="quick-add">
        <button class="quick-btn" onclick="adjustHabit('sleep', 0.5)">+0.5hr</button>
        <button class="quick-btn" onclick="adjustHabit('sleep', 1)">+1hr</button>
      </div>
    </div>

    <div class="habit-card protein-card">
      <div class="habit-card-header">
        <div class="habit-icon">🥩</div>
        <div class="habit-title">Protein</div>
      </div>
      <div class="progress-ring-container">
        <svg class="progress-ring" viewBox="0 0 120 120">
          <circle class="progress-bg" cx="60" cy="60" r="52"/>
          <circle class="progress-fill protein-fill" cx="60" cy="60" r="52" style="stroke-dashoffset: <?php echo 327 - (327 * $proteinPct / 100); ?>"/>
        </svg>
        <div class="progress-value">
          <span class="value"><?php echo $protein; ?></span>
          <span class="unit">g</span>
        </div>
      </div>
      <div class="goal-text">Goal: <?php echo $goals['protein']; ?>g</div>
      <div class="quick-add">
        <button class="quick-btn" onclick="adjustHabit('protein', 25)">+25g</button>
        <button class="quick-btn" onclick="adjustHabit('protein', 50)">+50g</button>
      </div>
    </div>
  </div>

  <div class="weekly-overview">
    <h4>Weekly Overview</h4>
    <div class="week-grid">
      <?php 
      $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
      for($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $dayName = $days[date('N', strtotime($d)) - 1];
        $dayData = null;
        foreach($weekData as $wd) {
          if($wd['date'] == $d) { $dayData = $wd; break; }
        }
        $hasData = $dayData && ($dayData['water_ml'] > 0 || $dayData['sleep_hours'] > 0 || $dayData['protein_g'] > 0);
        $isToday = $d == $today;
      ?>
        <div class="week-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $hasData ? 'completed' : ''; ?>">
          <span class="day-name"><?php echo $dayName; ?></span>
          <span class="day-num"><?php echo date('d', strtotime($d)); ?></span>
          <div class="day-dots">
            <span class="dot water <?php echo $dayData && $dayData['water_ml'] >= $goals['water'] ? 'filled' : ''; ?>"></span>
            <span class="dot sleep <?php echo $dayData && $dayData['sleep_hours'] >= $goals['sleep'] ? 'filled' : ''; ?>"></span>
            <span class="dot protein <?php echo $dayData && $dayData['protein_g'] >= $goals['protein'] ? 'filled' : ''; ?>"></span>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>

  <div class="weekly-stats">
    <div class="stat-box">
      <div class="stat-label">Weekly Frequency Rank</div>
      <div class="stat-value"><?php echo frequency_rank_label($uid); ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Workouts This Week</div>
      <div class="stat-value"><?php echo weekly_workouts($uid); ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Current Streak</div>
      <div class="stat-value streak">🔥 <?php echo weekly_workouts($uid); ?> days</div>
    </div>
  </div>

  <div class="save-section">
    <form method="post" class="save-form">
      <input type="hidden" name="date" value="<?php echo $today; ?>">
      <input type="hidden" id="input-water" name="water_ml" value="<?php echo $water; ?>">
      <input type="hidden" id="input-sleep" name="sleep_hours" value="<?php echo $sleep; ?>">
      <input type="hidden" id="input-protein" name="protein_g" value="<?php echo $protein; ?>">
      <button type="submit" class="btn-save">Save Progress</button>
    </form>
  </div>
</div>

<script>
function adjustHabit(type, amount) {
  const input = document.getElementById('input-' + type);
  input.value = Math.max(0, parseFloat(input.value) + amount);
  document.querySelector('.save-form').submit();
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
