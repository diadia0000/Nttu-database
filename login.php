<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$projectUrl = $_ENV['SUPABASE_URL'] ?? '';
$apiKey = $_ENV['SUPABASE_API_KEY'] ?? '';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $authUrl = "$projectUrl/auth/v1/token?grant_type=password";

    $loginData = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apiKey",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['access_token'])) {
        // 登入成功
        $_SESSION['access_token'] = $responseData['access_token'];
        $_SESSION['user'] = $responseData['user'];

        header('Location: index.php'); // 登入後導向主頁
        exit;
    } else {
        // 這裡強化了錯誤訊息顯示
        $errorMsg = $responseData['error_description'] ?? '未知錯誤';
        $message = "❌ 登入失敗：$errorMsg";
    }
}
?>


<!-- HTML 部分與你原本提供的一樣，但修正了一些 ID 錯誤 -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入帳號</title>
    <link rel="stylesheet" href="stylelogin.css">
</head>
<body>
    <div class="auth-container">
      <div class="auth-box">
        <h1>登入帳號</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, '❌') === 0 ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <input type="email" id="email" name="email" class="input-field" placeholder="電子郵件地址" required>
            </div>
            
            <div class="form-group">
                <input type="password" id="password" name="password" class="input-field" placeholder="密碼" required>
            </div>
            
            <button type="submit" id="submitBtn" class="submit-btn">登入</button>
        </form>
        
        <div class="footer-text">
            <a href="register.php">註冊新帳號</a>
            <!--<a href="forgot_password.php">忘記密碼？</a>-->
        </div>
      </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '登入中...';
        });
    </script>
</body>
</html>
