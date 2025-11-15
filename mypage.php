<?php
session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script>
            alert('You need to Log in.');
            location.href = 'login.php';
          </script>";
    exit;
}
?>
<?php
include 'pages/nav.php';
?>
<!-- // 마이페이지에서 이름 변경 -->
<!DOCTYPE html>
<html>
<head>
    <title>My Page</title>
    <link rel="stylesheet" href="css/main.css" />

</head>
<body>
    <div class="layout">
    <h1>My Page</h1>
    <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (ID: <?php echo htmlspecialchars($_SESSION['userid']); ?>)</p>
    
    <form action="mypage_process.php" method="POST">
        
        <div>
            <label for="userName">Name to change:</label>
            <input type="text" id="userName" name="userName" 
                value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
        </div>
        <br/>
        <input id="btn"type="submit" value="Change Name">
    </form>
    <br/><br/>
    <a href="index.php">Home</a> | 
    <a href="logout_process.php">Log Out</a> | 
    <a href="delete_account.php" style="color:red;">delete account</a>
    </div>
</body>
</html>