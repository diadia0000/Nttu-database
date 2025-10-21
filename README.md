# FoxTalk - Discord風格聊天室
National Taitung University Database Project

## 專案簡介
一個基於 PHP 和 PostgreSQL 的 Discord 風格即時聊天應用程式，支援用戶註冊、登入、伺服器管理、頻道聊天和好友系統。

## 團隊成員
1. 謝尚哲  
2. 謝秉倫  
3. 鍾承翰  
4. 蔡昌??  

## 主要功能
- 🔐 用戶註冊與登入系統
- 💬 即時聊天功能
- 🏠 伺服器建立與管理
- 📺 頻道系統
- 👥 好友系統
- 🎨 深色/亮色主題切換
- 📱 響應式設計

## 技術架構
- **後端**: PHP 8.2.12
- **資料庫**: PostgreSQL (Supabase)
- **前端**: HTML5, CSS3, JavaScript
- **依賴管理**: Composer
- **環境變數**: vlucas/phpdotenv

## 檔案結構
```
Nttu-database/
├── img/                    # 圖片資源
├── uploads/               # 上傳檔案
├── vendor/                # Composer 依賴
├── whiteboard/            # 白板功能
├── index.php              # 主頁面
├── login.php              # 登入頁面
├── register.php           # 註冊頁面
├── db.php                 # 資料庫連線
├── main.js                # 主要 JavaScript
├── style.css              # 主要樣式
├── stylelogin.css         # 登入頁面樣式
├── composer.json          # PHP 依賴配置
├── .env                   # 環境變數
└── README.md              # 專案說明
```

## 安裝與設定

### 1. 環境需求
- PHP 8.2.12 或更高版本
- Composer
- PostgreSQL 資料庫

### 2. 安裝步驟
```bash
# 複製專案
git clone <repository-url>
cd Nttu-database

# 安裝 PHP 依賴
composer install

# 設定環境變數
cp .env.example .env
# 編輯 .env 檔案，填入資料庫連線資訊
```

### 3. 環境變數設定
在 `.env` 檔案中設定以下變數：
```env
SUPABASE_URL=your_supabase_url
SUPABASE_API_KEY=your_api_key
DB_HOST=your_db_host
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASSWORD=your_db_password
```

## 使用說明
1. 開啟 `login.php` 進行登入或註冊
2. 登入後進入主頁面 `index.php`
3. 可以建立伺服器、加入頻道、新增好友
4. 支援即時聊天和主題切換

## 主要頁面
- `index.php` - 主聊天介面
- `login.php` - 用戶登入
- `register.php` - 用戶註冊
- `friend_add.php` - 新增好友
- `create-server.php` - 建立伺服器

## 資料庫架構
使用 PostgreSQL 資料庫，主要資料表包括：
- `users` - 用戶資料
- `servers` - 伺服器資料
- `server_members` - 伺服器成員關係
- `channels` - 頻道資料
- `messages` - 訊息資料
- `friends` - 好友關係