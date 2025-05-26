<?php
header('Content-Type: application/json');

// 載入 .env 檔案的設定
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 從 .env 檔案中取得資料庫連線設定
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$dbuser = $_ENV['DB_USER'];
$dbpassword = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 取得 server_id
$server_id = isset($_GET['server_id']) ? intval($_GET['server_id']) : 0;
if ($server_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid server_id']);
    exit;
}

// 查詢該伺服器的頻道
$sql = "SELECT id, name FROM server_channels WHERE server_id = :server_id ORDER BY created_at";
$stmt = $pdo->prepare($sql);
$stmt->execute(['server_id' => $server_id]);

$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 回傳 JSON
echo json_encode($channels);
