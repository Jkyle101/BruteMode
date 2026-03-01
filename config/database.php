<?php
require_once __DIR__ . '/session.php';
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
// ensure weight_unit column
$col = fetch_one(q("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='users' AND COLUMN_NAME='weight_unit'","s",[$dbName]));
if(!$col){ $mysqli->query("ALTER TABLE users ADD COLUMN weight_unit VARCHAR(3) DEFAULT 'kg'"); }
function q($sql,$types='',$params=[]) { global $mysqli; $stmt=$mysqli->prepare($sql); if(!$stmt) return false; if($types){ $stmt->bind_param($types,...$params); } $stmt->execute(); return $stmt; }
function fetch_all($stmt){ $res=$stmt->get_result(); return $res? $res->fetch_all(MYSQLI_ASSOC):[]; }
function fetch_one($stmt){ $res=$stmt->get_result(); return $res? $res->fetch_assoc():null; }
function sanitize($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }
function ensure_upload_dir($dir){ if(!is_dir($dir)) { @mkdir($dir,0777,true); } }
function auth_user(){ return $_SESSION['uid']??null; }
function require_auth(){ if(!auth_user()){ header('Location: /BruteMode/login.php'); exit; } }
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
function check_unlock_badges($uid){ $tw = total_workouts($uid); $st = current_streak($uid); $lv = lifetime_volume($uid);
 $prCount = fetch_one(q("SELECT COUNT(*) c FROM user_points WHERE user_id=? AND reason='PR'","i",[$uid]))['c'] ?? 0;
 $toCheck = [ 'beginner_first' => ($tw>=1), 'beginner_5'=>($tw>=5), 'beginner_streak3'=>($st>=3), 'intermediate_20'=>($tw>=20), 'intermediate_streak7'=>($st>=7), 'intermediate_pr5'=>($prCount>=5), 'advanced_50'=>($tw>=50), 'advanced_streak30'=>($st>=30), 'advanced_volume'=>($lv>=50000), 'elite_100'=>($tw>=100), 'elite_streak60'=>($st>=60), 'elite_year'=>($tw>=260) ];
 $unlocked = fetch_all(q("SELECT b.code FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=?","i",[$uid]));
 $have = array_map(function($r){return $r['code'];},$unlocked);
 $newUnlocked=[];
 foreach($toCheck as $code=>$ok){ if(!$ok) continue; if(in_array($code,$have)) continue; $b = fetch_one(q("SELECT id FROM badges WHERE code=?","s",[$code])); if($b){ q("INSERT IGNORE INTO user_badges(user_id,badge_id) VALUES(?,?)","ii",[$uid,$b['id']]); $newUnlocked[]=$code; } }
 return $newUnlocked;
}
function handle_upload($field,$subdir){ if(!isset($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK) return null; ensure_upload_dir(__DIR__."/../assets/images/uploads/$subdir"); $ext = pathinfo($_FILES[$field]['name'],PATHINFO_EXTENSION) ?: 'jpg'; $name = $subdir.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext; $dest = __DIR__."/../assets/images/uploads/$subdir/$name"; move_uploaded_file($_FILES[$field]['tmp_name'],$dest); return "assets/images/uploads/$subdir/$name"; }
function user_weight_unit($uid=null){ if($uid===null) $uid=auth_user(); if(!$uid) return 'kg'; $row=fetch_one(q("SELECT weight_unit FROM users WHERE id=?","i",[$uid])); $u=$row['weight_unit']??'kg'; return $u==='lb'?'lb':'kg'; }
function convert_kg_to_user($kg,$uid=null){ $unit=user_weight_unit($uid); return $unit==='lb'? round($kg*2.20462,2): round($kg,2); }
function convert_user_to_kg($val,$uid=null){ $unit=user_weight_unit($uid); return $unit==='lb'? round($val/2.20462,4): round($val,4); }
function unit_label($uid=null){ return user_weight_unit($uid); }
