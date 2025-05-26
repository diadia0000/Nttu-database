    const channelTitle = document.getElementById("channelTitle");

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

    const Serverinput = document.getElementById("newServerName");
    const button = document.getElementById("createServerConfirm");

    Serverinput.addEventListener("input", () => {
        const name = Serverinput.value.trim();
        if (name === "") {
            button.disabled = true;  // 沒輸入就禁用按鈕
        } else {
            button.disabled = false; // 有輸入就啟用按鈕
        }
    });

    // 開啟
    // 建立伺服器面板
    document.getElementById("addServerBtn").addEventListener("click", () => {
        document.getElementById("createServerModal").classList.remove("hidden");
    });
    // 建立伺服器確認邏輯（你可以延伸接後端 API）
    document.getElementById("createServerConfirm").addEventListener("click", () => {
        const name = document.getElementById("newServerName").value.trim();
        if (!name) return;

        fetch("create-server.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `server_name=${encodeURIComponent(name)}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("伺服器建立成功！");
                    // 可選：重新載入或動態新增伺服器按鈕
                    window.location.reload();
                } else {
                    alert("建立失敗");
                }
            })
            .catch(err => {
                console.error(err);
                alert("發生錯誤");
            });
    });

    // 預覽伺服器圖片
    function previewServerIcon(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('iconPreview');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    // 新稱伺服器的取消按鈕
    function cancelCreateServer() {
        document.getElementById('createServerModal').classList.add('hidden');

        // 清空圖片預覽
        document.getElementById('iconPreview').style.display = 'none';
        document.getElementById('iconPreview').src = '';

        // 重設表單
        document.getElementById('createServerForm').reset();
    }

    // 伺服器清單
    const serverList = document.getElementById("serverList");

    if (Array.isArray(window.joinedServers)) {
        window.joinedServers.forEach(server => {
            const li = document.createElement("li");
            li.className = "server-icon";
            li.title = server.name;
            li.style.marginTop = "10px";

            // 顯示圖示：有自訂icon就顯示，否則顯示servername首字
            if (server.icon) {
                const img = document.createElement("img");
                img.src = server.icon;
                img.alt = server.name;
                img.style.width = "40px";
                img.style.height = "40px";
                img.style.borderRadius = "10px";
                li.appendChild(img);
            } else {
                // 顯示伺服器名稱的首字母（轉為大寫）
                li.textContent = server.name.charAt(0).toUpperCase();
            }

            li.addEventListener("click", () => {
                const serverId = server.id; // 假設你的伺服器 id 在 server.id
                document.querySelectorAll('.server-icon').forEach(icon => icon.classList.remove('active'));
                li.classList.add('active');
                const serverHeader = document.querySelector('.server-header');
                serverHeader.textContent = server.name;

                // 1. 用 fetch 請求後端拿頻道資料
                fetch(`get-channels.php?server_id=${serverId}`)
                    .then(res => res.json())
                    .then(channels => {
                        if (channels.length > 0) {
                            // 預設頻道顯示第一個
                            channelTitle.textContent = "#" + channels[0].name;
                        } else {
                            channelTitle.textContent = "沒有頻道";
                        }
                    })
                    .catch(err => {
                        console.error('頻道載入失敗', err);
                        channelTitle.textContent = "頻道載入錯誤";
                    });

                // 2. 更新頂部伺服器名稱與 icon
                const topbartitle = document.querySelector('.top-bar-title');
                if (topbartitle) {
                    topbartitle.innerHTML = `
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <img src="${server.icon || ''}" alt="server-icon" style="height: 20px; width: 20px; border-radius: 5px;" />
                ${server.name}
            </span>
        `;
                }

                // 3. 隱藏私訊面板
                const dmPanel = document.getElementById('dmPanel');
                if (dmPanel) dmPanel.classList.add('hidden');

                // 4. 解除私訊按鈕 active 狀態
                const dmBtn = document.getElementById('dmButton');
                if (dmBtn) dmBtn.classList.remove('active');
            });


            serverList.insertBefore(li, document.getElementById("addServerBtn"));
        });
    }