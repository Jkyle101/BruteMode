<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$uid=auth_user();
$action=$_POST['action']??'';
if($action==='add_exercise'){
  $wid=(int)($_POST['workout_id']??0);
  $eid=(int)($_POST['exercise_id']??0);
  if($wid && $eid){ q("INSERT INTO workout_exercises(workout_id,exercise_id) VALUES(?,?)","ii",[$wid,$eid]); }
}
if($action==='add_set'){
  $weid=(int)($_POST['workout_exercise_id']??0);
  $reps=(int)($_POST['reps']??0);
  $weightInput=(float)($_POST['weight']??0);
  $weight=convert_user_to_kg($weightInput,$uid);
  if($weid && $reps>0 && $weight>0){
    q("INSERT INTO sets(workout_exercise_id,reps,weight) VALUES(?,?,?)","idd",[$weid,$reps,$weight]);
    $we = fetch_one(q("SELECT workout_id,exercise_id FROM workout_exercises WHERE id=?","i",[$weid]));
    if($we){
      $best = best_one_rm($uid,$we['exercise_id']);
      $cur = one_rm($weight,$reps);
      if($cur>$best){ add_points($uid,5,'PR'); $_SESSION['new_badges']=check_unlock_badges($uid); }
      $wid=$we['workout_id'];
      $sets=fetch_all(q("SELECT reps,weight FROM sets WHERE workout_exercise_id IN (SELECT id FROM workout_exercises WHERE workout_id=?)","i",[$wid]));
      $volume = workout_volume($sets);
      q("UPDATE workouts SET total_volume=? WHERE id=?","di",[$volume,$wid]);
    }
  }
}
header('Location: /BruteMode/modules/workouts/view_workout.php');
exit;
