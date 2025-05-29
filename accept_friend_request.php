<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$request_id = $_POST['request_id'];

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO("pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 確認請求存在且屬於此使用者
    $stmt = $pdo->prepare("SELECT * FROM friendships WHERE id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }

    // 更新為接受狀態
    $stmt = $pdo->prepare("
        UPDATE friendships 
        SET status = 'accepted', responded_at = NOW(), created_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
