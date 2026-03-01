<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$uid=auth_user();
if(isset($_POST['duplicate_last'])){
  $last = fetch_one(q("SELECT * FROM workouts WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 1","i",[$uid]));
  if($last){
    q("INSERT INTO workouts(user_id,date,notes) VALUES(?,?,?)","iss",[$uid,date('Y-m-d'),$last['notes']]);
    $newWid = $mysqli->insert_id;
    $wes = fetch_all(q("SELECT * FROM workout_exercises WHERE workout_id=?","i",[$last['id']]));
    foreach($wes as $we){
      q("INSERT INTO workout_exercises(workout_id,exercise_id) VALUES(?,?)","ii",[$newWid,$we['exercise_id']]);
      $newWeId=$mysqli->insert_id;
      $sets = fetch_all(q("SELECT reps,weight FROM sets WHERE workout_exercise_id=?","i",[$we['id']]));
      foreach($sets as $s){
        q("INSERT INTO sets(workout_exercise_id,reps,weight) VALUES(?,?,?)","idd",[$newWeId,$s['reps'],$s['weight']]);
      }
    }
    add_points($uid,10,'Workout');
    $_SESSION['new_badges']=check_unlock_badges($uid);
  }
} else {
  $date=$_POST['date']??date('Y-m-d');
  $notes=trim($_POST['notes']??'');
  q("INSERT INTO workouts(user_id,date,notes) VALUES(?,?,?)","iss",[$uid,$date,$notes]);
  add_points($uid,10,'Workout');
  $_SESSION['new_badges']=check_unlock_badges($uid);
}
header('Location: /BruteMode/modules/workouts/view_workout.php');
exit;
