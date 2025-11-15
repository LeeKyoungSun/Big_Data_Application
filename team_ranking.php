<?php
session_start();
include 'db_connect.php';

$year = isset($_GET['year']) ? $_GET['year'] : '2015';
$lg = isset($_GET['lg']) ? $_GET['lg'] : 'NL';

?>

<!DOCTYPE html>
<html>
<head>
    <title>팀 시즌별 성적 순위</title>
    <link rel="stylesheet" href="css/main.css"/>
    <style>
        /* body { font-family: sans-serif; margin: 20px; } */
        form { 
            /* text-align: center; */
             margin-bottom: 20px; }
        .form_horizontal{
            width: 320px;
            align-content: center;
        }
        /* .form_horizontal label{
            margin: auto 30;
        } */
        table { border-collapse: collapse; width: 80%; 
            /* margin: 0 auto; */
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: center; 
        }
        th { 
            /* background-color: var(--background-color);  */
            background-color: var(--accent-color);
            text-align: center; 
        }
        /* 상위 3개 팀 */
        tr:nth-child(2),tr:nth-child(3),tr:nth-child(4){background-color: var(--secondary-accent-color) ;}
        td:nth-child(2) { text-align: left; }
        td:nth-child(1),td:nth-child(5){width: 15%;}
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
    <h1>Get Team Score Ranking in Each Year and League</h1>
    <div !font-size:18>
        Get Team score ranking in season with your form.<br/>
        You can pick the year and league to get the Teams' score ranking.
    </div>
    <form action="team_ranking.php" method="GET">
        <div class="form_horizontal">
            <div><label for="year">Year</label></div><div>></div>
            <div>
                <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepDown()">-</button>
                <input type="number" type="button" id="year" name="year" 
                    value="<?php echo htmlspecialchars($year); ?>" min="1871"
                        max="2015">
                    <button class="numBtn" onclick="this.parentNode.querySelector('#year').stepUp()">+</button>
            </div>
        </div>
        <div class="form_horizontal">
            <div><label for="lg">League</label></div><div>></div>
            <div>
                <select name="lg">
                    <option value="NL" <?php if ($lg == 'NL')
                        echo 'selected'; ?>>National League (NL)</option>
                    <option value="AL" <?php if ($lg == 'AL')
                        echo 'selected'; ?>>American League (AL)</option>
                </select>
           </div>
        </div>
        <div><input id="btn" type="submit" value="Get Result"></div>
    </form>

        <h2> Score Ranking in <?php echo htmlspecialchars(string: $year); ?> <?php echo htmlspecialchars(string: $lg); ?></h2>

        <table border:1px>
        <tr>
            <th>Calculated Rank</th>
            <th>Team</th>
            <th>W</th>
            <th>L</th>
            <th>Official Rank</th>
        </tr>
        <?php
            $sql = "SELECT 
                    name, W, L, `Rank` AS official_rank,
                    RANK() OVER (PARTITION BY yearID, lgID ORDER BY W DESC) AS calculated_rank
                FROM Teams
                WHERE yearID = ? AND lgID = ?
                ORDER BY calculated_rank ASC";

            $stmt = $conn->prepare(query: $sql);
            $stmt->bind_param("is", $year, $lg);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['calculated_rank'] . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . $row['W'] . "</td>";
                    echo "<td>" . $row['L'] . "</td>";
                    echo "<td>" . $row['official_rank'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>해당 연도/리그의 데이터가 없습니다.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </table>

        <!-- <p style-align:center><a href="index.php">Go main</a></p> -->

</body>
</html>