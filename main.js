const toggleTheme = document.querySelector(".toggle-theme");
const channelButtons = document.querySelectorAll(".channel-button");
const channelTitle = document.getElementById("channelTitle");

// 頻道切換
channelButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    channelButtons.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    currentChannel = btn.dataset.channel;
    channelTitle.textContent = `# ${btn.textContent.replace('# ', '')}`;
    renderMessages();
  });
});

// 主題切換
const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
if (localStorage.getItem("theme") === "light" || (!localStorage.getItem("theme") && !prefersDark)) {
  document.body.classList.add("light-mode");
}

toggleTheme.addEventListener("click", () => {
  document.body.classList.toggle("light-mode");
  localStorage.setItem("theme", document.body.classList.contains("light-mode") ? "light" : "dark");
});

// 登入使用者名稱從 PHP 傳進來（在 index.php 頁面中用 JS 變數傳入）
const username = window.loggedInUser || "未知使用者";
let currentChannel = "general"; // 你可根據實際情況支援多頻道

const messageList = document.getElementById("messageList");
const input = document.getElementById("messageInput");

// 載入歷史訊息
function loadMessages(channel) {
  fetch(`get_messages.php?channel=${channel}`)
    .then(res => res.json())
    .then(data => {
      messageList.innerHTML = "";
      data.forEach(msg => {
        appendMessage(msg.user, msg.content);
      });
      messageList.scrollTop = messageList.scrollHeight;
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
input.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && input.value.trim() !== "") {
    const text = input.value.trim();
    input.value = "";

    // 顯示在畫面
    appendMessage(username, text);
    messageList.scrollTop = messageList.scrollHeight;

    const msg = {
      user: username,
      content: text,
      channel: currentChannel
    };

    // 發送到後端
    fetch("save_message.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(msg)
    }).then(() => {
      //立即重新載入最新訊息
      fetchMessages();
    });
  }
});

// 載入歷史訊息（或最新訊息）
function fetchMessages() {
  fetch(`get_messages.php?channel=${currentChannel}&after=${lastMessageId}`)
    .then(res => res.json())
    .then(data => {
      data.forEach(msg => {
        appendMessage(msg.user, msg.content);
        lastMessageId = Math.max(lastMessageId, msg.id);
      });
    });
}

// 啟動輪詢（每 2 秒抓一次最新訊息）
setInterval(fetchMessages, 2000);

// 首次載入
fetchMessages();
