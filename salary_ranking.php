<?php
session_start();
include 'db_connect.php';

$year = isset($_GET['year']) ? intval($_GET['year']) : 2015; 

?>

<!DOCTYPE html>
<html>
<head>
    <title>선수 연봉 순위</title>
    <link rel="stylesheet" href="css/main.css"/>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        form { 
            /* text-align: center; */
             margin-bottom: 20px; }
        table { border-collapse: collapse; width: 80%; 
            /* margin: 0 auto; */
         }
        th, td { border: 1px solid #ddd; padding: 8px; 
            text-align: left; 
        }
        th{
            background-color: var(--accent-color);
        }
        th { 
            /* background-color: var(--background-color);  */
            text-align: center; 
        }
        /* tr:nth-child(even) { background-color: var(--background-color); } */
        td:first-child, td:last-child { text-align: center; }
        tr:nth-child(2),tr:nth-child(3),tr:nth-child(4){background-color: #80B0BD ;}
        td:nth-child(4) { text-align: right; }
        .form_horizontal{width:280px;}
        .numBtn{
            background-color:var(--background-color);
            color: var(--text-color);
            width:42px;
            height: 42px;
            font-size: 18px;
            margin: auto 0;
        }

        /* number 안의 상하 버튼 삭제 */
        /* chrome 등 */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }

        /* Firefox */
        input[type=number] {
        -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php
        include 'pages/nav.php';
        ?>
    <h1>Get Annual Salary Ranking <br/>by player with your form.</h1>
    <div>
        Get annual salary ranking by player with your form.<br/>
        you can filter the players by team, position, etc.  
    </div>
    <form action="salary_ranking.php" method="GET">
        <div class="form_horizontal">
            <div><label for="year">Year</label></div><div>></div>
            <div>
                <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepDown()">-</button>
                <input type="number" id="year" name="year" 
                    value="<?php echo htmlspecialchars($year); ?>" 
                    min="1985" max="2015"> 
                <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepUp()">+</button>
            </div>
        </div>
        <div><input id="btn" type="submit" value="Get Result"></div>
    </form>

    <h2> Annual Salary Ranking <?php echo htmlspecialchars(string: $year); ?>:TOP 100</h2>
    
    <table>
        <tr>
            <th>Rank</th>
            <th>Player</th>
            <th>Team</th>
            <th>Annual Salary</th>
        </tr>
        <?php
        
        $sql = "SELECT 
                    m.nameFirst, 
                    m.nameLast,
                    t.name AS teamName,
                    s.salary,
                    RANK() OVER (PARTITION BY s.yearID ORDER BY s.salary DESC) as salary_rank
                FROM 
                    Salaries s
                JOIN 
                    Master m ON s.playerID = m.playerID
                JOIN 
                    Teams t ON s.teamID = t.teamID AND s.yearID = t.yearID AND s.lgID = t.lgID
                WHERE 
                    s.yearID = ?
                ORDER BY 
                    salary_rank ASC, s.salary DESC
                LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['salary_rank'] . "</td>";
                echo "<td>" . htmlspecialchars($row['nameFirst'] . " " . $row['nameLast']) . "</td>";
                echo "<td>" . htmlspecialchars($row['teamName']) . "</td>";
                echo "<td>$" . number_format($row['salary']) . "</td>"; 
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>해당 연도의 데이터가 없습니다. (1985~2015년 사이로 입력하세요.)</td></tr>";
        }
        
        $stmt->close();
        $conn->close();
        ?>
    </table>
    
    <p style-align:center><a href="index.php">Go main</a></p>

</body>
</div>
</html>