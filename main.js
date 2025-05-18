const channelTitle = document.getElementById("channelTitle");

// 登入使用者名稱從 PHP 傳進來（在 index.php 頁面中用 JS 變數傳入）
const username = window.loggedInUser || "未知使用者";
let currentChannel = null;
let lastMessageId = 0;

const messageList = document.getElementById("messageList");
const input = document.getElementById("messageInput");

// 載入歷史訊息
function loadMessages(channelId) {
  fetch(`get_messages.php?channel=${channelId}`)
      .then(res => res.json())
      .then(data => {
        messageList.innerHTML = "";
        data.forEach(msg => {
          appendMessage(msg.user, msg.content);
          lastMessageId = Math.max(lastMessageId, msg.id);
        });
      });
}

// 合併的 appendMessage 函數，顯示用戶名和訊息內容
function appendMessage(user, content) {
  const messageEl = document.createElement("div");
  messageEl.classList.add("message");

  const authorEl = document.createElement("span");
  authorEl.classList.add("author");
  authorEl.textContent = `${user}：`;

  const contentEl = document.createElement("span");
  contentEl.classList.add("content");
  contentEl.textContent = content;

  messageList.prepend(messageEl);
  messageEl.prepend(contentEl);
  messageEl.prepend(authorEl);
}

// 發送訊息
// 發送訊息
input.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && input.value.trim() !== "" && currentChannel) {
    const text = input.value.trim();
    input.value = "";

    appendMessage(username, text);
    messageList.scrollTop = messageList.scrollHeight;

    const msg = {
      user: username,
      content: text,
      channel: currentChannel
    };

    fetch("save_message.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(msg)
    }).then(() => fetchMessages());
  }
});


// 載入歷史訊息（或最新訊息）
function fetchMessages() {
  if (!currentChannel) return;
  fetch(`get_messages.php?channel=${currentChannel}&after=${lastMessageId}`)
      .then(res => res.json())
      .then(data => {
        data.forEach(msg => {
          appendMessage(msg.user, msg.content);
          lastMessageId = Math.max(lastMessageId, msg.id);
        });
      });
}

function loadServers() {
  fetch("get_servers.php")
      .then(res => res.json())
      .then(servers => {
        const serverList = document.getElementById("serverList");
        serverList.innerHTML = "";
        servers.forEach(server => {
          const li = document.createElement("li");
          li.className = "server-icon";
          li.textContent = server.name[0]; // 用首字母
          li.title = server.name;
          li.dataset.serverId = server.id;
          li.addEventListener("click", () => loadChannels(server.id, server.name));
          serverList.appendChild(li);
        });
      });
}

function loadChannels(serverId, serverName) {
    fetch(`get_channels.php?server_id=${serverId}`)
        .then(res => res.json())
        .then(channels => {
            const channelList = document.getElementById("channelList");
            channelList.innerHTML = "";

            channels.forEach(channel => {
                const li = document.createElement("li");
                li.textContent = `# ${channel.name}`;
                li.dataset.channelId = channel.id;
                li.className = "channel-button";

                li.addEventListener("click", () => {
                    document.querySelectorAll(".channel-button").forEach(b => b.classList.remove("active"));
                    li.classList.add("active");

                    currentChannel = channel.id;
                    localStorage.setItem("lastChannel", currentChannel); // ✅ 儲存上次選擇的頻道 ID

                    channelTitle.textContent = `# ${channel.name}`;
                    lastMessageId = 0;
                    loadMessages(currentChannel);
                });

                channelList.appendChild(li);
            });

            // ✅ 自動選擇上次進入的頻道
            const lastChannelId = localStorage.getItem("lastChannel");
            if (lastChannelId) {
                const lastBtn = [...channelList.children].find(li => li.dataset.channelId === lastChannelId);
                if (lastBtn) {
                    lastBtn.click();
                } else if (channelList.firstChild) {
                    channelList.firstChild.click(); // 若找不到，就選第一個
                }
            } else if (channelList.firstChild) {
                channelList.firstChild.click(); // 沒有記錄也預設點第一個
            }
        });
}


loadServers();
setInterval(fetchMessages, 2000);

window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('theme')) {
            if (e.target.classList.contains('light')) {
                document.body.classList.add('light-mode');
                localStorage.setItem('theme', 'light');
            } else {
                document.body.classList.remove('light-mode');
                localStorage.setItem('theme', 'dark');
            }
        }
    });
});
// 點擊事件（仍保留）
document.getElementById('dmButton').addEventListener('click', openDMPanel);

// 頁面載入時自動執行
window.addEventListener('DOMContentLoaded', openDMPanel);

const settingsPanel = document.getElementById('userSettings');
const openBtn = document.getElementById('settingsBtn');
const closeBtn = document.getElementById('x');

function showSettingsPanel() {
    settingsPanel.classList.add('active');
    question1();
    document.querySelectorAll(".userList").forEach(btn => btn.classList.remove("actives"));
    document.querySelector(".userList").classList.add("actives");
}

function hideSettingsPanel() {
    settingsPanel.classList.remove('active');
}

// 開啟設定
openBtn.addEventListener('click', showSettingsPanel);

// 點叉叉關閉
closeBtn.addEventListener('click', hideSettingsPanel);

// 按 ESC 關閉
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && settingsPanel.classList.contains('active')) {
        hideSettingsPanel();
    }
});

document.getElementById("x").addEventListener("mouseenter", function () {
    const isLight = document.body.classList.contains("light-mode");
    document.getElementById("esc").style.color = isLight ? "#23272a" : "#f4f4f4";
});
document.getElementById("x").addEventListener("mouseleave", function () {
    const isLight = document.body.classList.contains("light-mode");
    document.getElementById("esc").style.color = isLight ? "rgba(35, 39, 42, 0.82)" : "#d5d5d5";
});



const buttons = document.querySelectorAll('.userList');

buttons.forEach(button => {
    button.addEventListener('click', () => {
        // 移除所有按鈕的 active 狀態
        buttons.forEach(btn => btn.classList.remove('actives'));
        // 把 active 加到被點的按鈕
        button.classList.add('actives');
    });
});

// 開啟與關閉建立伺服器面板
document.getElementById("addServerBtn").addEventListener("click", () => {
    document.getElementById("createServerModal").classList.remove("hidden");
});

document.getElementById("createServerCancel").addEventListener("click", () => {
    document.getElementById("createServerModal").classList.add("hidden");
});

// 建立伺服器確認邏輯（你可以延伸接後端 API）
document.getElementById("createServerConfirm").addEventListener("click", () => {
    const name = document.getElementById("newServerName").value.trim();
    if (name === "") {
        alert("伺服器名稱不能為空");
        return;
    }

    // 這裡未來可以呼叫 API 建立伺服器，暫時只是模擬新增到清單
    const serverList = document.getElementById("serverList");
    const newIcon = document.createElement("li");
    newIcon.className = "server-icon";
    newIcon.textContent = name[0].toUpperCase(); // 用第一個字母當 Icon
    serverList.insertBefore(newIcon, document.getElementById("addServerBtn"));

    // 關閉 modal 並清空輸入
    document.getElementById("createServerModal").classList.add("hidden");
    document.getElementById("newServerName").value = "";
});