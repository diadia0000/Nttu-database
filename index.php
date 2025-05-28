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
    $stmt = $pdo->prepare("SELECT id,username, avatar FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // 使用者沒有註冊過 username，導回補完註冊
        header("Location: register-complete.php");
        exit;
    }else {
        $username = $user['username'];
        if(isset($user['avatar']) && !empty($user['avatar'])) {
        $avatar = "avatars/" . $user['avatar']; // 假設用戶有一個 avatar 欄位來儲存頭像
        }
        else {
        $avatar = "img/FoxTalk.png"; // 默認頭像
        }
    }
    // 查詢使用者加入的伺服器清單
    $stmt = $pdo->prepare("
    SELECT s.id, s.name, s.icon
    FROM servers s
    JOIN server_members sm ON s.id = sm.server_id
    WHERE sm.user_id = ?
");
    $stmt->execute([$user['id']]);
    $joinedServers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    echo "系統發生錯誤，請稍後再試。";
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
<div class="top-bar">
    <div class="top-bar-title"></div>
</div>
<div class="app">
    <!-- 左側：伺服器列 -->
    <aside class="servers-bar">
        <div class="server-icon active" id="dmButton" title="私訊">💬</div>
        <ul id="serverList">
            <!-- JS 會填入 <li class="server-icon">🔥</li> 等 -->
            <li class="server-icon" style="margin-top: 10px;" id="addServerBtn" title="新增伺服器">➕</li>
        </ul>
    </aside>

    <aside class="sidebar">
        <nav>
            <div class="server-header"></div>
            <ul id="channelList">
                <!-- 頻道清單 -->
            </ul>
            <ul id="dmList">
                <!-- 私訊清單 -->
            </ul>
        </nav>

        <div class="user-info-bottom">
            <div class="user-avatar">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" />
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <div class="setting">
                <button id="settingsBtn">
                    <img src="img/icon-dark.png" alt="settings" id="dark-icon" />
                    <img src="img/icon-light.png" alt="settings" id="light-icon" />
                </button>
            </div>
        </div>
    </aside>

    <!-- 右側：聊天室主畫面 -->
    <main class="main">
        <header class="channel-header" id="channelTitle"></header>
            <div id="friendList">
                <h2 style="margin: 20px;">👥 我的好友</h2>
                <?php if (empty($friends)): ?>
                    <p style="margin-left: 20px;">你目前沒有任何好友。</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0; margin-left: 20px;">
                        <?php foreach ($friends as $friend): ?>
                            <li style="margin-bottom: 10px; display: flex; align-items: center;">
                                <img src="<?= htmlspecialchars($friend['avatar'] ?? 'img/FoxTalk.png') ?>"
                                 alt="頭像"
                                 style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                                <?= htmlspecialchars($friend['username']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <section class="chat-window">
            <div class="message-list" id="messageList"></div>
            <div class="message-input hidden" id="messageInputWrapper">
                <input type="text" id="messageInput" placeholder="輸入訊息並按 Enter..." />
            </div>
        </section>
    </main>

    <!-- 用戶設定面板 -->
    <div id="userSettings" class="user-settings">
        <!-- 這裡放設定內容 -->
        <aside class="left">
            <div style="border-bottom: 1px solid rgba(128,128,128,0.8)">
                <p style="margin-top: 35px;margin-left: 6px;">使用者設定</p>
                <button style="margin-bottom: 5px" class="userList actives" onclick="question1()">我的帳號</button>
            </div>
            <div style="border-bottom: 1px solid rgba(128,128,128,0.8)">
                <p style="margin-top: 10px;margin-left: 6px;">應用程式(網頁)設定</p>
                <button style="margin-bottom: 5px" class="userList" onclick="question2()">外觀</button>
            </div>
                <button class="userList logout" onclick="logout()">登出帳號</button>
        </aside>
        <div class="right">
            <p id="content"></p>
            <button id="closeSettingsBtn">
                <p id="x">✕</p>
                <p id="esc">ESC</p>
            </button>
        </div>
    </div>
    <!-- 新增伺服器面板 -->
    <div id="createServerModal" class="modal hidden">
        <div class="modal-content">
            <h2>建立伺服器</h2>

            <form id="createServerForm" action="create-server.php" method="POST" enctype="multipart/form-data">
                <label>
                    選擇圖示：
                    <input type="file" name="server_icon" accept="image/*" onchange="previewServerIcon(event)" required>
                </label>
                <img id="iconPreview" src="" alt="預覽圖示" style="max-width: 100px; margin: 10px 0; display: none;">

                <p>給你的伺服器取個名字吧。</p>
                <input type="text" name="server_name" id="newServerName" placeholder="伺服器名稱" required />

                <div class="modal-buttons">
                    <button type="submit" id="createServerConfirm">建立</button>
                    <button type="button" id="createServerCancel" onclick="cancelCreateServer()">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.loggedInUser = <?php echo json_encode($username, JSON_UNESCAPED_UNICODE); ?>;
    window.joinedServers = <?php echo json_encode($joinedServers); ?>;

    // 安全地輸出用戶名稱給 JavaScript
    window.loggedInUser = <?php echo json_encode(htmlspecialchars($username, ENT_QUOTES, 'UTF-8')); ?>;

    function openDMPanel() {
        document.getElementById('channelTitle').innerHTML = `
            <button onclick="loadFriendList()">好友</button>
            <button onclick="window.location.href='friend_add.php'">新增好友</button>
        `;

        const topbartitle = document.querySelector('.top-bar-title');
        if (topbartitle) {
            topbartitle.innerHTML = `
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                <img src="img/FoxTalk.png" alt="fox talk" style="height: 20px; width: 20px; border-radius: 5px;" />
                私人訊息
                </span>
            `;
        }

        document.querySelectorAll('.server-icon').forEach(icon => icon.classList.remove('active'));

        const dmButton = document.getElementById('dmButton');
        if (dmButton) {
            dmButton.classList.add('active');
        }

        const serverHeader = document.querySelector('.server-header');
        if (serverHeader) {
            serverHeader.textContent = "私人訊息";
        }
    }

    function question1() {
        document.getElementById("content").innerHTML = `
            <h2 style='width: 150px;margin-left: 40px;margin-top: 55px;font-size: 20px;font-weight: bold;'>我的帳號</h2>
        `;
    }
    function question2() {
        document.getElementById("content").innerHTML = `
            <h2 style='width: 150px;margin-left: 40px;margin-top: 55px;font-size: 20px;font-weight: bold;'>外觀</h2>
            <h2 style='width: 150px;margin-left: 40px;margin-top: 55px;font-size: 20px;font-weight: lighter;'>主題</h2>
            <button class="theme dark" title="深色模式"></button>
            <button class="theme light" title="亮色模式"></button>
        `;

        const darkBtn = document.querySelector('.theme.dark');
        const lightBtn = document.querySelector('.theme.light');

        // 先根據目前 body 的類別設定按鈕選中狀態
        if(document.body.classList.contains('light-mode')) {
            lightBtn.classList.add('selected');
        } else {
            darkBtn.classList.add('selected');
        }

        darkBtn.addEventListener('click', () => {
            document.body.classList.remove('light-mode');
            darkBtn.classList.add('selected');
            lightBtn.classList.remove('selected');
            document.getElementById("esc").style.color = "#d5d5d5";
        });

        lightBtn.addEventListener('click', () => {
            document.body.classList.add('light-mode');
            lightBtn.classList.add('selected');
            darkBtn.classList.remove('selected');
            document.getElementById("esc").style.color = "rgba(35, 39, 42, 0.82)";
        });
    }

    function logout() {
        window.location.href = 'logout.php';
    }
    function loadFriendList() {
        fetch('friend_list.php')
            .then(res => res.text())
            .then(html => {
                document.getElementById('content').innerHTML = html;
            });
    }

</script>
<script src="main.js"></script>
</body>
</html>
