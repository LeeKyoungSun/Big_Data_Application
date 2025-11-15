<?php
session_start();
include 'db_connect.php'; 

$year = isset($_GET['year']) ? intval($_GET['year']) : 2015;

$sql_summary = "
  SELECT
    lgID,
    COUNT(DISTINCT playerID) AS PlayerCount,
    COUNT(DISTINCT teamID)   AS TeamCount,
    SUM(salary)              AS TotalSalary,
    AVG(salary)              AS AvgSalary
  FROM Salaries
  WHERE yearID = ?
  GROUP BY lgID WITH ROLLUP
";


$stmt = $conn->prepare($sql_summary);
$stmt->bind_param("i", $year);
$stmt->execute();
$rs = $stmt->get_result();

$overall = null; $AL = null; $NL = null;
while ($row = $rs->fetch_assoc()) {
  if (is_null($row['lgID'])) $overall = $row;
  elseif ($row['lgID'] === 'AL') $AL = $row;
  elseif ($row['lgID'] === 'NL') $NL = $row;
}
$stmt->close();


function mf($v, $dec = 0) { // money format
  if ($v === null) return '-';
  $num = $dec > 0 ? number_format((float)$v, $dec) : number_format((float)$v);
  return '$' . $num;
}
?>


<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<title>Salary Comparison by League</title>
<link rel="stylesheet" href="css/main.css" />

<style>
  :root { --card-w: 420px; }
  /* body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 26px; }
  h1 { text-align: center; margin: 6px 0 12px; }
  form { text-align: center; margin: 10px 0 22px; }
  form input, form button { padding: 8px 12px; font-size: 14px; }
  form button { cursor: pointer; } */

  .wrap {
    width: 90%;
    display: flex;
    justify-content: space-around;
    grid-template-columns: repeat(3, minmax(280px, var(--card-w)));
    gap: 16px;
    align-items: start;
  }
  @media (max-width: 1200px) {
    .wrap { grid-template-columns: repeat(2, minmax(280px, 1fr)); }
  }
  @media (max-width: 800px) {
    .wrap { grid-template-columns: minmax(280px, 1fr); }
  }

  .card {
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 14px 16px 12px;
    color: black;
  }
  .card h2 { font-size: 18px; margin: 2px 0 10px; }

  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #eee; padding: 10px 12px; }
  th { 
    /* background: #f7f8fb;  */
    text-align: center; }
  td { text-align: right; }
  td:first-child, th:first-child { text-align: center; }

  .muted { text-align: center; color:#666; font-size: 13px; margin-top: 12px; }

  .league-rows {
    margin-top: 28px;
    display: grid;
    grid-template-columns: repeat(2, minmax(320px, 560px));
    gap: 18px;
    justify-content: center;
  }
  @media (max-width: 1000px) {
    .league-rows { grid-template-columns: minmax(320px, 1fr); }  }
  .subhead { text-align:center; margin: 34px 0 12px; font-size: 20px; }
  td{text-align:center}
  .card{max-width:980px;margin: 28px auto;}
  .card h2 {margin: 4px 0 10px;}

  .form_horizontal{width:250px}
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
  .conclusion{
    width: 90%;
    margin: 0 auto;
    padding: auto;
    background-color: white;
    border: 1px solid #e6e6e6;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 14px 16px 12px;
    /* color: black; */
  }
  .conclusion >section{
    box-shadow: none;
    border: 0;
    font-size: 18px;
    margin-left: 0;
    line-height: 1.7;
}
</style>
</head>
<body>
  <div class="layout">

  <?php
  include 'pages/nav.php';
  ?>
  <h1>Salary Comparison by League</h1>

<form method="GET" action="game_salary_olap.php">-
  <div>
      Get Salary Comparison.<br/>
      You can filter the data by year (1985-2015).
  </div>
  <div class="form_horizontal">
    <div><label for="year">Year</label></div><div>></div>
    <div>
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepDown()">-</button>
      <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1985" max="2015" />
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepUp()">+</button>
    </div>
  </div>
  <div><input type="submit" id="btn" value="Get Result"></input></div>
 
</form>
<br/>
<!-- 전체 합계,  리그별 소계-->
<h2>Salary Comparison (<?php echo htmlspecialchars($year); ?>)</h2>

<div class="wrap" >
  <section class="card">
    <h2>Total</h2>
    <?php if ($overall): ?>
      <table>
        <tr><th>Index</th><th>Value</th></tr>
        <tr><td>Number of Player</td><td><?php echo number_format($overall['PlayerCount']); ?></td></tr>
        <tr><td>Number of Team</td><td><?php echo number_format($overall['TeamCount']); ?></td></tr>
        <tr><td>Total Salary</td><td><?php echo mf($overall['TotalSalary']); ?></td></tr>
        <tr><td>Average Salary</td><td><?php echo mf($overall['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>There's no data.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>League AL</h2>
    <?php if ($AL): ?>
      <table>
        <tr><th>Index</th><th>Value</th></tr>
        <tr><td>Number of Player</td><td><?php echo number_format($AL['PlayerCount']); ?></td></tr>
        <tr><td>Number of Team</td><td><?php echo number_format($AL['TeamCount']); ?></td></tr>
        <tr><td>Total Salary</td><td><?php echo mf($AL['TotalSalary']); ?></td></tr>
        <tr><td>Average Salary</td><td><?php echo mf($AL['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>There's no AL data.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>League NL</h2>
    <?php if ($NL): ?>
      <table>
        <tr><th>Index</th><th>Value</th></tr>
        <tr><td>Number of Player</td><td><?php echo number_format($NL['PlayerCount']); ?></td></tr>
        <tr><td>Number of Team</td><td><?php echo number_format($NL['TeamCount']); ?></td></tr>
        <tr><td>Total Salary</td><td><?php echo mf($NL['TotalSalary']); ?></td></tr>
        <tr><td>Average Salary</td><td><?php echo mf($NL['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>There's no NL data.</p>
    <?php endif; ?>
  </section>
</div>

<?php
  $alTotal = (float)$AL['TotalSalary'];
  $nlTotal = (float)$NL['TotalSalary'];
  $alAvg   = (float)$AL['AvgSalary'];
  $nlAvg   = (float)$NL['AvgSalary'];
  $alPlayers = (int)$AL['PlayerCount'];
  $nlPlayers = (int)$NL['PlayerCount'];
  $alTeams   = (int)$AL['TeamCount'];
  $nlTeams   = (int)$NL['TeamCount'];

  // 총연봉/평균연봉 승자 및 차이
  $totalWinner = ($alTotal === $nlTotal) ? '동일' : (($alTotal > $nlTotal) ? 'AL' : 'NL');
  $avgWinner   = ($alAvg   === $nlAvg)   ? '동일' : (($alAvg   > $nlAvg)   ? 'AL' : 'NL');
  $totalDiff   = abs($alTotal - $nlTotal);
  $avgDiff     = abs($alAvg   - $nlAvg);

  // 총 연봉은 NL 리그가 더 높습니다 ($ 차이)
  $totalSentence = ($totalWinner === '동일') 
    ? "Total Salaries of Two Leagues are identical."
    : "Total Salary is higher in {$totalWinner} League (" . mf($totalDiff) . " different)";
  // 평균 연봉은 AL 리그가 더 높습니다 ($ 차이)
  $avgSentence = ($avgWinner === '동일')
    ? "Average Salaries of Two Leagues are identical."
    : "Average Salary is higher in {$avgWinner} League (" . mf($avgDiff, 2) . " different)";

  // 요약: 총 연봉은 NL 리그가 더 높지만, 평균 연봉은 AL 리그가 더 높습니다.
  $crossSentence = '';
  if ($totalWinner !== '동일' && $avgWinner !== '동일' && $totalWinner !== $avgWinner) {
    $crossSentence = "To summarize, Total Salary is higher in {$totalWinner} League, but average salary is higher in {$avgWinner} League.";
  }
  // 참여 선수 수: AL -명 vs NL -명 , 팀 수: AL -팀 vs NL -팀
  $symbol = $alPlayers > $nlPlayers ? ' > ' : ($alPlayers < $nlPlayers ? ' < ' : ' = ');  
  $playerNote = "Number of Players: AL (" . number_format($alPlayers) . ")" . ($alPlayers > $nlPlayers ? ' > ' : ($alPlayers < $nlPlayers ? ' < ' : ' = ')). "NL (" . number_format($nlPlayers) . ")";
  $teamNote   = "Number of Teams: AL (" . number_format($alTeams) . ") ".($alTeams > $nlTeams ? ' > ' : ($alTeams < $nlTeams ? ' < ' : ' = '))." NL (" . number_format($nlTeams) . ")";

?>
<h2>Conclusion (<?php echo htmlspecialchars($year); ?>)</h2>

<?php if ($AL && $NL): ?>
  <table style="text-align: left !important">
    <tr>
        <th>Analysis</th>
      </tr>
    <tr>
      <td><?php echo $totalSentence; ?></td>
    </tr>
    <tr>
      <td><?php echo $avgSentence; ?></td>
    </tr>
    <tr>
      <td><?php echo $playerNote; ?></td>
    </tr>
    <tr>
      <td><?php echo $teamNote; ?></td>
    </tr>
  </table>
  <table>
    <?php if ($crossSentence): ?>
      <!-- <tr style="background-color: var(--secondary-accent-color);"> -->
      <tr style="font-size: 20px;">
        <td><?php echo $crossSentence; ?></td>
      </tr><br /><?php endif; ?>
  </table>

<?php else: ?>
  <p>You need AL/NL data both to make conclusion</p>
<?php endif; ?>



    </div>
</body>
</html>
<?php $conn->close(); ?>
