<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $date=$_POST['date']??date('Y-m-d');
  $weightInput=$_POST['weight']??null;
  $weight = $weightInput!==null ? convert_user_to_kg((float)$weightInput,$uid) : null;
  $neck=$_POST['neck']??null;
  $chest=$_POST['chest']??null;
  $waist=$_POST['waist']??null;
  $hips=$_POST['hips']??null;
  $arm=$_POST['arm']??null;
  $thigh=$_POST['thigh']??null;
  $photo=handle_upload('photo','progress');
  $existing = fetch_one(q("SELECT id FROM body_logs WHERE user_id=? AND date=?","is",[$uid,$date]));
  if($existing){
    q("UPDATE body_logs SET weight=?,neck=?,chest=?,waist=?,hips=?,arm=?,thigh=?,photo_path=? WHERE id=?","ddddddddi",[$weight,$neck,$chest,$waist,$hips,$arm,$thigh,$photo,$existing['id']]);
  } else {
    q("INSERT INTO body_logs(user_id,date,weight,neck,chest,waist,hips,arm,thigh,photo_path) VALUES(?,?,?,?,?,?,?,?,?,?,?)","isdddddddds",[$uid,$date,$weight,$neck,$chest,$waist,$hips,$arm,$thigh,$photo]);
  }
  $notice='Saved';
}
$logs=fetch_all(q("SELECT * FROM body_logs WHERE user_id=? ORDER BY date DESC LIMIT 30","i",[$uid]));

// Get latest and calculate changes
$latest = $logs[0] ?? null;
$previous = $logs[1] ?? null;
$weightChange = $latest && $previous ? round(convert_kg_to_user($latest['weight'],$uid) - convert_kg_to_user($previous['weight'],$uid), 1) : 0;

// Get user goal
$user = fetch_one(q("SELECT goal, weight as start_weight FROM users WHERE id=?", "i", [$uid]));
$startWeight = $user['start_weight'] ?? $latest['weight'] ?? 0;
$goalWeight = $user['goal'] ?? 0;
$weightProgress = $startWeight && $goalWeight ? round((($startWeight - $weight) / ($startWeight - $goalWeight)) * 100) : 0;
?>
<div class="container py-3">
  <?php if($notice): ?>
    <div class="body-alert"><span>✓</span> <?php echo $notice; ?></div>
  <?php endif; ?>

  <!-- Header -->
  <div class="body-header">
    <div class="body-title">
      <h2>Body Tracking</h2>
      <p>Track your measurements and progress</p>
    </div>
  </div>

  <!-- Stats Overview -->
  <div class="body-stats">
    <div class="stat-card">
      <div class="stat-icon">⚖️</div>
      <div class="stat-info">
        <span class="stat-label">Current Weight</span>
        <?php $unit=unit_label($uid); $currW = $latest ? convert_kg_to_user($latest['weight'],$uid) : null; ?>
        <span class="stat-value"><?php echo $currW ?? '-'; ?> <small><?php echo $unit; ?></small></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-info">
        <span class="stat-label">Change</span>
        <span class="stat-value <?php echo $weightChange > 0 ? 'negative' : ($weightChange < 0 ? 'positive' : ''); ?>">
          <?php echo $weightChange > 0 ? '+' : ''; echo $weightChange; ?>
        </span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🎯</div>
      <div class="stat-info">
        <span class="stat-label">Goal Progress</span>
        <span class="stat-value"><?php echo $weightProgress; ?>%</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📅</div>
      <div class="stat-info">
        <span class="stat-label">Logs</span>
        <span class="stat-value"><?php echo count($logs); ?></span>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Body Diagram & Measurements -->
    <div class="col-lg-6 mb-4">
      <div class="body-measurement-card">
        <h4>Body Measurements</h4>
        <div class="body-diagram-wrapper">
          <svg viewBox="0 0 200 350" class="body-diagram-svg">
            <!-- Body outline -->
            <ellipse cx="100" cy="25" rx="18" ry="20" fill="#3d434c"/>
            <rect x="90" y="42" width="20" height="20" fill="#3d434c"/>
            <!-- Torso -->
            <path d="M70 62 Q100 70 130 62 L125 150 Q100 160 75 150 Z" fill="#3d434c"/>
            <!-- Arms -->
            <rect x="45" y="65" width="20" height="70" rx="8" fill="#3d434c"/>
            <rect x="135" y="65" width="20" height="70" rx="8" fill="#3d434c"/>
            <!-- Legs -->
            <rect x="70" y="155" width="25" height="90" rx="10" fill="#3d434c"/>
            <rect x="105" y="155" width="25" height="90" rx="10" fill="#3d434c"/>
            <!-- Measurement points -->
            <circle cx="100" cy="85" r="6" fill="<?php echo $latest['chest'] ? '#e33b2e' : '#555'; ?>" class="measure-point"/>
            <circle cx="100" cy="115" r="6" fill="<?php echo $latest['waist'] ? '#ff7a00' : '#555'; ?>" class="measure-point"/>
            <circle cx="100" cy="140" r="6" fill="<?php echo $latest['hips'] ? '#28a745' : '#555'; ?>" class="measure-point"/>
            <circle cx="55" cy="100" r="5" fill="<?php echo $latest['arm'] ? '#17a2b8' : '#555'; ?>" class="measure-point"/>
            <circle cx="145" cy="100" r="5" fill="<?php echo $latest['arm'] ? '#17a2b8' : '#555'; ?>" class="measure-point"/>
            <circle cx="82" cy="200" r="5" fill="<?php echo $latest['thigh'] ? '#6f42c1' : '#555'; ?>" class="measure-point"/>
            <circle cx="118" cy="200" r="5" fill="<?php echo $latest['thigh'] ? '#6f42c1' : '#555'; ?>" class="measure-point"/>
          </svg>
        </div>
        
        <!-- Measurement Values Display -->
        <div class="measurements-grid">
          <div class="measure-item">
            <span class="measure-label">Chest</span>
            <span class="measure-value"><?php echo $latest['chest'] ?? '-'; ?> cm</span>
          </div>
          <div class="measure-item">
            <span class="measure-label">Waist</span>
            <span class="measure-value"><?php echo $latest['waist'] ?? '-'; ?> cm</span>
          </div>
          <div class="measure-item">
            <span class="measure-label">Hips</span>
            <span class="measure-value"><?php echo $latest['hips'] ?? '-'; ?> cm</span>
          </div>
          <div class="measure-item">
            <span class="measure-label">Arm</span>
            <span class="measure-value"><?php echo $latest['arm'] ?? '-'; ?> cm</span>
          </div>
          <div class="measure-item">
            <span class="measure-label">Thigh</span>
            <span class="measure-value"><?php echo $latest['thigh'] ?? '-'; ?> cm</span>
          </div>
          <div class="measure-item">
            <span class="measure-label">Neck</span>
            <span class="measure-value"><?php echo $latest['neck'] ?? '-'; ?> cm</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div class="col-lg-6 mb-4">
      <div class="body-form-card">
        <h4>Add Measurement</h4>
        <form method="post" enctype="multipart/form-data" class="body-form">
          <div class="form-row">
            <div class="form-group">
              <label>Date</label>
              <input class="form-control" type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          
          <div class="measure-inputs">
            <div class="input-group">
              <?php $unit=unit_label($uid); $valW = $latest ? convert_kg_to_user($latest['weight'],$uid) : ''; ?>
              <label>Weight (<?php echo $unit; ?>)</label>
              <input class="form-control" type="number" step="0.1" name="weight" value="<?php echo $valW; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Neck (cm)</label>
              <input class="form-control" type="number" step="0.1" name="neck" value="<?php echo $latest['neck'] ?? ''; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Chest (cm)</label>
              <input class="form-control" type="number" step="0.1" name="chest" value="<?php echo $latest['chest'] ?? ''; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Waist (cm)</label>
              <input class="form-control" type="number" step="0.1" name="waist" value="<?php echo $latest['waist'] ?? ''; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Hips (cm)</label>
              <input class="form-control" type="number" step="0.1" name="hips" value="<?php echo $latest['hips'] ?? ''; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Arm (cm)</label>
              <input class="form-control" type="number" step="0.1" name="arm" value="<?php echo $latest['arm'] ?? ''; ?>" placeholder="0.0">
            </div>
            <div class="input-group">
              <label>Thigh (cm)</label>
              <input class="form-control" type="number" step="0.1" name="thigh" value="<?php echo $latest['thigh'] ?? ''; ?>" placeholder="0.0">
            </div>
          </div>

          <div class="photo-upload">
            <label>Progress Photo</label>
            <div class="upload-area">
              <input type="file" name="photo" id="photo" accept="image/*">
              <label for="photo" class="upload-label">
                <span class="upload-icon">📷</span>
                <span>Click to upload photo</span>
              </label>
            </div>
            <?php if($latest['photo_path']): ?>
              <div class="current-photo">
                <img src="<?php echo $latest['photo_path']; ?>" alt="Current photo">
              </div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn-body-save">Save Measurements</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Photo Timeline -->
  <?php $photoLogs = array_filter($logs, fn($l) => !empty($l['photo_path'])); ?>
  <?php if(!empty($photoLogs)): ?>
  <div class="photo-timeline">
    <h4>Progress Photos</h4>
    <div class="timeline-scroll">
      <?php foreach($photoLogs as $pl): ?>
        <div class="timeline-item">
          <div class="timeline-photo">
            <img src="<?php echo $pl['photo_path']; ?>" alt="Progress">
          </div>
          <span class="timeline-date"><?php echo date('M d', strtotime($pl['date'])); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Weight Chart -->
  <div class="body-chart-card">
    <h4>Weight Progress</h4>
    <canvas id="weightChart" height="100"></canvas>
  </div>

  <!-- Recent Logs Table -->
  <div class="body-table-card">
    <h4>Recent Logs</h4>
    <div class="table-responsive">
      <table class="body-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Weight</th>
            <th>Chest</th>
            <th>Waist</th>
            <th>Hips</th>
            <th>Arm</th>
            <th>Thigh</th>
            <th>Photo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($logs as $l): ?>
            <tr>
              <td><?php echo sanitize($l['date']); ?></td>
              <td><?php echo $l['weight'] ? sanitize($l['weight']).' kg' : '-'; ?></td>
              <td><?php echo $l['chest'] ? sanitize($l['chest']).' cm' : '-'; ?></td>
              <td><?php echo $l['waist'] ? sanitize($l['waist']).' cm' : '-'; ?></td>
              <td><?php echo $l['hips'] ? sanitize($l['hips']).' cm' : '-'; ?></td>
              <td><?php echo $l['arm'] ? sanitize($l['arm']).' cm' : '-'; ?></td>
              <td><?php echo $l['thigh'] ? sanitize($l['thigh']).' cm' : '-'; ?></td>
              <td><?php echo $l['photo_path'] ? '<img src="'.sanitize($l['photo_path']).'" class="table-photo">' : '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const wc = document.getElementById('weightChart');
if(wc && <?php echo count($logs); ?> > 0){
  const labels = [<?php foreach(array_reverse($logs) as $l){ echo "'".sanitize($l['date'])."',"; } ?>];
  const data = [<?php foreach(array_reverse($logs) as $l){ echo ($l['weight']?:'null').','; } ?>];
  new Chart(wc,{
    type:'line',
    data:{
      labels,
      datasets:[{
        label:'Weight (kg)',
        data,
        borderColor:'#e33b2e',
        backgroundColor:'rgba(227,59,46,0.1)',
        fill:true,
        tension:0.4
      }]
    },
    options:{
      responsive:true,
      plugins:{legend:{labels:{color:'#c4c9cf'}}},
      scales:{
        x:{ticks:{color:'#c4c9cf'},grid:{color:'#3d434c'}},
        y:{ticks:{color:'#c4c9cf'},grid:{color:'#3d434c'}}
      }
    }
  });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
