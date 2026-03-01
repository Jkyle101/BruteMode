<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$uid=auth_user();
$wid=(int)($_POST['workout_id']??0);
if($wid){
  q("DELETE FROM workouts WHERE id=? AND user_id=?","ii",[$wid,$uid]);
}
header('Location: /BruteMode/modules/workouts/view_workout.php');
exit;
