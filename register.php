<!DOCTYPE html>
<html>
<head>
    <title>회원가입</title>
    <link rel="stylesheet" href="css/main.css"/>
</head>
<body id="userPage">
    <div class="layout">
    <?php
        include 'pages/nav.php';
        ?>
    <h1>Sign Up</h1>
    <form action="register_process.php" method="POST">
        <div>
            <label for="userId">ID</label><br/>
            <input type="text" id="userId" name="userId" required>
        </div>
        <div>
            <label for="userPW">Password</label><br/>
            <input type="password" id="userPW" name="userPW" required>
        </div>
        <div>
            <label label for="userName">Name</label><br/>
            <input type="text" id="userName" name="userName" required>
        </div>
        <input id="btn" type="submit" value="Sign Up">
    </form>
        </div>

</body>
</html>