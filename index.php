<?php
session_start();
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'brutemode';
$mysqli = new mysqli($dbHost, $dbUser, $dbPass);
if ($mysqli->connect_error) { die('DB error'); }
$mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db($dbName);
$mysqli->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) UNIQUE, password VARCHAR(255), name VARCHAR(120), weight DECIMAL(6,2) DEFAULT NULL, goal VARCHAR(255) DEFAULT NULL, profile_pic VARCHAR(255) DEFAULT NULL, rank VARCHAR(40) DEFAULT 'Recruit', points INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$mysqli->query("CREATE TABLE IF NOT EXISTS workouts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, date DATE, notes TEXT DEFAULT NULL, total_volume DECIMAL(12,2) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS exercises (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160), muscle_group VARCHAR(80), is_custom TINYINT(1) DEFAULT 0, user_id INT DEFAULT NULL, is_favorite TINYINT(1) DEFAULT 0, INDEX(user_id))");
$mysqli->query("CREATE TABLE IF NOT EXISTS workout_exercises (id INT AUTO_INCREMENT PRIMARY KEY, workout_id INT, exercise_id INT, FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE, FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS sets (id INT AUTO_INCREMENT PRIMARY KEY, workout_exercise_id INT, reps INT, weight DECIMAL(8,2), FOREIGN KEY (workout_exercise_id) REFERENCES workout_exercises(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS badges (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(80) UNIQUE, name VARCHAR(120), tier VARCHAR(20), icon VARCHAR(40), description VARCHAR(255))");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_badges (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, badge_id INT, unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_user_badge(user_id,badge_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_points (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, points INT, reason VARCHAR(80), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS body_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, date DATE, weight DECIMAL(6,2) DEFAULT NULL, neck DECIMAL(6,2) DEFAULT NULL, chest DECIMAL(6,2) DEFAULT NULL, waist DECIMAL(6,2) DEFAULT NULL, hips DECIMAL(6,2) DEFAULT NULL, arm DECIMAL(6,2) DEFAULT NULL, thigh DECIMAL(6,2) DEFAULT NULL, photo_path VARCHAR(255) DEFAULT NULL, INDEX(user_id,date), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
$mysqli->query("CREATE TABLE IF NOT EXISTS habits (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, date DATE, water_ml INT DEFAULT 0, sleep_hours DECIMAL(4,2) DEFAULT 0, protein_g INT DEFAULT 0, INDEX(user_id,date), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
function q($sql,$types='',$params=[]) { global $mysqli; $stmt=$mysqli->prepare($sql); if(!$stmt) return false; if($types){ $stmt->bind_param($types,...$params); } $stmt->execute(); return $stmt; }
function fetch_all($stmt){ $res=$stmt->get_result(); return $res? $res->fetch_all(MYSQLI_ASSOC):[]; }
function fetch_one($stmt){ $res=$stmt->get_result(); return $res? $res->fetch_assoc():null; }
function sanitize($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }
function ensure_upload_dir($dir){ if(!is_dir($dir)) { @mkdir($dir,0777,true); } }
function auth_user(){ return $_SESSION['uid']??null; }
function require_auth(){ if(!auth_user()){ header('Location: ?page=login'); exit; } }
function password_ok($pwd,$hash){ return password_verify($pwd,$hash); }
function one_rm($weight,$reps){ if($reps<1) return 0; return round($weight*(1+($reps/30)),2); }
function workout_volume($sets){ $t=0; foreach($sets as $s){ $t += ($s['weight']??0)*($s['reps']??0); } return $t; }
function add_points($uid,$points,$reason){ q("INSERT INTO user_points(user_id,points,reason) VALUES(?,?,?)","iis",[$uid,$points,$reason]); q("UPDATE users SET points=points+? WHERE id=?","ii",[$points,$uid]); update_rank($uid); }
function update_rank($uid){ $u = fetch_one(q("SELECT points FROM users WHERE id=?","i",[$uid])); if(!$u) return; $p=(int)$u['points']; $rank='Recruit'; if($p>=1500) $rank='Titan'; elseif($p>=1000) $rank='Warlord'; elseif($p>=600) $rank='Dominator'; elseif($p>=300) $rank='Gladiator'; elseif($p>=100) $rank='Warrior'; q("UPDATE users SET rank=? WHERE id=?","si",[$rank,$uid]); }
function seed_exercises(){ $exists = fetch_one(q("SELECT id FROM exercises LIMIT 1")); if($exists) return; $seed = [ ['Bench Press','Chest'], ['Incline Bench','Chest'], ['Push-up','Chest'], ['Squat','Legs'], ['Front Squat','Legs'], ['Deadlift','Back'], ['Romanian Deadlift','Back'], ['Lat Pulldown','Back'], ['Bent Row','Back'], ['Overhead Press','Shoulders'], ['Dumbbell Press','Shoulders'], ['Bicep Curl','Arms'], ['Tricep Extension','Arms'], ['Lunge','Legs'], ['Leg Press','Legs'], ['Leg Curl','Legs'], ['Calf Raise','Legs'], ['Hip Thrust','Glutes'], ['Pull-up','Back'], ['Chin-up','Arms'], ['Plank','Core'], ['Crunch','Core'] ];
  foreach($seed as $s){ q("INSERT INTO exercises(name,muscle_group,is_custom) VALUES(?,?,0)","ss",[$s[0],$s[1]]); } }
function seed_badges(){ $exists = fetch_one(q("SELECT id FROM badges LIMIT 1")); if($exists) return; $badges = [
 ['beginner_first','Beginner: First workout','Beginner','🥉','First workout'],
 ['beginner_5','Beginner: 5 workouts','Beginner','🥉','5 workouts'],
 ['beginner_streak3','Beginner: 3-day streak','Beginner','🥉','3-day streak'],
 ['intermediate_20','Intermediate: 20 workouts','Intermediate','🥈','20 workouts'],
 ['intermediate_streak7','Intermediate: 7-day streak','Intermediate','🥈','7-day streak'],
 ['intermediate_pr5','Intermediate: 5 PR improvements','Intermediate','🥈','5 PR improvements'],
 ['advanced_50','Advanced: 50 workouts','Advanced','🥇','50 workouts'],
 ['advanced_streak30','Advanced: 30-day streak','Advanced','🥇','30-day streak'],
 ['advanced_volume','Advanced: Volume milestones','Advanced','🥇','Volume milestones'],
 ['elite_100','Elite: 100 workouts','Elite','🔥','100 workouts'],
 ['elite_streak60','Elite: 60-day streak','Elite','🔥','60-day streak'],
 ['elite_year','Elite: 1-year consistency','Elite','🔥','1-year consistency']
];
 foreach($badges as $b){ q("INSERT INTO badges(code,name,tier,icon,description) VALUES(?,?,?,?,?)","sssss",$b); } }
seed_exercises(); seed_badges();
function weekly_workouts($uid){ $stmt = q("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)","i",[$uid]); $row=fetch_one($stmt); return (int)($row['c']??0); }
function frequency_rank_label($uid){ $w=weekly_workouts($uid); if($w>=5) return 'Elite'; if($w>=4) return 'Dedicated'; if($w>=3) return 'Consistent'; if($w>=2) return 'Active'; if($w>=1) return 'Casual'; return 'Inactive'; }
function current_streak($uid){ $dates = fetch_all(q("SELECT date FROM workouts WHERE user_id=? ORDER BY date DESC","i",[$uid])); $streak=0; $day= new DateTime(); foreach($dates as $d){ $wd = DateTime::createFromFormat('Y-m-d',$d['date']); if(!$wd) break; if($wd->format('Y-m-d') === $day->format('Y-m-d')){ $streak++; $day->modify('-1 day'); } else break; } return $streak; }
function total_workouts($uid){ $row = fetch_one(q("SELECT COUNT(*) c FROM workouts WHERE user_id=?","i",[$uid])); return (int)($row['c']??0); }
function lifetime_volume($uid){ $row = fetch_one(q("SELECT COALESCE(SUM(total_volume),0) v FROM workouts WHERE user_id=?","i",[$uid])); return (float)($row['v']??0); }
function best_one_rm($uid,$exercise_id){ $row = fetch_one(q("SELECT MAX(weight*(1+(reps/30))) m FROM sets s JOIN workout_exercises we ON we.id=s.workout_exercise_id JOIN workouts w ON w.id=we.workout_id WHERE w.user_id=? AND we.exercise_id=?","ii",[$uid,$exercise_id])); return $row && $row['m']? round($row['m'],2):0; }
function check_unlock_badges($uid,$newEvents=[]){ $tw = total_workouts($uid); $st = current_streak($uid); $lv = lifetime_volume($uid);
 $prCount = fetch_one(q("SELECT COUNT(*) c FROM user_points WHERE user_id=? AND reason='PR'","i",[$uid]))['c'] ?? 0;
 $toCheck = [ 'beginner_first' => ($tw>=1), 'beginner_5'=>($tw>=5), 'beginner_streak3'=>($st>=3), 'intermediate_20'=>($tw>=20), 'intermediate_streak7'=>($st>=7), 'intermediate_pr5'=>($prCount>=5), 'advanced_50'=>($tw>=50), 'advanced_streak30'=>($st>=30), 'advanced_volume'=>($lv>=50000), 'elite_100'=>($tw>=100), 'elite_streak60'=>($st>=60), 'elite_year'=>($tw>=260) ];
 $unlocked = fetch_all(q("SELECT b.code FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=?","i",[$uid]));
 $have = array_map(function($r){return $r['code'];},$unlocked);
 $newUnlocked=[];
 foreach($toCheck as $code=>$ok){ if(!$ok) continue; if(in_array($code,$have)) continue; $b = fetch_one(q("SELECT id FROM badges WHERE code=?","s",[$code])); if($b){ q("INSERT IGNORE INTO user_badges(user_id,badge_id) VALUES(?,?)","ii",[$uid,$b['id']]); $newUnlocked[]=$code; } }
 return $newUnlocked;
}
function handle_upload($field,$subdir){ if(!isset($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK) return null; ensure_upload_dir(__DIR__."/uploads/$subdir"); $ext = pathinfo($_FILES[$field]['name'],PATHINFO_EXTENSION) ?: 'jpg'; $name = $subdir.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext; $dest = __DIR__."/uploads/$subdir/$name"; move_uploaded_file($_FILES[$field]['tmp_name'],$dest); return "uploads/$subdir/$name"; }
$page = $_GET['page'] ?? 'dashboard';
$notice = '';
if(isset($_POST['action'])){
  if($_POST['action']==='register'){ $email=trim($_POST['email']??''); $pwd=$_POST['password']??''; $name=trim($_POST['name']??''); $weight=$_POST['weight']??null; $goal=trim($_POST['goal']??''); if($email && $pwd){ $hash=password_hash($pwd,PASSWORD_DEFAULT); $stmt=q("INSERT INTO users(email,password,name,weight,goal) VALUES(?,?,?,?,?)","sssss",[$email,$hash,$name,$weight,$goal]); if($stmt){ $uid=$stmt->insert_id; $_SESSION['uid']=$uid; add_points($uid,0,'init'); header('Location: ?page=dashboard'); exit; } else { $notice='Registration failed'; } } }
  if($_POST['action']==='login'){ $email=trim($_POST['email']??''); $pwd=$_POST['password']??''; $u = fetch_one(q("SELECT * FROM users WHERE email=?","s",[$email])); if($u && password_ok($pwd,$u['password'])){ $_SESSION['uid']=$u['id']; header('Location: ?page=dashboard'); exit; } else { $notice='Invalid credentials'; } }
  if($_POST['action']==='logout'){ session_destroy(); header('Location: ?page=login'); exit; }
  if($_POST['action']==='profile_save'){ require_auth(); $uid=auth_user(); $name=trim($_POST['name']??''); $weight=$_POST['weight']??null; $goal=trim($_POST['goal']??''); $pic = handle_upload('profile_pic','profiles'); if($pic){ q("UPDATE users SET name=?,weight=?,goal=?,profile_pic=? WHERE id=?","sdssi",[$name,$weight,$goal,$pic,$uid]); } else { q("UPDATE users SET name=?,weight=?,goal=? WHERE id=?","sdsi",[$name,$weight,$goal,$uid]); } $notice='Profile updated'; }
  if($_POST['action']==='change_password'){ require_auth(); $uid=auth_user(); $old=$_POST['old']??''; $new=$_POST['new']??''; $u = fetch_one(q("SELECT password FROM users WHERE id=?","i",[$uid])); if($u && password_ok($old,$u['password']) && strlen($new)>=6){ $hash=password_hash($new,PASSWORD_DEFAULT); q("UPDATE users SET password=? WHERE id=?","si",[$hash,$uid]); $notice='Password changed'; } else { $notice='Password change failed'; } }
  if($_POST['action']==='exercise_add'){ require_auth(); $uid=auth_user(); $name=trim($_POST['name']??''); $mg=trim($_POST['muscle_group']??'Other'); if($name){ q("INSERT INTO exercises(name,muscle_group,is_custom,user_id) VALUES(?,?,1,?)","ssi",[$name,$mg,$uid]); $notice='Exercise added'; } }
  if($_POST['action']==='exercise_fav'){ require_auth(); $uid=auth_user(); $id=(int)($_POST['exercise_id']??0); $ex = fetch_one(q("SELECT id,is_favorite FROM exercises WHERE id=?","i",[$id])); if($ex){ $nf = $ex['is_favorite']?0:1; q("UPDATE exercises SET is_favorite=? WHERE id=?","ii",[$nf,$id]); } }
  if($_POST['action']==='workout_new'){ require_auth(); $uid=auth_user(); $date=$_POST['date']??date('Y-m-d'); $notes=trim($_POST['notes']??''); q("INSERT INTO workouts(user_id,date,notes) VALUES(?,?,?)","iss",[$uid,$date,$notes]); add_points($uid,10,'Workout'); $new = check_unlock_badges($uid); if($new){ $_SESSION['new_badges']=$new; } }
  if($_POST['action']==='workout_delete'){ require_auth(); $uid=auth_user(); $wid=(int)($_POST['workout_id']??0); q("DELETE FROM workouts WHERE id=? AND user_id=?","ii",[$wid,$uid]); }
  if($_POST['action']==='workout_duplicate'){ require_auth(); $uid=auth_user(); $last = fetch_one(q("SELECT * FROM workouts WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 1","i",[$uid])); if($last){ q("INSERT INTO workouts(user_id,date,notes) VALUES(?,?,?)","iss",[$uid,date('Y-m-d'),$last['notes']]); $newWid = $mysqli->insert_id; $wes = fetch_all(q("SELECT * FROM workout_exercises WHERE workout_id=?","i",[$last['id']])); foreach($wes as $we){ q("INSERT INTO workout_exercises(workout_id,exercise_id) VALUES(?,?)","ii",[$newWid,$we['exercise_id']]); $newWeId=$mysqli->insert_id; $sets = fetch_all(q("SELECT reps,weight FROM sets WHERE workout_exercise_id=?","i",[$we['id']])); foreach($sets as $s){ q("INSERT INTO sets(workout_exercise_id,reps,weight) VALUES(?,?,?)","idd",[$newWeId,$s['reps'],$s['weight']]); } } add_points($uid,10,'Workout'); $new = check_unlock_badges($uid); if($new){ $_SESSION['new_badges']=$new; } }
  if($_POST['action']==='workout_add_ex'){ require_auth(); $uid=auth_user(); $wid=(int)($_POST['workout_id']??0); $eid=(int)($_POST['exercise_id']??0); if($wid && $eid){ q("INSERT INTO workout_exercises(workout_id,exercise_id) VALUES(?,?)","ii",[$wid,$eid]); } }
  if($_POST['action']==='set_add'){ require_auth(); $uid=auth_user(); $weid=(int)($_POST['workout_exercise_id']??0); $reps=(int)($_POST['reps']??0); $weight=(float)($_POST['weight']??0); if($weid && $reps>0 && $weight>0){ q("INSERT INTO sets(workout_exercise_id,reps,weight) VALUES(?,?,?)","idd",[$weid,$reps,$weight]); $we = fetch_one(q("SELECT workout_id,exercise_id FROM workout_exercises WHERE id=?","i",[$weid])); if($we){ $best = best_one_rm($uid,$we['exercise_id']); $cur = one_rm($weight,$reps); if($cur>$best){ add_points($uid,5,'PR'); $_SESSION['new_badges']=check_unlock_badges($uid); } $wid=$we['workout_id']; $sets=fetch_all(q("SELECT reps,weight FROM sets WHERE workout_exercise_id IN (SELECT id FROM workout_exercises WHERE workout_id=?)","i",[$wid])); $volume = workout_volume($sets); q("UPDATE workouts SET total_volume=? WHERE id=?","di",[$volume,$wid]); } } }
  if($_POST['action']==='habit_log'){ require_auth(); $uid=auth_user(); $date=$_POST['date']??date('Y-m-d'); $water=(int)($_POST['water_ml']??0); $sleep=(float)($_POST['sleep_hours']??0); $protein=(int)($_POST['protein_g']??0); $existing = fetch_one(q("SELECT id FROM habits WHERE user_id=? AND date=?","is",[$uid,$date])); if($existing){ q("UPDATE habits SET water_ml=?,sleep_hours=?,protein_g=? WHERE id=?","iddi",[$water,$sleep,$protein,$existing['id']]); } else { q("INSERT INTO habits(user_id,date,water_ml,sleep_hours,protein_g) VALUES(?,?,?,?,?)","isidi",[$uid,$date,$water,$sleep,$protein]); add_points($uid,1,'Habit'); } $_SESSION['new_badges']=check_unlock_badges($uid); }
  if($_POST['action']==='body_log'){ require_auth(); $uid=auth_user(); $date=$_POST['date']??date('Y-m-d'); $weight=$_POST['weight']??null; $neck=$_POST['neck']??null; $chest=$_POST['chest']??null; $waist=$_POST['waist']??null; $hips=$_POST['hips']??null; $arm=$_POST['arm']??null; $thigh=$_POST['thigh']??null; $photo=handle_upload('photo','progress'); $existing = fetch_one(q("SELECT id FROM body_logs WHERE user_id=? AND date=?","is",[$uid,$date])); if($existing){ q("UPDATE body_logs SET weight=?,neck=?,chest=?,waist=?,hips=?,arm=?,thigh=?,photo_path=? WHERE id=?","ddddddddi",[$weight,$neck,$chest,$waist,$hips,$arm,$thigh,$photo,$existing['id']]); } else { q("INSERT INTO body_logs(user_id,date,weight,neck,chest,waist,hips,arm,thigh,photo_path) VALUES(?,?,?,?,?,?,?,?,?,?,?)","isdddddddds",[$uid,$date,$weight,$neck,$chest,$waist,$hips,$arm,$thigh,$photo]); } }
  }}
$uid = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>BruteMode</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #0f0f10; color: #e6e6e6; }
.navbar { background: linear-gradient(90deg,#000,#111); }
.card.bg-secondary { background-color: #212529 !important; border: 1px solid #2b2f33; }
.btn-primary { background-color: #0d6efd; border-color: #0d6efd; }
.btn-warning { background-color: #f0ad4e; border-color: #f0ad4e; color: #111; }
.btn-danger { background-color: #dc3545; border-color: #dc3545; }
.table-dark { --bs-table-bg: #121416; }
.progress { background-color: #1b1d20; }
.progress-bar { color: #111; }
.bg-black { background-color: #000 !important; }
.rounded { border-radius: .5rem !important; }
.display-6 { font-weight: 700; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-dark text-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="?page=dashboard">BruteMode</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if($uid){ ?>
        <li class="nav-item"><a class="nav-link" href="?page=workouts">Workouts</a></li>
        <li class="nav-item"><a class="nav-link" href="?page=exercises">Exercises</a></li>
        <li class="nav-item"><a class="nav-link" href="?page=habits">Habits</a></li>
        <li class="nav-item"><a class="nav-link" href="?page=body">Body</a></li>
        <li class="nav-item"><a class="nav-link" href="?page=badges">Badges</a></li>
        <?php } ?>
      </ul>
      <ul class="navbar-nav">
        <?php if($uid){ $u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid])); ?>
          <li class="nav-item"><span class="nav-link">Rank: <?php echo sanitize($u['rank']); ?> · Points: <?php echo (int)$u['points']; ?></span></li>
          <li class="nav-item"><a class="nav-link" href="?page=profile">Profile</a></li>
          <li class="nav-item">
            <form method="post" class="d-inline"><input type="hidden" name="action" value="logout"><button class="btn btn-sm btn-outline-danger">Logout</button></form>
          </li>
        <?php } else { ?>
          <li class="nav-item"><a class="nav-link" href="?page=login">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="?page=register">Register</a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container py-3">
<?php if($notice){ echo '<div class="alert alert-info">'.$notice.'</div>'; } ?>
<?php if(isset($_SESSION['new_badges']) && is_array($_SESSION['new_badges']) && count($_SESSION['new_badges'])){ $codes=$_SESSION['new_badges']; $_SESSION['new_badges']=[]; ?>
  <div class="alert alert-success text-dark fw-bold">Badge unlocked: <?php echo implode(', ',$codes); ?></div>
<?php } ?>
<?php
if($page==='login'){ ?>
  <div class="row justify-content-center"><div class="col-md-4"><div class="card bg-secondary text-light"><div class="card-body">
  <h4 class="mb-3">Login</h4>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <div class="mb-2"><label>Email</label><input class="form-control" name="email" type="email" required></div>
    <div class="mb-2"><label>Password</label><input class="form-control" name="password" type="password" required></div>
    <button class="btn btn-primary w-100">Login</button>
  </form>
  <div class="mt-3"><a href="?page=register" class="link-light">Create account</a></div>
  </div></div></div></div>
<?php } elseif($page==='register'){ ?>
  <div class="row justify-content-center"><div class="col-md-5"><div class="card bg-secondary text-light"><div class="card-body">
  <h4 class="mb-3">Register</h4>
  <form method="post">
    <input type="hidden" name="action" value="register">
    <div class="mb-2"><label>Name</label><input class="form-control" name="name" required></div>
    <div class="mb-2"><label>Email</label><input class="form-control" name="email" type="email" required></div>
    <div class="mb-2"><label>Password</label><input class="form-control" name="password" type="password" required></div>
    <div class="mb-2"><label>Weight</label><input class="form-control" name="weight" type="number" step="0.1"></div>
    <div class="mb-2"><label>Goal</label><input class="form-control" name="goal"></div>
    <button class="btn btn-primary w-100">Create</button>
  </form>
  </div></div></div></div>
<?php } elseif($page==='profile'){ require_auth(); $u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid])); ?>
  <div class="row">
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h5 class="mb-3">Profile</h5>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="profile_save">
        <div class="mb-2"><label>Name</label><input class="form-control" name="name" value="<?php echo sanitize($u['name']); ?>"></div>
        <div class="mb-2"><label>Weight</label><input class="form-control" name="weight" type="number" step="0.1" value="<?php echo sanitize($u['weight']); ?>"></div>
        <div class="mb-2"><label>Goal</label><input class="form-control" name="goal" value="<?php echo sanitize($u['goal']); ?>"></div>
        <div class="mb-2"><label>Profile picture</label><input class="form-control" name="profile_pic" type="file"></div>
        <?php if($u['profile_pic']){ echo '<img class="rounded mt-2" style="max-width:120px" src="'.sanitize($u['profile_pic']).'">'; } ?>
        <button class="btn btn-primary mt-2">Save</button>
      </form>
    </div></div></div>
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h5 class="mb-3">Change Password</h5>
      <form method="post">
        <input type="hidden" name="action" value="change_password">
        <div class="mb-2"><label>Current</label><input class="form-control" name="old" type="password" required></div>
        <div class="mb-2"><label>New</label><input class="form-control" name="new" type="password" required></div>
        <button class="btn btn-warning">Change</button>
      </form>
    </div></div></div>
  </div>
<?php } elseif($page==='exercises'){ require_auth(); $qstr=trim($_GET['q']??''); $mg=trim($_GET['mg']??''); $sql="SELECT * FROM exercises WHERE (user_id IS NULL OR user_id=?)"; $types="i"; $params=[$uid]; if($qstr){ $sql.=" AND name LIKE ?"; $types.="s"; $params[]="%$qstr%"; } if($mg){ $sql.=" AND muscle_group=?"; $types.="s"; $params[]=$mg; } $list=fetch_all(q($sql." ORDER BY is_favorite DESC, name",$types,$params)); ?>
  <div class="d-flex justify-content-between align-items-center mb-2"><h5>Exercise Library</h5>
    <form class="d-flex" method="get"><input type="hidden" name="page" value="exercises"><input class="form-control me-2" name="q" placeholder="Search" value="<?php echo sanitize($qstr); ?>"><select class="form-select me-2" name="mg"><option value="">All</option><option>Chest</option><option>Back</option><option>Legs</option><option>Shoulders</option><option>Arms</option><option>Core</option><option>Glutes</option></select><button class="btn btn-outline-light">Filter</button></form>
  </div>
  <div class="row">
    <div class="col-md-7">
      <table class="table table-dark table-striped"><thead><tr><th>Name</th><th>Group</th><th>Fav</th></tr></thead><tbody>
      <?php foreach($list as $e){ ?>
        <tr><td><?php echo sanitize($e['name']); ?></td><td><?php echo sanitize($e['muscle_group']); ?></td>
        <td>
          <form method="post">
            <input type="hidden" name="action" value="exercise_fav">
            <input type="hidden" name="exercise_id" value="<?php echo $e['id']; ?>">
            <button class="btn btn-sm <?php echo $e['is_favorite']?'btn-warning':'btn-outline-light'; ?>"><?php echo $e['is_favorite']?'★':'☆'; ?></button>
          </form>
        </td></tr>
      <?php } ?>
      </tbody></table>
    </div>
    <div class="col-md-5"><div class="card bg-secondary"><div class="card-body">
      <h6>Add Custom Exercise</h6>
      <form method="post">
        <input type="hidden" name="action" value="exercise_add">
        <div class="mb-2"><input class="form-control" name="name" placeholder="Name" required></div>
        <div class="mb-2"><select class="form-select" name="muscle_group"><option>Chest</option><option>Back</option><option>Legs</option><option>Shoulders</option><option>Arms</option><option>Core</option><option>Glutes</option><option>Other</option></select></div>
        <button class="btn btn-primary">Add</button>
      </form>
    </div></div></div>
  </div>
<?php } elseif($page==='workouts'){ require_auth(); $from=$_GET['from']??''; $to=$_GET['to']??''; $sql="SELECT * FROM workouts WHERE user_id=?"; $types="i"; $params=[$uid]; if($from){ $sql.=" AND date>=?"; $types.="s"; $params[]=$from; } if($to){ $sql.=" AND date<=?"; $types.="s"; $params[]=$to; } $wlist=fetch_all(q($sql." ORDER BY date DESC",$types,$params)); $exAll=fetch_all(q("SELECT * FROM exercises WHERE (user_id IS NULL OR user_id=?) ORDER BY name","i",[$uid])); ?>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5>Workouts</h5>
    <form method="post" class="d-flex"><input type="hidden" name="action" value="workout_new"><input class="form-control me-2" type="date" name="date" value="<?php echo date('Y-m-d'); ?>"><input class="form-control me-2" name="notes" placeholder="Notes"><button class="btn btn-primary">New Workout</button></form>
  </div>
  <div class="mb-3">
    <form class="d-flex" method="get">
      <input type="hidden" name="page" value="workouts">
      <input class="form-control me-2" type="date" name="from" value="<?php echo sanitize($from); ?>">
      <input class="form-control me-2" type="date" name="to" value="<?php echo sanitize($to); ?>">
      <button class="btn btn-outline-light">Filter</button>
    </form>
  </div>
  <div class="row">
  <?php foreach($wlist as $w){ $wes=fetch_all(q("SELECT we.*, e.name FROM workout_exercises we JOIN exercises e ON e.id=we.exercise_id WHERE we.workout_id=?","i",[$w['id']])); ?>
    <div class="col-md-6 mb-3"><div class="card bg-secondary"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div><h6 class="mb-0"><?php echo sanitize($w['date']); ?></h6><small><?php echo sanitize($w['notes']); ?></small></div>
        <div>
          <form method="post" class="d-inline"><input type="hidden" name="action" value="workout_duplicate"><button class="btn btn-sm btn-warning">Duplicate</button></form>
          <form method="post" class="d-inline ms-1"><input type="hidden" name="action" value="workout_delete"><input type="hidden" name="workout_id" value="<?php echo $w['id']; ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </div>
      </div>
      <div class="mt-2">Total Volume: <?php echo number_format((float)$w['total_volume'],2); ?></div>
      <div class="mt-2">
        <form method="post" class="d-flex">
          <input type="hidden" name="action" value="workout_add_ex">
          <input type="hidden" name="workout_id" value="<?php echo $w['id']; ?>">
          <select class="form-select me-2" name="exercise_id">
            <?php foreach($exAll as $e){ echo '<option value="'.$e['id'].'">'.sanitize($e['name']).'</option>'; } ?>
          </select>
          <button class="btn btn-sm btn-primary">Add Exercise</button>
        </form>
      </div>
      <?php foreach($wes as $we){ $sets=fetch_all(q("SELECT * FROM sets WHERE workout_exercise_id=? ORDER BY id","i",[$we['id']])); ?>
        <div class="mt-3 p-2 bg-dark rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold"><?php echo sanitize($we['name']); ?></div>
          </div>
          <table class="table table-dark table-sm"><thead><tr><th>Reps</th><th>Weight</th><th>1RM est</th></tr></thead><tbody>
          <?php foreach($sets as $s){ echo '<tr><td>'.$s['reps'].'</td><td>'.$s['weight'].'</td><td>'.one_rm($s['weight'],$s['reps']).'</td></tr>'; } ?>
          </tbody></table>
          <form method="post" class="d-flex">
            <input type="hidden" name="action" value="set_add">
            <input type="hidden" name="workout_exercise_id" value="<?php echo $we['id']; ?>">
            <input class="form-control me-2" type="number" name="reps" placeholder="Reps" min="1" required>
            <input class="form-control me-2" type="number" step="0.5" name="weight" placeholder="Weight" min="0" required>
            <button class="btn btn-sm btn-success">Add Set</button>
          </form>
        </div>
      <?php } ?>
    </div></div></div>
  <?php } ?>
  </div>
<?php } elseif($page==='badges'){ require_auth(); $all=fetch_all(q("SELECT * FROM badges ORDER BY tier,name")); $got=fetch_all(q("SELECT b.code FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=?","i",[$uid])); $have=array_map(function($r){return $r['code'];},$got); ?>
  <h5>Badges</h5>
  <div class="row">
  <?php foreach($all as $b){ $unlocked = in_array($b['code'],$have); ?>
    <div class="col-md-3 mb-3"><div class="card <?php echo $unlocked?'bg-success text-dark':'bg-secondary'; ?>"><div class="card-body">
      <div class="display-6"><?php echo sanitize($b['icon']); ?></div>
      <div class="fw-bold"><?php echo sanitize($b['name']); ?></div>
      <div><?php echo sanitize($b['description']); ?></div>
      <div class="small">Tier: <?php echo sanitize($b['tier']); ?></div>
      <div class="mt-2"><?php echo $unlocked?'Unlocked':'Locked'; ?></div>
    </div></div></div>
  <?php } ?>
  </div>
<?php } elseif($page==='habits'){ require_auth(); $today=date('Y-m-d'); $h=fetch_one(q("SELECT * FROM habits WHERE user_id=? AND date=?","is",[$uid,$today])); $streak=current_streak($uid); ?>
  <div class="row">
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h6>Daily Habits</h6>
      <form method="post">
        <input type="hidden" name="action" value="habit_log">
        <div class="mb-2"><label>Water (ml)</label><input class="form-control" type="number" name="water_ml" value="<?php echo (int)($h['water_ml']??0); ?>"></div>
        <div class="mb-2"><label>Sleep (hours)</label><input class="form-control" type="number" step="0.25" name="sleep_hours" value="<?php echo sanitize($h['sleep_hours']??0); ?>"></div>
        <div class="mb-2"><label>Protein (g)</label><input class="form-control" type="number" name="protein_g" value="<?php echo (int)($h['protein_g']??0); ?>"></div>
        <button class="btn btn-primary">Save</button>
      </form>
    </div></div></div>
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h6>Weekly Frequency Rank</h6>
      <div class="display-6"><?php echo frequency_rank_label($uid); ?></div>
      <div>This week workouts: <?php echo weekly_workouts($uid); ?></div>
    </div></div></div>
  </div>
<?php } elseif($page==='body'){ require_auth(); $today=date('Y-m-d'); $logs=fetch_all(q("SELECT * FROM body_logs WHERE user_id=? ORDER BY date DESC LIMIT 14","i",[$uid])); ?>
  <div class="row">
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h6>Body Log</h6>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="body_log">
        <div class="mb-2"><label>Date</label><input class="form-control" type="date" name="date" value="<?php echo date('Y-m-d'); ?>"></div>
        <div class="mb-2"><label>Weight</label><input class="form-control" type="number" step="0.1" name="weight"></div>
        <div class="mb-2"><label>Neck</label><input class="form-control" type="number" step="0.1" name="neck"></div>
        <div class="mb-2"><label>Chest</label><input class="form-control" type="number" step="0.1" name="chest"></div>
        <div class="mb-2"><label>Waist</label><input class="form-control" type="number" step="0.1" name="waist"></div>
        <div class="mb-2"><label>Hips</label><input class="form-control" type="number" step="0.1" name="hips"></div>
        <div class="mb-2"><label>Arm</label><input class="form-control" type="number" step="0.1" name="arm"></div>
        <div class="mb-2"><label>Thigh</label><input class="form-control" type="number" step="0.1" name="thigh"></div>
        <div class="mb-2"><label>Photo</label><input class="form-control" type="file" name="photo"></div>
        <button class="btn btn-primary">Save</button>
      </form>
    </div></div></div>
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <h6>Recent Logs</h6>
      <table class="table table-dark table-sm"><thead><tr><th>Date</th><th>Weight</th><th>Photo</th></tr></thead><tbody>
      <?php foreach($logs as $l){ echo '<tr><td>'.sanitize($l['date']).'</td><td>'.sanitize($l['weight']).'</td><td>'.($l['photo_path']?'<img src="'.sanitize($l['photo_path']).'" style="max-width:80px">':'').'</td></tr>'; } ?>
      </tbody></table>
      <canvas id="weightChart" height="160"></canvas>
      <script>
        const wc = document.getElementById('weightChart');
        if(wc){
          const labels = [<?php foreach(array_reverse($logs) as $l){ echo "'".sanitize($l['date'])."',"; } ?>];
          const data = [<?php foreach(array_reverse($logs) as $l){ echo ($l['weight']?:'null').','; } ?>];
          new Chart(wc,{type:'line',data:{labels,datasets:[{label:'Weight',data,borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,0.2)'}]},options:{plugins:{legend:{labels:{color:'#fff'}}},scales:{x:{ticks:{color:'#ccc'}},y:{ticks:{color:'#ccc'}}}}});
        }
      </script>
    </div></div></div>
  </div>
<?php } elseif($page==='dashboard'){ if(!$uid){ echo '<div class="text-center py-5"><h3>Welcome to BruteMode</h3><p><a class="btn btn-primary" href="?page=register">Get Started</a></p></div>'; } else { $u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid])); $tw=total_workouts($uid); $ww=weekly_workouts($uid); $st=current_streak($uid); $lv=lifetime_volume($uid); $latestBadge = fetch_one(q("SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.unlocked_at DESC LIMIT 1","i",[$uid])); $recentW = fetch_all(q("SELECT * FROM workouts WHERE user_id=? ORDER BY date DESC LIMIT 7","i",[$uid])); ?>
  <div class="row">
    <div class="col-md-3"><div class="card bg-secondary"><div class="card-body"><div class="fw-bold">Total Workouts</div><div class="display-6"><?php echo $tw; ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary"><div class="card-body"><div class="fw-bold">This Week</div><div class="display-6"><?php echo $ww; ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary"><div class="card-body"><div class="fw-bold">Streak</div><div class="display-6"><?php echo $st; ?> days</div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary"><div class="card-body"><div class="fw-bold">Lifetime Volume</div><div class="display-6"><?php echo number_format($lv,0); ?></div></div></div></div>
  </div>
  <div class="row mt-3">
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <div class="fw-bold">Rank Progress</div>
      <?php $p=$u['points']; $next=100; $label='Warrior'; if($p<100){ $base=0; $next=100; $label='Warrior'; } elseif($p<300){ $base=100; $next=300; $label='Gladiator'; } elseif($p<600){ $base=300; $next=600; $label='Dominator'; } elseif($p<1000){ $base=600; $next=1000; $label='Warlord'; } elseif($p<1500){ $base=1000; $next=1500; $label='Titan'; } else { $base=1500; $next=2000; $label='Legend'; } $pct=min(100, max(0, round((($p-$base)/($next-$base))*100))); ?>
      <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%"><?php echo $pct; ?>%</div></div>
      <div class="small mt-1">Next: <?php echo $label; ?></div>
    </div></div></div>
    <div class="col-md-6"><div class="card bg-secondary"><div class="card-body">
      <div class="fw-bold">Latest Badge</div>
      <?php if($latestBadge){ echo '<div class="h1">'.$latestBadge['icon'].'</div><div>'.$latestBadge['name'].'</div>'; } else { echo 'None yet'; } ?>
    </div></div></div>
  </div>
  <div class="row mt-3">
    <div class="col-md-12"><div class="card bg-secondary"><div class="card-body">
      <h6>Weekly Summary</h6>
      <canvas id="weeklyChart" height="120"></canvas>
      <script>
        const wctx = document.getElementById('weeklyChart');
        if(wctx){
          const labels = [<?php for($i=6;$i>=0;$i--){ $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); echo "'$d',"; } ?>];
          const data = [<?php for($i=6;$i>=0;$i--){ $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); $c=fetch_one(q("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND date=?","is",[$uid,$d]))['c']??0; echo $c.','; } ?>];
          new Chart(wctx,{type:'bar',data:{labels,datasets:[{label:'Workouts',data,backgroundColor:'#0d6efd'}]},options:{plugins:{legend:{labels:{color:'#fff'}}},scales:{x:{ticks:{color:'#ccc'}},y:{ticks:{color:'#ccc'},beginAtZero:true,precision:0}}}});
        }
      </script>
    </div></div></div>
  </div>
  <div class="row mt-3">
    <div class="col-md-12"><div class="card bg-secondary"><div class="card-body">
      <h6>Export</h6>
      <a class="btn btn-outline-light" href="?page=export">Printable Workout Log</a>
    </div></div></div>
  </div>
<?php } } elseif($page==='export'){ require_auth(); $ws=fetch_all(q("SELECT * FROM workouts WHERE user_id=? ORDER BY date","i",[$uid])); ?>
  <div class="bg-white text-dark p-3 rounded">
    <h3>Workout History</h3>
    <table class="table table-striped"><thead><tr><th>Date</th><th>Notes</th><th>Volume</th></tr></thead><tbody>
    <?php foreach($ws as $w){ echo '<tr><td>'.$w['date'].'</td><td>'.sanitize($w['notes']).'</td><td>'.number_format((float)$w['total_volume'],2).'</td></tr>'; } ?>
    </tbody></table>
    <p>Use your browser Print to save as PDF.</p>
    <button class="btn btn-primary" onclick="window.print()">Print</button>
  </div>
<?php } else { echo '<div>Page not found</div>'; }
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const alerts = document.querySelectorAll('.alert-success');
  alerts.forEach(a=>{
    a.style.transition='transform 0.3s ease, opacity 0.3s ease';
    a.style.transform='scale(1.05)';
    setTimeout(()=>{ a.style.transform='scale(1)'; },200);
    setTimeout(()=>{ a.style.opacity='0'; a.style.transform='translateY(-10px)'; },4000);
    setTimeout(()=>{ a.remove(); },4500);
  });
});
</script>
</body>
</html>
