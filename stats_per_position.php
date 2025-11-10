<?php
session_start();
include 'db_connect.php';

$year = isset($_GET['year']) ? intval($_GET['year']) : 2015;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'batter'; // batter | pitcher
$pos  = isset($_GET['pos'])  ? strtoupper(trim($_GET['pos'])) : null;

$posOptions = [];
$sqlPos = "SELECT DISTINCT POS FROM Fielding WHERE yearID = ? ORDER BY POS";
if ($stmtPos = $conn->prepare($sqlPos)) {
  $stmtPos->bind_param("i", $year);
  $stmtPos->execute();
  $resPos = $stmtPos->get_result();
  while ($r = $resPos->fetch_assoc()) {
    if (!empty($r['POS'])) $posOptions[] = strtoupper($r['POS']);
  }
  $stmtPos->close();
}

if ($pos === null) {
  if ($mode === 'pitcher' && in_array('P', $posOptions, true)) $pos = 'P';
  else $pos = $posOptions[0];
}

if ($mode === 'pitcher') {  // 투수
  $sql = "SELECT 
  T.name AS TeamName,
  A.avgERA, 
  A.W, 
  A.L, 
  A.G
FROM (SELECT
    p.teamID,
    p.lgID,
    ROUND(AVG(p.ERA), 2) AS avgERA,
    SUM(p.W)             AS W,
    SUM(p.L)             AS L,
    SUM(p.G)             AS G
  FROM Pitching p
  JOIN Fielding f
    ON p.playerID = f.playerID
   AND p.yearID   = f.yearID
   AND p.teamID   = f.teamID
   AND p.stint    = f.stint
  WHERE p.yearID = ?
    AND f.POS = ?
  GROUP BY p.teamID, p.lgID
) A
LEFT JOIN Teams T
  ON T.teamID = A.teamID
 AND T.yearID = ?
 AND T.lgID   = A.lgID
ORDER BY A.avgERA ASC, A.W DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isi", $year, $pos, $year);

} else {  // 타자
  $sql = "SELECT
  T.name AS TeamName,
  A.Total_Hits,
  A.Total_HomeRuns,
  A.Batting_Avg,
  A.Total_Games
FROM (
  SELECT
    b.teamID,
    b.lgID,
    SUM(b.H)                                  AS Total_Hits,
    SUM(b.HR)                                 AS Total_HomeRuns,
    SUM(b.H) / NULLIF(SUM(b.AB), 0)           AS Batting_Avg,
    SUM(b.G)                                  AS Total_Games
  FROM Batting b
  JOIN Fielding f
    ON  b.playerID = f.playerID
    AND b.yearID   = f.yearID
    AND b.teamID   = f.teamID
    AND b.stint    = f.stint
  WHERE b.yearID = ?
    AND f.POS = ?
  GROUP BY b.teamID, b.lgID
) A
LEFT JOIN Teams T
  ON T.teamID = A.teamID
 AND T.yearID = ?
 AND T.lgID   = A.lgID
ORDER BY A.Batting_Avg DESC, A.Total_HomeRuns DESC, A.Total_Hits DESC;";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isi", $year, $pos, $year);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>포지션별 팀 성적 비교</title>
<style>
  :root { --w: 1000px; }
  body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 28px; }
  h1, h2 { text-align: center; margin: 8px 0; }
  form { text-align: center; margin: 16px 0 24px; }
  form label { margin: 0 8px; }
  form input, form select { padding: 6px 10px; font-size: 14px; }
  form button { padding: 8px 14px; cursor: pointer; }
  .pos-wrap { display: inline-block; margin-left: 10px; }
  table { border-collapse: collapse; width: var(--w); max-width: 100%; margin: 0 auto; }
  th, td { border: 1px solid #ddd; padding: 10px 12px; }
  th { background: #f5f7fa; text-align: center; }
  tr:nth-child(even) { background: #fafafa; }
  td { text-align: right; }
  td:first-child { text-align: center; }
  .muted { color: #666; font-size: 13px; text-align: center; margin-top: 8px; }
  .foot { text-align: center; margin-top: 18px; }
</style>
<script>
function onModeChange() {
  // 투수로 바꿀 때 'P'가 목록에 있으면 자동 선택
  const mode = document.querySelector('input[name="mode"]:checked').value;
  const posSel = document.getElementById('pos');
  if (mode === 'pitcher') {
    const hasP = Array.from(posSel.options).some(o => o.value === 'P');
    if (hasP) posSel.value = 'P';
  }
}
</script>
</head>
<body>

<h1>포지션별 팀 비교</h1>

<form method="GET" action="stats_per_position.php">
  연도는 1871년부터 2015년까지 선택가능합니다. <br><br>
  <label>
    <input type="radio" name="mode" value="batter" <?php echo $mode==='batter'?'checked':''; ?> onclick="onModeChange()"> 타자
  </label>
  <label>
    <input type="radio" name="mode" value="pitcher" <?php echo $mode==='pitcher'?'checked':''; ?> onclick="onModeChange()"> 투수
  </label>
  <label for="year">연도</label>
  <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1871" max="2015" />

  <span class="pos-wrap">
    <label for="pos">포지션</label>
    <select id="pos" name="pos">
      <?php foreach ($posOptions as $opt): ?>
        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $pos === $opt ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($opt); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </span>

  <button type="submit">조회</button>
</form>

<?php
if ($mode === 'pitcher') {

    echo "<h2>" . htmlspecialchars($year) . "년 — 투수(" . htmlspecialchars($pos) . ") 포지션</h2>";
    echo "<table>";
    echo "<tr>
            <th>팀</th>
            <th>팀별 평균자책점</th>
            <th>총 승리수</th>
            <th>총 패배 수</th>
            <th>총 경기수</th>
          </tr>";

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['TeamName']) . "</td>";
            echo "<td>" . (is_null($row['avgERA']) ? '-' : number_format((float)$row['avgERA'], 2)) . "</td>";
            echo "<td>" . number_format((int)$row['W']) . "</td>";
            echo "<td>" . number_format((int)$row['L']) . "</td>";
            echo "<td>" . number_format((int)$row['G']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td>데이터가 없습니다.</td></tr>";
    }

    echo "</table>";
} else {
    echo "<h2>" . htmlspecialchars($year) . "년 — 타자(" . htmlspecialchars($pos) . ") 포지션</h2>";
    echo "<table>";
    echo "<tr>
            <th>팀</th>
            <th>평균 타율</th>
            <th>총 안타수</th>
            <th>총 홈런수</th>
            <th>총 경기수</th>
          </tr>";

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['TeamName']) . "</td>";
            echo "<td>" . (is_null($row['Batting_Avg']) ? '-' : number_format((float)$row['Batting_Avg'], 3)) . "</td>";
            echo "<td>" . number_format((int)$row['Total_Hits']) . "</td>";
            echo "<td>" . number_format((int)$row['Total_HomeRuns']) . "</td>";
            echo "<td>" . number_format((int)$row['Total_Games']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>데이터가 없습니다.</td></tr>";
    }

    echo "</table>";
   
}

echo "<div class='foot'><a href='index.php'>메인으로</a></div>";

?>

</body>
</html>
<?php
$stmt->close();
$conn->close();
