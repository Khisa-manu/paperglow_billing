<?php
/**
 * PaperGlow Billing System - User Logout
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

logoutUser();
setFlash('info', 'You have been safely signed out.');
header('Location: /auth/login.php');
exit;
