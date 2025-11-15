<?php
session_start();
if (!isset($_SESSION['userid'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>회원 탈퇴</title>
        <link rel="stylesheet" href="css/main.css" />
    <style>
        button{
            text-align: center;
            background-color: var(--background-color);
            color: var(--text-color); 
            border: var(--text-color) 1px solid;
            padding: 10px 15px;
            font-size: var(--font-size-base);
        }
    </style>
</head>
<body style="text-align: center; padding-top: 50px;">
    <h1 style="color:red;">정말로 탈퇴하시겠습니까?</h1>

    <form action="delete_account_process.php" method="POST" style="margin-top: 30px;">
        <input type="hidden" name="confirm_delete" value="true">
        <input type="submit" value="예" style="background-color: red; color: white; padding: 10px 20px;">
        <button type="button" onclick="location.href='mypage.php'">아니오</button>
    </form>
</body>
</html>