<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$dbuser = $_ENV['DB_USER'];
$dbpassword = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get current user info
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['user']['email']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        die("找不到使用者資料。");
    }

    $userId = $currentUser['id'];
    $username = $currentUser['username'];
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendUsername = trim($_POST['friend_username'] ?? '');

        if ($friendUsername === $username) {
            $message = "❌ 不能加自己為好友！";
        } else {
            // Find friend by username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$friendUsername]);
            $friend = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$friend) {
                $message = "❌ 找不到該用戶。";
            } else {
                $friendId = $friend['id'];

                // Check if a friendship request already exists in either direction
                $stmt = $pdo->prepare("
                    SELECT * FROM friendship 
                    WHERE 
                        (user_id = ? AND friend_id = ?) OR 
                        (user_id = ? AND friend_id = ?)
                ");
                $stmt->execute([$userId, $friendId, $friendId, $userId]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $message = "⚠️ 已經有一筆好友紀錄存在（狀態：" . $existing['status'] . "）。";
                } else {
                    // Insert pending request
                    $stmt = $pdo->prepare("
                        INSERT INTO friendship (user_id, friend_id, status, requested_at, created_at)
                        VALUES (?, ?, 'pending', NOW(), NOW())
                    ");
                    $stmt->execute([$userId, $friendId]);

                    $message = "✅ 已傳送好友請求給：$friendUsername";
                }
            }
        }
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤：" . $e->getMessage());
    $message = "⚠️ 系統錯誤，請稍後再試。";
}
?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>新增好友</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        input { padding: 6px; margin-right: 10px; }
        .message { margin-top: 10px; font-weight: bold; color: #333; }
        a { margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>
<h2>新增好友</h2>

<form method="POST" action="add_friends.php">
    <label>好友帳號：</label>
    <input type="text" name="friend_username" required>
    <button type="submit">送出</button>
</form>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<a href="index.php">⬅ 返回主頁</a>
</body>
</html>
