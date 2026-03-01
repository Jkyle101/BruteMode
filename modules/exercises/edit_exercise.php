<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$id=(int)($_POST['exercise_id']??0);
if($id){
  $ex = fetch_one(q("SELECT id,is_favorite FROM exercises WHERE id=?","i",[$id]));
  if($ex){
    $nf = $ex['is_favorite']?0:1;
    q("UPDATE exercises SET is_favorite=? WHERE id=?","ii",[$nf,$id]);
  }
}
header('Location: /BruteMode/modules/exercises/list_exercises.php');
exit;
