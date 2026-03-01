<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();

// Get current month/year or from URL
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Navigation
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear = $month == 12 ? $year + 1 : $year;

// Get first day of month and total days
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dayOfWeek = date('w', $firstDay);

// Get month name
$monthName = date('F', $firstDay);

// Get all workouts for this month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
$monthWorkouts = fetch_all(q("SELECT * FROM workouts WHERE user_id=? AND date>=? AND date<=? ORDER BY date","iss",[$uid,$startDate,$endDate]));

// Get workout exercises for this month - fetch all and filter in PHP
$workoutExercises = [];
$workoutIds = array_column($monthWorkouts, 'id');
if (!empty($workoutIds)) {
  $allWes = fetch_all(q("SELECT we.workout_id, e.name FROM workout_exercises we JOIN exercises e ON e.id=we.exercise_id","",[]));
  foreach($allWes as $we) {
    if(in_array($we['workout_id'], $workoutIds)) {
      if(!isset($workoutExercises[$we['workout_id']])) {
        $workoutExercises[$we['workout_id']] = [];
      }
      $workoutExercises[$we['workout_id']][] = $we['name'];
    }
  }
}

// Create workout lookup by date
$workoutsByDate = [];
foreach($monthWorkouts as $w) {
  $workoutsByDate[$w['date']] = $w;
}

$exAll=fetch_all(q("SELECT * FROM exercises WHERE (user_id IS NULL OR user_id=?) ORDER BY name","i",[$uid]));

// Get today's date for highlighting
$today = date('Y-m-d');
?>
<div class="container py-3">
  <div class="section-banner">
    <div class="section-left">
      <div class="section-icon">🏋️</div>
      <div>
        <div class="section-title">Workouts Calendar</div>
        <div class="section-sub">Plan and track your workout sessions</div>
      </div>
    </div>
    <div class="bm-chip"><span>Month</span><span><?php echo count($monthWorkouts); ?></span></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">
      <span class="me-2">+</span>Add Workout
    </button>
  </div>

  <!-- Calendar Navigation -->
  <div class="calendar-nav">
    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="calendar-nav-btn">← Prev</a>
    <h3 class="calendar-month-title"><?php echo $monthName . ' ' . $year; ?></h3>
    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="calendar-nav-btn">Next →</a>
  </div>

  <!-- Calendar Grid -->
  <div class="calendar-grid">
    <div class="calendar-header">
      <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>
    <div class="calendar-days">
      <?php
      for ($i = 0; $i < $dayOfWeek; $i++) { echo '<div class="calendar-day empty"></div>'; }
      for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = $currentDate === $today;
        $hasWorkout = isset($workoutsByDate[$currentDate]);
        $workout = $hasWorkout ? $workoutsByDate[$currentDate] : null;
        
        echo '<div class="calendar-day' . ($isToday ? ' today' : '') . ($hasWorkout ? ' has-workout' : '') . '" data-date="' . $currentDate . '">';
        echo '<span class="day-number">' . $day . '</span>';
        
        if ($hasWorkout) {
          echo '<div class="workout-indicator">';
          echo '<span class="workout-dot"></span>';
          $unit = unit_label($uid);
          $volDisplay = $workout['total_volume'] * ($unit==='lb' ? 2.20462 : 1);
          echo '<span class="workout-volume">' . number_format($volDisplay, 0) . ' ' . $unit . '</span>';
          echo '</div>';
          // Show exercise list
          $wid = $workout['id'];
          if(isset($workoutExercises[$wid]) && count($workoutExercises[$wid]) > 0) {
            echo '<div class="workout-exercises-list">';
            foreach(array_slice($workoutExercises[$wid], 0, 3) as $exName) {
              echo '<span class="exercise-tag">' . sanitize($exName) . '</span>';
            }
            if(count($workoutExercises[$wid]) > 3) {
              echo '<span class="exercise-more">+' . (count($workoutExercises[$wid]) - 3) . ' more</span>';
            }
            echo '</div>';
          }
        }
        
        // Quick add button instead of form
        echo '<button class="quick-add-btn-new" data-date="' . $currentDate . '" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">';
        echo '<span>+</span>';
        echo '</button>';
        echo '</div>';
      }
      $totalCells = $dayOfWeek + $daysInMonth;
      $remaining = 7 - ($totalCells % 7);
      if ($remaining < 7) { for ($i = 0; $i < $remaining; $i++) { echo '<div class="calendar-day empty"></div>'; } }
      ?>
    </div>
  </div>

  <!-- Selected Day Workout Details -->
  <div id="workoutDetails" class="workout-details-panel">
    <?php if (isset($_GET['date'])) {
      $selectedDate = $_GET['date'];
      $selectedWorkout = fetch_one(q("SELECT * FROM workouts WHERE user_id=? AND date=?","is",[$uid,$selectedDate]));
      if ($selectedWorkout) {
        $wes = fetch_all(q("SELECT we.*, e.name FROM workout_exercises we JOIN exercises e ON e.id=we.exercise_id WHERE we.workout_id=?","i",[$selectedWorkout['id']]));
    ?>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0"><?php echo sanitize($selectedWorkout['date']); ?></h5><small><?php echo sanitize($selectedWorkout['notes']); ?></small></div>
            <div>
              <form method="post" class="d-inline" action="/BruteMode/modules/workouts/add_workout.php"><input type="hidden" name="duplicate_last" value="1"><button class="btn btn-sm btn-warning">Duplicate</button></form>
              <form method="post" class="d-inline ms-1" action="/BruteMode/modules/workouts/delete_workout.php"><input type="hidden" name="workout_id" value="<?php echo $selectedWorkout['id']; ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </div>
          </div>
          <?php $unit = unit_label($uid); $volDisplay = (float)$selectedWorkout['total_volume'] * ($unit==='lb' ? 2.20462 : 1); ?>
          <div class="mt-2">Total Volume: <?php echo number_format($volDisplay,2); ?> <?php echo $unit; ?></div>
          <div class="mt-2">
            <form method="post" class="d-flex" action="/BruteMode/modules/workouts/edit_workout.php">
              <input type="hidden" name="workout_id" value="<?php echo $selectedWorkout['id']; ?>">
              <select class="form-select me-2" name="exercise_id"><?php foreach($exAll as $e){ echo '<option value="'.$e['id'].'">'.sanitize($e['name']).'</option>'; } ?></select>
              <button class="btn btn-sm btn-primary" name="action" value="add_exercise">Add Exercise</button>
            </form>
          </div>
          <?php foreach($wes as $we){ $sets=fetch_all(q("SELECT * FROM sets WHERE workout_exercise_id=? ORDER BY id","i",[$we['id']])); ?>
            <div class="mt-3 p-2 bg-dark rounded">
              <div class="fw-bold"><?php echo sanitize($we['name']); ?></div>
              <table class="table table-dark table-sm"><thead><tr><th>Reps</th><th>Weight</th><th>1RM</th></tr></thead><tbody>
              <?php foreach($sets as $s){ $unit = unit_label($uid); $wDisp = convert_kg_to_user($s['weight'],$uid); $rmDisp = convert_kg_to_user(one_rm($s['weight'],$s['reps']),$uid); echo '<tr><td>'.$s['reps'].'</td><td>'.$wDisp.' '.$unit.'</td><td>'.$rmDisp.' '.$unit.'</td></tr>'; } ?>
              </tbody></table>
              <form method="post" class="d-flex" action="/BruteMode/modules/workouts/edit_workout.php">
                <input type="hidden" name="workout_exercise_id" value="<?php echo $we['id']; ?>">
                <input class="form-control me-2" type="number" name="reps" placeholder="Reps" min="1" required>
                <input class="form-control me-2" type="number" step="0.5" name="weight" placeholder="Weight (<?php echo unit_label($uid); ?>)" min="0" required>
                <button class="btn btn-sm btn-success" name="action" value="add_set">Add Set</button>
              </form>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } else { ?>
      <div class="text-center text-muted p-4"><p>Select a day with a workout to view details</p></div>
    <?php } } else { ?>
      <div class="text-center text-muted p-4"><p>Click on a day with a workout (highlighted) to view details</p></div>
    <?php } ?>
  </div>
</div>

<!-- Add Workout Modal -->
<div class="modal fade" id="addWorkoutModal" tabindex="-1" aria-labelledby="addWorkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWorkoutModalLabel">🏋️ Add New Workout</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="/BruteMode/modules/workouts/add_workout.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Workout Date</label>
            <input type="date" class="form-control" name="date" id="modalDate" value="<?php echo $today; ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Workout Notes (Optional)</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="How did your workout go?"></textarea>
          </div>
          <div class="quick-exercises">
            <label class="form-label">Quick Add Exercise</label>
            <div class="exercise-chips">
              <?php foreach(array_slice($exAll, 0, 8) as $e): ?>
                <button type="button" class="exercise-chip" data-exercise="<?php echo sanitize($e['name']); ?>">
                  <?php echo sanitize($e['name']); ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Workout</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.quick-add-btn-new {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--bm-accent);
  border: none;
  color: #fff;
  font-size: 18px;
  cursor: pointer;
  opacity: 0;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.calendar-day:hover .quick-add-btn-new {
  opacity: 1;
}
.quick-add-btn-new:hover {
  transform: scale(1.15);
  background: var(--bm-accent-2);
}
.modal-content {
  background: var(--bm-card);
  border: 1px solid var(--bm-border);
  border-radius: 20px;
}
.modal-header {
  border-bottom: 1px solid var(--bm-border);
  padding: 20px 25px;
}
.modal-title {
  font-family: 'Oswald', sans-serif;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #fff;
}
.modal-body {
  padding: 25px;
}
.modal-footer {
  border-top: 1px solid var(--bm-border);
  padding: 15px 25px;
}
.form-label {
  font-weight: 600;
  color: #e6e6e6;
  margin-bottom: 8px;
}
.form-control {
  background: #1a1d24;
  border: 1px solid var(--bm-border);
  color: #fff;
  border-radius: 10px;
  padding: 12px 15px;
}
.form-control:focus {
  background: #1a1d24;
  border-color: var(--bm-accent);
  box-shadow: 0 0 0 3px rgba(227,59,46,0.2);
  color: #fff;
}
.form-control::placeholder {
  color: rgba(255,255,255,0.4);
}
textarea.form-control {
  resize: none;
}
.quick-exercises {
  margin-top: 15px;
}
.exercise-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.exercise-chip {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--bm-border);
  border-radius: 20px;
  padding: 8px 15px;
  color: var(--bm-text);
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.exercise-chip:hover {
  background: var(--bm-accent);
  border-color: var(--bm-accent);
  color: #fff;
}
.btn-close-white {
  filter: invert(1);
}
.workout-exercises-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 8px;
  max-height: 50px;
  overflow: hidden;
}
.exercise-tag {
  font-size: 0.65rem;
  padding: 2px 6px;
  background: rgba(227,59,46,0.2);
  border: 1px solid rgba(227,59,46,0.3);
  border-radius: 10px;
  color: #ffcdc4;
  white-space: nowrap;
}
.exercise-more {
  font-size: 0.6rem;
  color: var(--bm-muted);
  padding: 2px 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Handle click on workout days
  document.querySelectorAll('.calendar-day.has-workout').forEach(day => {
    day.addEventListener('click', function(e) {
      if (e.target.closest('.quick-add-btn-new')) return;
      const date = this.getAttribute('data-date');
      window.location.href = '?month=' + <?php echo $month; ?> + '&year=' + <?php echo $year; ?> + '&date=' + date;
    });
  });

  // Handle quick add buttons - set the date in modal
  document.querySelectorAll('.quick-add-btn-new').forEach(btn => {
    btn.addEventListener('click', function() {
      const date = this.getAttribute('data-date');
      document.getElementById('modalDate').value = date;
    });
  });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
