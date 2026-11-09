<?php
/**
 * User Logout Action Handler
 * Online Tourism Portal
 */
require_once __DIR__ . '/includes/auth.php';

logoutUser();

setFlash('auth', 'You have been successfully logged out.', 'info');
header('Location: login.php');
exit;
