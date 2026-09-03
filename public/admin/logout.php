<?php
require_once __DIR__ . '/../../includes/auth.php';

start_admin_session();
logout();
redirect('/admin/login.php');
