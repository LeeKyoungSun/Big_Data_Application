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
<title>리그별 연봉 비교</title>
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
</style>
</head>
<body>
  <div class="layout">

  <?php
  include 'pages/nav.php';
  ?>
  <h1>리그별 연봉 비교</h1>

<form method="GET" action="salary_diff.php">
  연도는 1985년부터 2015년까지 선택가능합니다. <br><br>

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
    <h2>전체 합계</h2>
    <?php if ($overall): ?>
      <table>
        <tr><th>지표</th><th>값</th></tr>
        <tr><td>선수 수</td><td><?php echo number_format($overall['PlayerCount']); ?></td></tr>
        <tr><td>팀 수</td><td><?php echo number_format($overall['TeamCount']); ?></td></tr>
        <tr><td>총 연봉</td><td><?php echo mf($overall['TotalSalary']); ?></td></tr>
        <tr><td>평균 연봉</td><td><?php echo mf($overall['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>데이터가 없습니다.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>리그 AL</h2>
    <?php if ($AL): ?>
      <table>
        <tr><th>지표</th><th>값</th></tr>
        <tr><td>선수 수</td><td><?php echo number_format($AL['PlayerCount']); ?></td></tr>
        <tr><td>팀 수</td><td><?php echo number_format($AL['TeamCount']); ?></td></tr>
        <tr><td>총 연봉</td><td><?php echo mf($AL['TotalSalary']); ?></td></tr>
        <tr><td>평균 연봉</td><td><?php echo mf($AL['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>AL 데이터가 없습니다.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>리그 NL</h2>
    <?php if ($NL): ?>
      <table>
        <tr><th>지표</th><th>값</th></tr>
        <tr><td>선수 수</td><td><?php echo number_format($NL['PlayerCount']); ?></td></tr>
        <tr><td>팀 수</td><td><?php echo number_format($NL['TeamCount']); ?></td></tr>
        <tr><td>총 연봉</td><td><?php echo mf($NL['TotalSalary']); ?></td></tr>
        <tr><td>평균 연봉</td><td><?php echo mf($NL['AvgSalary'], 2); ?></td></tr>
      </table>
    <?php else: ?>
      <p>NL 데이터가 없습니다.</p>
    <?php endif; ?>
  </section>
</div>


<h2 class="subhead">리그 내 팀별 총연봉 상·하위 3팀</h2>

<div class="league-rows">
  <!-- AL 상위 3 -->
  <section class="card">
    <h2>AL — TOP 3 </h2>
    <?php if (!empty($topAL)): ?>
      <table>
        <tr><th>순위</th><th>팀</th><th>선수 수</th><th>총 연봉</th><th>평균 연봉</th></tr>
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
      <p>데이터가 없습니다.</p>
    <?php endif; ?>
  </section>

  <!-- AL 하위 3 -->
  <section class="card">
    <h2>AL — BOTTOM 3 </h2>
    <?php if (!empty($botAL)): ?>
      <table>
        <tr><th>순위</th><th>팀</th><th>선수 수</th><th>총 연봉</th><th>평균 연봉</th></tr>
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
      <p>데이터가 없습니다.</p>
    <?php endif; ?>
  </section>

  <!-- NL 상위 3 -->
  <section class="card">
    <h2>NL — TOP 3 </h2>
    <?php if (!empty($topNL)): ?>
      <table>
        <tr><th>순위</th><th>팀</th><th>선수 수</th><th>총 연봉</th><th>평균 연봉</th></tr>
        <?php foreach ($topNL as $i => $r): ?>
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
      <p>데이터가 없습니다.</p>
    <?php endif; ?>
  </section>

  <!-- NL 하위 3 -->
  <section class="card">
    <h2>NL — BOTTOM 3 </h2>
    <?php if (!empty($botNL)): ?>
      <table>
        <tr><th>순위</th><th>팀</th><th>선수 수</th><th>총 연봉</th><th>평균 연봉</th></tr>
        <?php foreach ($botNL as $i => $r): ?>
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
      <p>데이터가 없습니다.</p>
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
    ? "두 리그의 총 연봉이 동일합니다."
    : "총 연봉은 {$totalWinner} 리그가 더 높습니다 (" . mf($totalDiff) . " 차이)";
  // 평균 연봉은 AL 리그가 더 높습니다 ($ 차이)
  $avgSentence = ($avgWinner === '동일')
    ? "두 리그의 평균 연봉이 동일합니다."
    : "평균 연봉은 {$avgWinner} 리그가 더 높습니다 (" . mf($avgDiff, 2) . " 차이)";

  // 요약: 총 연봉은 NL 리그가 더 높지만, 평균 연봉은 AL 리그가 더 높습니다.
  $crossSentence = '';
  if ($totalWinner !== '동일' && $avgWinner !== '동일' && $totalWinner !== $avgWinner) {
    $crossSentence = "요약: 총 연봉은 {$totalWinner} 리그가 더 높지만, 평균 연봉은 {$avgWinner} 리그가 더 높습니다.";
  }
  // 참여 선수 수: AL -명 vs NL -명 , 팀 수: AL -팀 vs NL -팀
  $playerNote = "참여 선수 수: AL " . number_format($alPlayers) . "명 vs NL " . number_format($nlPlayers) . "명";
  $teamNote   = "팀 수: AL " . number_format($alTeams) . "팀 vs NL " . number_format($nlTeams) . "팀";

?>

<section class="card">
  <h2>결론 (<?php echo htmlspecialchars($year); ?>년)</h2>
  <?php if ($AL && $NL): ?>
    <ul>
      <li><?php echo $totalSentence; ?></li>
      <li><?php echo $avgSentence; ?></li>
      <?php if ($crossSentence): ?><li><?php echo $crossSentence; ?></li><?php endif; ?>
      <li><?php echo $playerNote; ?></li><li> <?php echo $teamNote; ?></li>
    </ul>
  <?php else: ?>
    <p>AL/NL 리그 데이터가 모두 있어야 결론을 생성할 수 있습니다.</p>
  <?php endif; ?>
</section>

<div class='foot'><a href='index.php'>메인으로</a></div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
