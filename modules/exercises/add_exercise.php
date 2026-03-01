<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$uid=auth_user();
$name=trim($_POST['name']??'');
$mg=trim($_POST['muscle_group']??'Other');
if($name){ q("INSERT INTO exercises(name,muscle_group,is_custom,user_id) VALUES(?,?,1,?)","ssi",[$name,$mg,$uid]); }
header('Location: /BruteMode/modules/exercises/list_exercises.php');
exit;
