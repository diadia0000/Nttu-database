<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    http_response_code(403);
    header('Location: login.php');
    exit;
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

    // 確認是 POST 請求且有傳伺服器 id
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['server_id'])) {
        $server_id = $_POST['server_id'];

        // 確認這位使用者是否為該伺服器的擁有者（或有權限刪除）
        $stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ? AND owner_email = ?");
        $stmt->execute([$server_id, $email]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$server) {
            echo "您沒有權限刪除此伺服器";
            exit;
        }

        // 執行刪除
        $stmt = $pdo->prepare("DELETE FROM servers WHERE id = ?");
        $stmt->execute([$server_id]);

        echo "刪除成功";
    } else {
        echo "請求格式錯誤";
    }
} catch (PDOException $e) {
    error_log("刪除伺服器失敗：" . $e->getMessage());
    echo "系統錯誤，請稍後再試";
}
?>
