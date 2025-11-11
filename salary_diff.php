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

// 리그 내 팀별 총연봉 상위/하위 3 팀 가져오기
function fetchTopTeamsBySalary(mysqli $conn, int $year, string $league): array {
  $sql = "WITH TeamSalary AS (
  SELECT
      t.name AS TeamName,
      COUNT(DISTINCT s.playerID) AS PlayerCount,
      SUM(s.salary) AS TotalSalary,
      AVG(s.salary) AS AvgSalary,
      ROW_NUMBER() OVER (ORDER BY SUM(s.salary) DESC) AS rn_desc
  FROM Teams t
  JOIN Salaries s
    ON t.yearID = s.yearID
   AND t.teamID = s.teamID
   AND t.lgID   = s.lgID
  WHERE t.yearID = ? and t.lgID=?
  GROUP BY t.teamID, t.name
)
SELECT *
FROM TeamSalary
WHERE rn_desc <= 3 
ORDER BY rn_desc
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $year, $league);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $rows;
}

function fetchBottomTeamsBySalary(mysqli $conn, int $year, string $league): array {
  $sql = "WITH TeamSalary AS (
  SELECT
      t.name AS TeamName,
      COUNT(DISTINCT s.playerID) AS PlayerCount,
      SUM(s.salary) AS TotalSalary,
      AVG(s.salary) AS AvgSalary,
      ROW_NUMBER() OVER (ORDER BY SUM(s.salary) ASC) AS rn_asc
  FROM Teams t
  JOIN Salaries s
    ON t.yearID = s.yearID
   AND t.teamID = s.teamID
   AND t.lgID   = s.lgID
  WHERE t.yearID = ? and t.lgID=?
  GROUP BY t.teamID, t.name
)
SELECT *
FROM TeamSalary
WHERE rn_asc <= 3 
ORDER BY rn_asc;
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $year, $league);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $rows;
}


$topAL = fetchTopTeamsBySalary($conn, $year, 'AL');
$botAL = fetchBottomTeamsBySalary($conn, $year, 'AL');
$topNL = fetchTopTeamsBySalary($conn, $year, 'NL');
$botNL = fetchBottomTeamsBySalary($conn, $year, 'NL');

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
</style>
</head>
<body>
  <div class="layout">

  <?php
  include 'pages/nav.php';
  ?>
  <h1>Salary Comparison by League</h1>

<form method="GET" action="salary_diff.php">
  <!-- 연도는 1985-2015까지 선택가능합니다. <br><br> -->
  <div>
      Get Teams' Salary Comparison by League.<br/>
      You can filter the data by year (1985-2015).
  </div>
  <div class="form_horizontal">
    <div><label for="year">Year</label></div><div>></div>
    <div>
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepDown()">-</button>
      <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1871" max="2015" />
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepUp()">+</button>
    </div>
  </div>
  <div><input type="submit" id="btn" value="Get Result"></input></div>
 
</form>

<!-- 전체 합계,  리그별 소계-->
<div class="wrap">
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


<h2 class="subhead"> Total Salary TOP/BOTTOM 3Teams in League</h2>

<div class="league-rows">
  <!-- AL 상위 3 -->
  <section class="card">
    <h2>AL — TOP 3 </h2>
    <?php if (!empty($topAL)): ?>
      <table>
        <tr><th>Rank</th><th>Team</th><th>Number of Player</th><th>Total Salary</th><th>Average Salary</th></tr>
        <?php foreach ($topAL as $i => $r): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($r['TeamName']); ?></td>
            <td><?php echo number_format($r['PlayerCount']); ?></td>
            <td><?php echo mf($r['TotalSalary']); ?></td>
            <td><?php echo mf($r['AvgSalary'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>There's no data.</p>
    <?php endif; ?>
  </section>

  <!-- AL 하위 3 -->
  <section class="card">
    <h2>AL — BOTTOM 3 </h2>
    <?php if (!empty($botAL)): ?>
      <table>
        <tr><th>Rank</th><th>Team</th><th>Number of Player</th><th>Total Salary</th><th>Average Salary</th></tr>
        <?php foreach ($botAL as $i => $r): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($r['TeamName']); ?></td>
            <td><?php echo number_format($r['PlayerCount']); ?></td>
            <td><?php echo mf($r['TotalSalary']); ?></td>
            <td><?php echo mf($r['AvgSalary'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>There's no data.</p>
    <?php endif; ?>
  </section>

  <!-- NL 상위 3 -->
  <section class="card">
    <h2>NL — TOP 3 </h2>
    <?php if (!empty($topNL)): ?>
      <table>
        <tr><th>Rank</th><th>Team</th><th>Number of Player</th><th>Total Salary</th><th>Average Salary</th></tr>        <?php foreach ($topNL as $i => $r): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($r['TeamName']); ?></td>
            <td><?php echo number_format($r['PlayerCount']); ?></td>
            <td><?php echo mf($r['TotalSalary']); ?></td>
            <td><?php echo mf($r['AvgSalary'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>There's no data.</p>
    <?php endif; ?>
  </section>

  <!-- NL 하위 3 -->
  <section class="card">
    <h2>NL — BOTTOM 3 </h2>
    <?php if (!empty($botNL)): ?>
      <table>
          <tr><th>Rank</th><th>Team</th><th>Number of Player</th><th>Total Salary</th><th>Average Salary</th></tr>        <?php foreach ($botNL as $i => $r): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($r['TeamName']); ?></td>
            <td><?php echo number_format($r['PlayerCount']); ?></td>
            <td><?php echo mf($r['TotalSalary']); ?></td>
            <td><?php echo mf($r['AvgSalary'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>There's no data.</p>
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
    $crossSentence = "Summary: Total Salary is higher in {$totalWinner} League, but average salary is higher in {$avgWinner} League.";
  }
  // 참여 선수 수: AL -명 vs NL -명 , 팀 수: AL -팀 vs NL -팀
  $playerNote = "Number of Players: AL " . number_format($alPlayers) . " vs NL " . number_format($nlPlayers) . "";
  $teamNote   = "Number of Teams: AL " . number_format($alTeams) . " vs NL " . number_format($nlTeams) . "";

?>

<section class="card">
  <h2>Conclusion (<?php echo htmlspecialchars($year); ?>)</h2>
  <?php if ($AL && $NL): ?>
    <ul>
      <li><?php echo $totalSentence; ?></li><br/>
      <li><?php echo $avgSentence; ?></li><br/>
      <?php if ($crossSentence): ?><li><?php echo $crossSentence; ?></li><?php endif; ?>
      <li><?php echo $playerNote; ?></li><br/><li> <?php echo $teamNote; ?></li>
    </ul>
  <?php else: ?>
    <p>You need AL/NL data both to make conclusion</p>
  <?php endif; ?>
</section>

<div class='foot'><a href='index.php'>Go to main</a></div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
