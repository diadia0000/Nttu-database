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

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentUser) {
        exit("找不到使用者");
    }
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    exit("系統錯誤");
}
?>

<div style="margin: 20px;">
    <h2>🔍 發送好友邀請</h2>
    <form id="addFriendForm">
        <label for="friend_username">輸入對方使用者名稱：</label><br>
        <input type="text" id="friend_username" name="friend_username" required><br><br>
        <button type="submit">送出邀請</button>
    </form>
    <p id="addFriendMessage" style="margin-top: 10px;"></p>
</div>

<script>
    const form = document.getElementById("addFriendForm");
    const message = document.getElementById("addFriendMessage");

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault(); // 阻止表單跳轉
            const username = document.getElementById("friend_username").value.trim();

            if (!username) {
                message.textContent = "請輸入使用者名稱";
                message.style.color = "red";
                return;
            }

            fetch("friend_add_submit.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "friend_username=" + encodeURIComponent(username),
            })
                .then((res) => res.text())
                .then((msg) => {
                    message.textContent = msg;
                    message.style.color = msg.includes("成功") || msg.includes("邀請已送出") ? "green" : "red";
                    if (message.style.color === "green") form.reset(); // 成功後清空欄位
                })
                .catch((err) => {
                    message.textContent = "發生錯誤：" + err.message;
                    message.style.color = "red";
                });
        });
    }
</script>
