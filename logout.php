<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

/*
 * تسجيل الخروج متاح من روابط GET ومن نماذج POST.
 * لا نعتمد على CSRF لتسجيل الخروج حتى تعمل جميع
 * أزرار وروابط الخروج الموجودة في النظام.
 */

logoutUser();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

redirectTo('/login.php');
