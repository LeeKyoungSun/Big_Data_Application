<?php
session_start();
include 'db_connect.php'; 

$year = isset($_GET['year']) ? intval($_GET['year']) : 2015;
$lg = isset($_GET['lg']) ? $_GET['lg'] : 'NL';

$sql_summary = "SELECT
    CASE
        WHEN t.teamID IS NULL THEN 'All team'    
        ELSE t.name
    END AS TeamName,
    COUNT(DISTINCT s.playerID) AS PlayerCount,
    SUM(s.salary)              AS TotalSalary,
    AVG(s.salary)              AS AvgSalary
FROM Salaries s
JOIN Teams t
  ON s.teamID = t.teamID
 AND s.yearID = t.yearID
 AND s.lgID   = t.lgID
WHERE s.yearID = ?
  AND s.lgID   = ?
GROUP BY t.teamID WITH ROLLUP
";


$stmt = $conn->prepare($sql_summary);
$stmt->bind_param("is", $year, $lg);
$stmt->execute();
$rs = $stmt->get_result();

$overall = null;     
$teams   = [];       

while ($row = $rs->fetch_assoc()) {
  if ($row['TeamName'] === 'All team') {
    $overall = $row;
  } else {
    $teams[] = $row;
  }
}

$sql_rank = "SELECT
    teamName,
    PlayerCount,
    DENSE_RANK() OVER (ORDER BY TotalSalary DESC) AS Rank_TotalSalary,
    DENSE_RANK() OVER (ORDER BY AvgSalary DESC)   AS Rank_AvgSalary
FROM (
    SELECT
        t.name AS teamName,
        COUNT(DISTINCT s.playerID) AS PlayerCount,
        SUM(s.salary) AS TotalSalary,
        AVG(s.salary) AS AvgSalary
    FROM Salaries s
    JOIN Teams t 
      ON s.teamID = t.teamID 
     AND s.yearID = t.yearID
     AND s.lgID   = t.lgID
    WHERE s.yearID = ?
      AND s.lgID   = ?
    GROUP BY s.teamID
) AS x
ORDER BY Rank_TotalSalary ASC
";

$stmt2 = $conn->prepare($sql_rank);
$stmt2->bind_param("is", $year, $lg);
$stmt2->execute();
$rs_rank = $stmt2->get_result();

$ranks = [];
while ($row = $rs_rank->fetch_assoc()) {
    $ranks[] = $row;
}
$stmt2->close();
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
<title>Salary Comparison by Team</title>
<link rel="stylesheet" href="css/main.css" />

<style>
  :root { --card-w: 420px; }
  /* body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 26px; }
  h1 { text-align: center; margin: 6px 0 12px; }
  form { text-align: center; margin: 10px 0 22px; }
  form input, form button { padding: 8px 12px; font-size: 14px; }
  form button { cursor: pointer; } */

  #cardContainer {
    width: 100%;
    
  }
  .wrap{
    display: flex;
    justify-content: space-around;
    grid-template-columns: repeat(3, minmax(280px, var(--card-w)));
    gap: 96px;
    align-items: start;
  }
  @media (max-width: 1200px) {
    .wrap { grid-template-columns: repeat(2, minmax(280px, 1fr)); }
  }
  @media (max-width: 800px) {
    .wrap { grid-template-columns: minmax(280px, 1fr); }
  }

  
  .carousel {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  /* gap: 12px; */
}
.carousel .prev { left: 10px; }
.carousel .next { right: 10px; }
.nav-btn {
  background: transparent;
  border: none;
  font-size: 32px;
  cursor: pointer;
  color: #fff;
}

.nav-btn:disabled {
  opacity: 0.3;
  cursor: default;
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
  .card.conclusion{
    width: 100% !important;
    padding: auto;
  }
  .conclusion tr:nth-child(2),
  .conclusion tr:nth-child(3),
  .conclusion tr:nth-child(4){background-color: var(--secondary-accent-color) ;}



</style>
</head>
<body>
  <div class="layout">

  <?php
  include 'pages/nav.php';
  ?>
  <h1>Salary Comparison by Team</h1>

<form method="GET" action="team_salary_olap.php">-
  <div>
      Get Salary Comparison.<br/>
      You can filter the data by year (1985-2015) and league.
  </div>
  <div class="form_horizontal">
    <div><label for="year">Year</label></div><div>></div>
    <div>
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepDown()">-</button>
      <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1985" max="2015" />
      <button class="numBtn" type="button" onclick="this.parentNode.querySelector('#year').stepUp()">+</button>
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
  <div><input type="submit" id="btn" value="Get Result"></input></div>
 
</form>
<br/>
<h2>Salary Comparison (<?php echo htmlspecialchars($year) . ' ' . htmlspecialchars($lg); ?>)</h2>
<div class="carousel">
  <button class="nav-btn prev" type="button">&#10094;</button>

  <div class="wrap" id="cardContainer">
    <?php if ($overall): ?>
      <section class="card" style="background-color: var(--secondary-accent-color);">
        <h2>All Teams (<?php echo htmlspecialchars($lg); ?>)</h2>
        <table>
          <tr><th>Index</th><th>Value</th></tr>
          <tr><td>Number of Player</td><td><?php echo number_format($overall['PlayerCount']); ?></td></tr>
          <tr><td>Total Salary</td><td><?php echo mf($overall['TotalSalary']); ?></td></tr>
          <tr><td>Average Salary</td><td><?php echo mf($overall['AvgSalary'], 2); ?></td></tr>
        </table>
      </section>
    <?php endif; ?>

    <?php foreach ($teams as $row): ?>
      <section class="card">
        <h2>Team: <?php echo htmlspecialchars($row['TeamName']); ?></h2>
        <table>
          <tr><th>Index</th><th>Value</th></tr>
          <tr><td>Number of Player</td><td><?php echo number_format($row['PlayerCount']); ?></td></tr>
          <tr><td>Total Salary</td><td><?php echo mf($row['TotalSalary']); ?></td></tr>
          <tr><td>Average Salary</td><td><?php echo mf($row['AvgSalary'], 2); ?></td></tr>
        </table>
      </section>
    <?php endforeach; ?>
  </div>

  <button class="nav-btn next" type="button">&#10095;</button>
</div>
<section class=" conclusion" id="cardContainer">
  <h2>Conclusion (<?php echo htmlspecialchars($year) . ' ' . htmlspecialchars($lg); ?>)</h2>
  <?php if (!empty($ranks)): ?>
    <table>
      <tr>
        <th>Rank (Total Salary)</th>
        <th>Rank (Average Salary)</th>
        <th>Team</th>
        <th>Number of Player</th>
      </tr>
      <?php foreach ($ranks as $row): ?>
        <tr>
          <td><?php echo $row['Rank_TotalSalary']; ?></td>
          <td><?php echo $row['Rank_AvgSalary']; ?></td>
          <td><?php echo htmlspecialchars($row['teamName']); ?></td>
          <td><?php echo number_format($row['PlayerCount']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>There is no team data for this league/year.</p>
  <?php endif; ?>
</section>

</div>

<script>
(function() {
  const container = document.getElementById('cardContainer');
  if (!container) return;

  const cards = Array.from(container.querySelectorAll('.card'));
  const visible = 3;          // 한 화면에 3개씩
  let start = 0;

  const prevBtn = document.querySelector('.nav-btn.prev');
  const nextBtn = document.querySelector('.nav-btn.next');

  function update() {
    cards.forEach((card, idx) => {
      card.style.display = (idx >= start && idx < start + visible) ? 'block' : 'none';
    });

    // 버튼 상태 (원하면 루프형으로 바꿔도 됨)
    prevBtn.disabled = (start === 0);
    nextBtn.disabled = (start + visible >= cards.length);
  }

  prevBtn.addEventListener('click', () => {
    if (start - visible >= 0) {
      start -= visible;
      update();
    }
  });

  nextBtn.addEventListener('click', () => {
    if (start + visible < cards.length) {
      start += visible;
      update();
    }
  });

  update();
})();
</script>

</body>
</html>
<?php $conn->close(); ?>
