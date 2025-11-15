
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <!-- <title>Baseball Analytics</title> -->
    <!-- <link rel="stylesheet" href="../css/main.css"/> -->
    <style>
        body{
            background-color: var(--background-color);
            color: var(--text-color); 
            width: 100% !important;
        }
        nav { 
            margin-top: 15px; 
            display: flex;
            flex-direction: column;
            border-top: 1px var(--text-color) solid;
            border-bottom: 1px var(--text-color) solid;
        }
        nav a { 
            margin-right: 15px; 
            text-decoration: none; 
            font-size: 0.9em; 
            width: fit-content;
            padding: 0 15px;
        }
        nav ul,li{
            list-style: none;
            display: inline-block;
        }
        nav ul{
            padding-bottom: 10px;
        }
        nav ul li{
            position: relative;
            width: max-content;
            align-items: center;
            height: 30px;
            padding: 10px 0px 10px 5px;
        }
        nav ul  li  ul{
            display: none;
            position: absolute;
            top: 100%;		/* 2차 메뉴를 1차 메뉴의 아래에 위치시킨다 */
            left: 0;		/* 2차 메뉴를 1차 메뉴의 왼쪽 벽에 붙인다 */
            width: 100%;
            height: auto;
            padding-left: 0;
            background-color: var(--text-color);
            color: var(--background-color);
            box-sizing: border-box;  
        }
        nav ul  li:hover  ul {
            display: flex;  
            flex-direction: column;
            box-sizing: border-box;  
            width: 100%;    	
        }
        nav ul li:hover{
            background-color: var(--text-color);
            color: var(--background-color);
        }
        nav ul li ul li:hover{
            background-attachment: fixed;
        }
        nav .login-menu{
            float: right;
        }
    </style>
</head>
<body>

    <!-- 로그인/회원가입  -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a> </li>
            <li>
                <a href="#">Analytics by Player</a> 
                <ul>
                    <li><a href="player_score_trend.php">Score Trend</a></li>
                    <li><a href="stats_per_position.php">Stats per Position</a></li>
                    <li><a href="salary_ranking.php">Salary Ranking</a></li>
                </ul>
            </li>
            <li>
                <a href="#">Analytics by Team</a> 
                <ul>
                    <li><a href="team_ranking.php">Score Ranking</a></li>
                    <li><a href="team_salary_olap.php">Salary Comparison</a></li>
                </ul>
            </li>
            <li>
                <a href="#">Analytics by Season/Game</a> 
                <ul>
                    <li><a href="game_position.php">Position Distribution</a></li>
                    <li><a href="game_salary_olap.php">Salary Comparison</a></li>
                </ul>
            </li>
            <li class="login-menu">
            <?php if (isset($_SESSION['userid'])): ?>
                    <!-- 1. 로그인 성공 -->
                    <a>
                        <strong>Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                    </a>
                    <ul>
                    <li><a href="mypage.php">My Page</a></li> 
                    <li><a href="logout_process.php">Logout</a></li>
                    </ul>
                <?php else: ?>
                    <!-- 2. 로그인 안 됨 -->
                    <!-- <p>You need to Login.</p> -->
                    <a>Login | Sign Up</a>
                    <ul>
                        <li><a href="login.php">Login</a></li> 
                        <li><a href="register.php">Sign Up</a></li>
                    </ul>
                 
                <?php endif; ?>
            </li>
        </ul>
        
    </nav>

    <!-- <hr/> -->
</body>

</html>
