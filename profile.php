<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$uid = auth_user();
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  if($action==='profile_save'){
    $name=trim($_POST['name']??'');
    $weightInput=$_POST['weight']??null;
    $weight=$weightInput!==null ? convert_user_to_kg((float)$weightInput,$uid) : null;
    $goal=trim($_POST['goal']??'');
    $pic = handle_upload('profile_pic','profiles');
    if($pic){ q("UPDATE users SET name=?,weight=?,goal=?,profile_pic=? WHERE id=?","sdssi",[$name,$weight,$goal,$pic,$uid]); }
    else { q("UPDATE users SET name=?,weight=?,goal=? WHERE id=?","sdsi",[$name,$weight,$goal,$uid]); }
    $notice='Profile updated';
  }
  if($action==='unit_pref'){
    $unit = $_POST['weight_unit']==='lb' ? 'lb' : 'kg';
    q("UPDATE users SET weight_unit=? WHERE id=?","si",[$unit,$uid]);
    $notice='Preferences updated';
  }
  if($action==='change_password'){
    $old=$_POST['old']??''; $new=$_POST['new']??'';
    $u = fetch_one(q("SELECT password FROM users WHERE id=?","i",[$uid]));
    if($u && password_ok($old,$u['password']) && strlen($new)>=6){
      $hash=password_hash($new,PASSWORD_DEFAULT); q("UPDATE users SET password=? WHERE id=?","si",[$hash,$uid]);
      $notice='Password changed';
    } else { $notice='Password change failed'; }
  }
}
$u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid]));
$tw=total_workouts($uid);
$ww=weekly_workouts($uid);
$st=current_streak($uid);
$lv=lifetime_volume($uid);
$prevWeek = fetch_one(q("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND date BETWEEN DATE_SUB(CURDATE(),INTERVAL 14 DAY) AND DATE_SUB(CURDATE(),INTERVAL 7 DAY)","i",[$uid]))['c'] ?? 0;
$trend = $ww - (int)$prevWeek;
$doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$candidates = ['/BruteMode/assets/images/logo.png','/BruteMode/assets/images/bmlogo.png','/BruteMode/assets/logo.png'];
$logoUrl = null; foreach($candidates as $p){ if(file_exists($doc.$p)){ $logoUrl=$p; break; } }
$avatar = $u['profile_pic'] ?: $logoUrl;
$recentW = fetch_all(q("SELECT * FROM workouts WHERE user_id=? ORDER BY date DESC LIMIT 6","i",[$uid]));
$latestBadges = fetch_all(q("SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.unlocked_at DESC LIMIT 6","i",[$uid]));
$goalText = $u['goal'] ?: 'No goal set';
$goalPct = min(100, max(0, round(($lv/50000)*100)));
$days=[]; for($i=34;$i>=0;$i--){ $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); $cnt = fetch_one(q("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND date=?","is",[$uid,$d]))['c']??0; $days[]=['date'=>$d,'c'=>$cnt]; }
$exerciseIds = fetch_all(q("SELECT DISTINCT we.exercise_id FROM workout_exercises we JOIN workouts w ON w.id=we.workout_id WHERE w.user_id=?","i",[$uid]));
$prs=[]; foreach($exerciseIds as $ex){ $best = best_one_rm($uid,$ex['exercise_id']); if($best>0){ $name = fetch_one(q("SELECT name FROM exercises WHERE id=?","i",[$ex['exercise_id']]))['name']??'Exercise'; $prs[]=['name'=>$name,'best'=>$best]; } }
usort($prs,function($a,$b){ return $b['best']<=>$a['best']; });
$prs = array_slice($prs,0,6);
?>
<div class="container py-3">
  <?php if($notice){ echo '<div class="alert alert-info">'.$notice.'</div>'; } ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card bg-secondary bm-card bm-stripes">
        <div class="card-body d-flex align-items-center">
          <img src="<?php echo sanitize($avatar); ?>" class="rounded me-3" style="width:72px;height:72px;object-fit:cover;">
          <div>
            <div class="fw-bold" style="font-size:1.2rem;"><?php echo sanitize($u['name'] ?: 'Athlete'); ?></div>
            <div class="d-flex align-items-center gap-2 mt-1">
              <span class="bm-chip">Rank <strong><?php echo sanitize($u['rank']); ?></strong></span>
              <span class="bm-chip">Points <strong><?php echo (int)$u['points']; ?></strong></span>
            </div>
          </div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🎯</span> Goal Progress</div>
          <div class="small text-muted mt-1"><?php echo sanitize($goalText); ?></div>
          <div class="progress mt-2"><div class="progress-bar" style="width: <?php echo $goalPct; ?>%"><?php echo $goalPct; ?>%</div></div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">⚙️</span> Quick Actions</div>
          <div class="d-grid gap-2 mt-2">
            <a class="btn btn-primary" href="/BruteMode/modules/workouts/view_workout.php">Add Workout</a>
            <a class="btn btn-primary" href="/BruteMode/modules/body/body_logs.php">Log Body</a>
            <a class="btn btn-primary" href="/BruteMode/modules/achievements/badges.php">View Badges</a>
          </div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🔗</span> Share Achievements</div>
          <?php $shareText = "BruteMode: ".$u['name']." • ".$u['rank']." • ".$tw." workouts"; ?>
          <div class="d-flex mt-2">
            <input class="form-control me-2" id="shareText" value="<?php echo sanitize($shareText); ?>">
            <button class="btn btn-primary" id="copyBtn">Copy</button>
          </div>
          <div class="small text-muted mt-1">Share to friends and social apps</div>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="row g-3">
        <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🏋️</span> Total</div><div class="display-6"><?php echo $tw; ?></div></div></div></div>
        <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🗓️</span> Weekly</div><div class="display-6"><?php echo $ww; ?></div><div class="small mt-1 <?php echo $trend>=0?'text-success':'text-danger'; ?>"><?php echo $trend>=0?'▲':'▼'; ?> <?php echo abs($trend); ?></div></div></div></div>
        <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🔥</span> Streak</div><div class="display-6"><?php echo $st; ?></div></div></div></div>
        <?php $unit=unit_label($uid); $lvDisp = $lv * ($unit==='lb' ? 2.20462 : 1); ?>
        <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">📈</span> Volume</div><div class="display-6"><?php echo number_format($lvDisp,0); ?> <?php echo $unit; ?></div></div></div></div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🕒</span> Recent Activity</div>
          <div class="row mt-2">
            <div class="col-md-7">
              <table class="table table-dark table-sm"><thead><tr><th>Date</th><th>Notes</th><th>Vol</th></tr></thead><tbody>
                <?php foreach($recentW as $w){ $unit=unit_label($uid); $vd=(float)$w['total_volume']*($unit==='lb'?2.20462:1); echo '<tr><td>'.sanitize($w['date']).'</td><td>'.sanitize($w['notes']).'</td><td>'.number_format($vd,0).' '.$unit.'</td></tr>'; } ?>
              </tbody></table>
            </div>
            <div class="col-md-5">
              <div class="row">
                <?php foreach($latestBadges as $b){ echo '<div class="col-6 mb-2"><div class="card bg-secondary bm-card"><div class="card-body text-center"><div class="h2">'.$b['icon'].'</div><div class="small">'.sanitize($b['name']).'</div></div></div></div>'; } ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">📅</span> Activity Heatmap</div>
          <div class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap:6px; margin-top:10px;">
            <?php foreach($days as $d){ $c=$d['c']; $bg = $c>=3?'#ff7a00':($c==2?'#e33b2e':($c==1?'#8a2b24':'#2a2e33')); echo '<div class="rounded" style="height:20px;background:'.$bg.'" title="'.$d['date'].' ('.$c.')"></div>'; } ?>
          </div>
          <div class="small text-muted mt-1">Last 5 weeks</div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🏆</span> Personal Bests</div>
          <div class="row mt-2">
            <?php foreach($prs as $p){ $unit=unit_label($uid); $bestDisp = convert_kg_to_user($p['best'],$uid); echo '<div class="col-md-4 mb-2"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold">'.sanitize($p['name']).'</div><div class="display-6">'.number_format($bestDisp,1).' '.$unit.'</div><div class="small">1RM</div></div></div></div>'; } ?>
            <?php if(!count($prs)){ echo '<div class="text-muted">No records yet</div>'; } ?>
          </div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">👤</span> Edit Profile</div>
          <form method="post" enctype="multipart/form-data" class="mt-2">
            <input type="hidden" name="action" value="profile_save">
            <div class="row g-2">
              <div class="col-md-4"><input class="form-control" name="name" placeholder="Name" value="<?php echo sanitize($u['name']); ?>"></div>
              <?php $unit=unit_label($uid); $dispW = $u['weight']!==null ? convert_kg_to_user($u['weight'],$uid) : ''; ?>
              <div class="col-md-4"><input class="form-control" name="weight" type="number" step="0.1" placeholder="Weight (<?php echo $unit; ?>)" value="<?php echo sanitize($dispW); ?>"></div>
              <div class="col-md-4"><input class="form-control" name="goal" placeholder="Goal" value="<?php echo sanitize($u['goal']); ?>"></div>
              <div class="col-md-12"><input class="form-control" name="profile_pic" type="file"></div>
            </div>
            <button class="btn btn-primary mt-2">Save</button>
          </form>
          <div class="fw-bold widget-title mt-3"><span class="widget-icon">⚙️</span> Preferences</div>
          <form method="post" class="mt-2">
            <input type="hidden" name="action" value="unit_pref">
            <div class="row g-2">
              <div class="col-md-6">
                <select class="form-select" name="weight_unit">
                  <?php $unit=unit_label($uid); ?>
                  <option value="kg" <?php echo $unit==='kg'?'selected':''; ?>>Kilograms (kg)</option>
                  <option value="lb" <?php echo $unit==='lb'?'selected':''; ?>>Pounds (lb)</option>
                </select>
              </div>
              <div class="col-md-6">
                <button class="btn btn-primary">Save Preferences</button>
              </div>
            </div>
          </form>
          <div class="fw-bold widget-title mt-3"><span class="widget-icon">🔒</span> Change Password</div>
          <form method="post" class="mt-2">
            <input type="hidden" name="action" value="change_password">
            <div class="row g-2">
              <div class="col-md-6"><input class="form-control" name="old" type="password" placeholder="Current" required></div>
              <div class="col-md-6"><input class="form-control" name="new" type="password" placeholder="New" required></div>
            </div>
            <button class="btn btn-warning mt-2">Change</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('copyBtn')?.addEventListener('click',function(){
  const t = document.getElementById('shareText'); if(!t) return;
  t.select(); t.setSelectionRange(0, 99999);
  try { document.execCommand('copy'); this.textContent='Copied'; setTimeout(()=>{ this.textContent='Copy'; },1500); } catch(e){}
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
