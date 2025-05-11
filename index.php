<?php
session_start();

// 確保已登入並存在 email
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['user']['email']; // 從 session 取得 email

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
    // 連接資料庫
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查詢用戶名
    $stmt = $pdo->prepare("SELECT username FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $username = $user['username']; // 取得用戶名
    } else {
        $username = "未找到用戶名"; // 如果找不到用戶名
    }
} catch (PDOException $e) {
    echo "資料庫錯誤: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Discord風格聊天室</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <button class="toggle-theme">🌓</button>
      <button class="logout-button" onclick="window.location.href='logout.php'">登出</button>
    </aside>
    <main class="main">
      <header class="channel-header" id="channelTitle"># 一般</header>
      <section class="chat-window">
        <div class="message-list" id="messageList"></div>
        <div class="message-input">
          <input type="text" id="messageInput" placeholder="輸入訊息並按 Enter..." />
        </div>
      </section>
    </main>
  </div>

  <script>
    // 安全地輸出用戶名稱給 JavaScript
    window.loggedInUser = <?php echo json_encode(htmlspecialchars($username, ENT_QUOTES, 'UTF-8')); ?>;
  </script>
  <script src="main.js"></script>
</body>
</html>
