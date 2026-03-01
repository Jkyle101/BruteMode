<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
$path = handle_upload('photo','progress');
echo $path ? $path : '';
