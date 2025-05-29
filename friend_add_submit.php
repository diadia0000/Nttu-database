<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    http_response_code(401);
    exit("未登入");
}

$email = $_SESSION['user']['email'];

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

    // 找目前使用者 ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentUser) {
        exit("找不到使用者");
    }
    $currentUserId = $currentUser['id'];

    $friendUsername = trim($_POST['friend_username'] ?? '');
    if (!$friendUsername) {
        exit("請輸入好友使用者名稱");
    }

    // 找對方 ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$friendUsername]);
    $friend = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$friend) {
        exit("找不到該使用者");
    }
    $friendId = $friend['id'];

    if ($friendId == $currentUserId) {
        exit("不能加自己為好友");
    }

    // 檢查是否已是好友
    $stmt = $pdo->prepare("
        SELECT 1 FROM friendships 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
        AND status = 'accepted'
    ");
    $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
    if ($stmt->fetch()) {
        exit("你們已經是好友了");
    }

    // 檢查是否已有邀請（不論誰送的）
    $stmt = $pdo->prepare("
        SELECT status FROM friendships 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        if ($existing['status'] === 'pending') {
            exit("已經有待處理的邀請");
        } else {
            exit("邀請狀態異常，請稍後再試");
        }
    }

    // 發送好友邀請（pending）
    $stmt = $pdo->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$currentUserId, $friendId]);

    echo "好友邀請已送出！";
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    echo "系統錯誤，請稍後再試";
}
