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

    // 找出目前使用者
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['user']['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "找不到使用者。";
        exit;
    }

    $userId = $user['id'];

    // 查詢與目前使用者有 accepted 狀態的好友（雙向處理）
    $stmt = $pdo->prepare("
        SELECT u.username, u.avatar
        FROM friendships f
        JOIN users u ON (
            (f.friend_id = u.id AND f.user_id = :uid)
            OR (f.user_id = u.id AND f.friend_id = :uid)
        )
        WHERE f.status = 'accepted' AND u.id != :uid
    ");
    $stmt->execute(['uid' => $userId]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($friends)) {
        print("HaHa You have no friend loser");
    }

} catch (PDOException $e) {
    echo "錯誤：" . $e->getMessage();
    exit;
}
?>
