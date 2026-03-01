<?php
require_once __DIR__ . '/config/session.php';
session_destroy();
header('Location: /BruteMode/login.php');
exit;
