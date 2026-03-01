<?php
require_once __DIR__ . '/../config/database.php';
if(!auth_user()){
  header('Location: /BruteMode/login.php');
  exit;
}
