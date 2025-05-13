<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
$apiKey = $_ENV['SUPABASE_API_KEY'] ?? '';
$table = 'users'; // 你的資料表名稱

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 查詢 users 資料表
    $queryUrl = "$supabaseUrl/rest/v1/$table?email=eq." . urlencode($email);

    $ch = curl_init($queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apiKey",
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);

    // 設定 SSL 憑證 (使用 cacert.pem)
    curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($responseData)) {
        $user = $responseData[0];

        if (password_verify($password, $user['password'])) {
            // 登入成功
            $_SESSION['user'] = $user;
            header('Location: index.php'); // 登入後導向主頁
            exit;
        } else {
            $message = "❌ 登入失敗：密碼錯誤";
        }
    } else {
        $message = "❌ 登入失敗：帳號不存在";
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
