<!DOCTYPE html>
<html>
<head>
    <title>로그인</title>
    <link rel="stylesheet" href="css/main.css"/>
    <style>
    </style>
</head>
<body id="userPage">
    <div class="layout">
    <?php
    include 'pages/nav.php';
    ?>
    <h1>Login</h1>
    <form action="login_process.php" method="POST">
        <div>
            <label for="userId">ID </label><br/>
            <input type="text" id="userId" name="userId" required>
        </div>
        <div>
            <label for="userPW">Password </label><br/>
           <input type="password" id="userPW" name="userPW" required>
        </div>
        <input id="btn" type="submit" value="Log in" width:100px>
    </form>
    <!-- <p><a href="register.php">Go to Sign Up</a></p> -->
    </div>
</body>
</html>