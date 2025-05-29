<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabaseUrl = $_ENV['SUPABASE_URL'];
$apiKey = $_ENV['SUPABASE_API_KEY'];
$table = 'users';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    http_response_code(403);
    header('Location: login.php');
    exit;
}

// 取出暫存資料
$username = $_SESSION['register_username'];
$email = $_SESSION['register_email'];
$password = $_SESSION['register_password'];

// 寫入 users 資料表
$insertUrl = "$supabaseUrl/rest/v1/$table";
$payload = json_encode([
    'username' => $username,
    'email' => $email,
    'password' => $password,
]);

$ch = curl_init($insertUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json",
    "Prefer: return=representation"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✔ 帳號已驗證並完成註冊，<a href='login.php'>點此登入</a>";
} else {
    http_response_code(403);
    header('Location: login.php');
}

// 清除 session
unset($_SESSION['register_username'], $_SESSION['register_email'], $_SESSION['register_password']);
?>
