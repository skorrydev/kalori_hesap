<?php
require_once __DIR__ . '/../config/helpers.php';
session_destroy();
header('Location: /login.php');
exit;
