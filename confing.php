<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تفعيل نظام تتبع وإظهار الأخطاء لضمان استقرار السيرفر وقراءة البيانات
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// بيانات الاتصال الرسمية والمطابقة للوحة تحكم حسابك الحالية
define('DB_HOST', 'sql306.infinityfree.com'); 
define('DB_USER', 'if0_42124157');           
define('DB_PASS', 'ahmed2026201365');          
define('DB_NAME', 'if0_42124157_db'); 

// إنشاء الاتصال بسيرفر MySQL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// التحقق من سلامة الاتصال
if ($conn->connect_error) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ترميز اللغة العربية المعتمد لضمان ظهور الأسئلة والأسماء بشكل صحيح
$conn->set_charset("utf8mb4");
?>
