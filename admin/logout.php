<?php
require_once __DIR__ . '/../config/database.php';
session_start();
session_destroy();
redirect(SITE_URL . '/admin/index.php');
