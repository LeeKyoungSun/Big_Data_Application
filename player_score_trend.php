<?php
session_start();
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>player Score Trend</title>
    <link rel="stylesheet" href="css/main.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;}
        .header h1 { margin: 0; }
        .login-menu { text-align: right; }
        #chartContainer{
            width: 80%;
            color:var(--text-color);
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php
        include 'pages/nav.php';
        ?>
        <h1>Get Score Trend by Player</h1>
        <div >
            choose a player who you want to know his or her score trend.<br/>
            you can filter the players by team, name, etc.  
        </div>
        <div>
    <div>
        <label for="teamSelect">Team > </label>
        <select id="teamSelect">
          <option value="">-- Select Team --</option>
        </select>
    </div>
    <div>
        <label for="playerSelect">Player > </label>
        <select id="playerSelect" disabled>
            <option value="">-- Select Team First --</option>
        </select>
    </div>
  </div>

  <div id="chartContainer">
    <canvas id="playerChart"></canvas>
  </div>

  <script>
   // -----------------------------------
    // ✅ 예시 데이터 정의
    // -----------------------------------
    const teams = [
      { teamID: "NYY", name: "New York Yankees" },
      { teamID: "BOS", name: "Boston Red Sox" }
    ];

    const players = {
      NYY: [
        { playerID: "judgea01", fullName: "Aaron Judge" }
      ],
      BOS: [
        { playerID: "bogarx01", fullName: "Xander Bogaerts" }
      ]
    };

    const playerStats = {
      judgea01: [
        { yearID: 2018, teamName: "New York Yankees", HR: 27, RBI: 67, battingAvg: 0.279 },
        { yearID: 2019, teamName: "New York Yankees", HR: 27, RBI: 55, battingAvg: 0.272 },
        { yearID: 2020, teamName: "New York Yankees", HR: 9, RBI: 22, battingAvg: 0.257 },
        { yearID: 2021, teamName: "New York Yankees", HR: 39, RBI: 98, battingAvg: 0.287 }
      ],
      bogarx01: [
        { yearID: 2018, teamName: "Boston Red Sox", HR: 23, RBI: 103, battingAvg: 0.288 },
        { yearID: 2019, teamName: "Boston Red Sox", HR: 33, RBI: 117, battingAvg: 0.309 },
        { yearID: 2020, teamName: "Boston Red Sox", HR: 11, RBI: 28, battingAvg: 0.300 },
        { yearID: 2021, teamName: "Boston Red Sox", HR: 23, RBI: 79, battingAvg: 0.295 }
      ]
    };

    // -----------------------------------
    // ⚙️ 그래프 초기화 로직
    // -----------------------------------
    const teamSelect = document.getElementById("teamSelect");
    const playerSelect = document.getElementById("playerSelect");
    const ctx = document.getElementById("playerChart").getContext("2d");
    let chartInstance = null;

    // 팀 목록 로드
    teams.forEach(t => {
      const opt = document.createElement("option");
      opt.value = t.teamID;
      opt.textContent = t.name;
      teamSelect.appendChild(opt);
    });

    // 팀 선택 시 선수 목록 로드
    teamSelect.addEventListener("change", () => {
      const teamID = teamSelect.value;
      playerSelect.innerHTML = '<option value="">-- Select Player --</option>';

      if (!teamID) {
        playerSelect.disabled = true;
        return;
      }

      players[teamID].forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.playerID;
        opt.textContent = p.fullName;
        playerSelect.appendChild(opt);
      });

      playerSelect.disabled = false;
    });

    // 선수 선택 시 그래프 표시
    playerSelect.addEventListener("change", () => {
      const playerID = playerSelect.value;
      if (!playerID) return;

      const stats = playerStats[playerID];
      const labels = stats.map(s => s.yearID);
      const hr = stats.map(s => s.HR);
      const rbi = stats.map(s => s.RBI);
      const avg = stats.map(s => (s.battingAvg * 1000).toFixed(0));

      if (chartInstance) chartInstance.destroy();
      chartInstance = new Chart(ctx, {
        type: "line",
        data: {
          labels,
          datasets: [
            {
              label: "Batting Average(×1000)",
              data: avg,
              borderColor: "#36A2EB",
              backgroundColor: "rgba(54,162,235,0.2)",
              yAxisID: "y1",
              tension: 0.3
            },
            {
              label: "Home Run(HR)",
              data: hr,
              borderColor: "#FF6384",
              backgroundColor: "rgba(255,99,132,0.2)",
              yAxisID: "y2",
              tension: 0.3
            },
            {
              label: "Run Batted In(RBI)",
              data: rbi,
              borderColor: "#FF9F40",
              backgroundColor: "rgba(255,159,64,0.2)",
              yAxisID: "y2",
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y1: {
              type: "linear",
              position: "left",
              title: { display: true, text: "Batting Average(×1000)" },
              grid: { drawOnChartArea: false }
            },
            y2: {
              type: "linear",
              position: "right",
              title: { display: true, text: "HR / RBI" }
            }
          },
          plugins: {
            title: {
              display: true,
              text: stats[0].teamName + " - " + playerSelect.options[playerSelect.selectedIndex].text,
              font:{size:20}
            }
          }
        }
      });
    });
    Chart.defaults.color = "#FAFAF8";
    </script>

    <?php
    $conn->close();
    ?>
</body>
</div>

</html>