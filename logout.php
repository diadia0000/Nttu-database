<?php
session_start();
session_unset(); // 清除 session 變數
session_destroy(); // 銷毀 session
header("Location: login.php"); // 登出後返回登入頁
exit;
