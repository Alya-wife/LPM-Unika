<?php
require_once __DIR__ . '/includes/auth.php';

// Seamless redirect to unified Siklus AMI (Master Periode tab)
$params = ['tab' => 'periode'];
if (isset($_GET['edit'])) $params['edit_periode'] = $_GET['edit'];
if (isset($_GET['delete'])) $params['delete_periode'] = $_GET['delete'];
if (isset($_GET['toggle'])) $params['toggle_periode'] = $_GET['toggle'];

redirect(SITE_URL . '/admin/ami-siklus-list.php?' . http_build_query($params));
