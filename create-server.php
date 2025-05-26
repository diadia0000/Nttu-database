<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 驗證登入
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    http_response_code(403);
    echo "未登入";
    exit;
}

// 資料庫連線
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$dbuser = $_ENV['DB_USER'];
$dbpassword = $_ENV['DB_PASSWORD'];
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $dbuser, $dbpassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 取得使用者 ID
$email = $_SESSION['user']['email'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'];

// 接收表單資料
$server_name = $_POST['server_name'] ?? '';
if (empty($server_name)) {
    http_response_code(400);
    echo "伺服器名稱為必填";
    exit;
}

// 處理圖片上傳
$server_icon_path = null;
if (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['server_icon']['tmp_name'];
    $original_name = basename($_FILES['server_icon']['name']);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // 簡單安全檢查
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowed_extensions)) {
        http_response_code(400);
        echo "不支援的圖片格式";
        exit;
    }

    $filename = uniqid("icon_") . "." . $extension;
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $target_path = $upload_dir . $filename;
    if (move_uploaded_file($tmp_name, $target_path)) {
        $server_icon_path = 'uploads/' . $filename; // 相對路徑，可改成網址格式
    } else {
        http_response_code(500);
        echo "圖片上傳失敗";
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // 1. 新增伺服器，含 icon
    $stmt = $pdo->prepare("INSERT INTO servers (name, owner_id, icon, created_at) VALUES (?, ?, ?, NOW()) RETURNING id");
    $stmt->execute([$server_name, $user_id, $server_icon_path]);
    $server_id = $stmt->fetchColumn();

    // 2. 新增預設頻道
    $stmt = $pdo->prepare("INSERT INTO server_channels (server_id, name, created_at) VALUES (?, '一般', NOW()) RETURNING id");
    $stmt->execute([$server_id]);
    $channel_id = $stmt->fetchColumn();

    // 3. 新增成員
    $stmt = $pdo->prepare("INSERT INTO server_members (server_id, user_id, role_id, created_at) VALUES (?, ?, NULL, NOW())");
    $stmt->execute([$server_id, $user_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "server_id" => $server_id,
        "channel_id" => $channel_id,
        "icon" => $server_icon_path
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "錯誤：" . $e->getMessage();
}
?>
