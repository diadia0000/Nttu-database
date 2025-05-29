<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabaseUrl = $_ENV['SUPABASE_URL'];
$apiKey = $_ENV['SUPABASE_API_KEY'];
$table = 'users'; // 你的資料表名稱

function insertUserToDatabase($supabaseUrl, $apiKey, $table, $username, $email, $hashedPassword, $avatar) {
    $insertUrl = "$supabaseUrl/rest/v1/$table";
    $payload = json_encode([
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'avatar' => $avatar
    ]);

    $ch = curl_init($insertUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: $apiKey",
        "Authorization: Bearer $apiKey",
        "Prefer: return=representation"
    ]);
    curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [$httpCode, $response, $error];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $rawPassword = $_POST['password'];
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    $avatar = null;

    list($httpCode, $response, $error) = insertUserToDatabase($supabaseUrl, $apiKey, $table, $username, $email, $hashedPassword, $avatar);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✔ 註冊成功";
        echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2000);</script>";
    } else {
        http_response_code(403);
        header('Location: login.php');
        echo "❌ 註冊失敗，HTTP Code: $httpCode<br>Error: $error<br>Response: <pre>$response</pre>";
    }
    exit;
}

?>



<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊帳號</title>
    <link rel="stylesheet" href="stylelogin.css">
</head>
<body>
    <div class="auth-container">
      <div class="auth-box">
        <h1>註冊帳號</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, '✔') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="使用者名稱" required class="input-field">
            </div>

            <div class="form-group">
                <input type="text" id="email" name="email" placeholder="電子郵件地址" required class="input-field">
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="密碼 (至少6個字符)" minlength="6" required class="input-field">
            </div>

            <button type="submit" id="submitBtn" class="submit-btn">註冊</button>
        </form>

        <div class="footer">
            <p class="footer-text">已有帳號？<a href="login.php">點此登入</a></p>
        </div>
      </div>
    </div>

    <script>
        // 防止表單重複提交
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '註冊中...';
        });

        // 密碼強度提示
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const indicator = document.getElementById('password-strength') ||
                document.createElement('div');

            if (!document.getElementById('password-strength')) {
                indicator.id = 'password-strength';
                indicator.style.marginTop = '5px';
                indicator.style.fontSize = '12px';
                e.target.parentNode.appendChild(indicator);
            }

            if (password.length === 0) {
                indicator.textContent = '';
                return;
            }

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const texts = ['非常弱', '弱', '中等', '強', '非常強'];
            const colors = ['#ff0000', '#ff6600', '#ffcc00', '#99cc00', '#009900'];

            indicator.textContent = `密碼強度: ${texts[strength]}`;
            indicator.style.color = colors[strength];
        });
    </script>
</body>
</html>