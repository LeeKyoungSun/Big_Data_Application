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
      <div>
        <label for="playerSelect">Player > </label>
        <select id="playerSelect" disabled>
            <option value="">-- Select Team First --</option>
        </select>
      </div>
      <div><button id="btn" disabled> Get Result </button></div>
    </div>
  </div>

  <div id="chartContainer">
    <canvas id="playerChart"></canvas>
  </div>

  <script>
    window.addEventListener('DOMContentLoaded', loadTeams);

    function loadTeams() {
        fetch('player_trend.php?action=getTeams')
            .then(res => res.json())
            .then(teams => {
                const select = document.getElementById('teamSelect');
                teams.forEach(team => {
                  // console.log(team);
                    const option = document.createElement('option');
                    option.value = team.teamID;
                    option.textContent = `${team.name}`;
                    select.appendChild(option);
                });
            })
            .catch(err => showError('팀 목록을 불러오는데 실패했습니다.'));
    }
    const teamSelect = document.getElementById("teamSelect");
    const playerSelect = document.getElementById("playerSelect");
    const ctx = document.getElementById("playerChart").getContext("2d");
    const getResult = document.getElementById("btn");
    let chartInstance = null;


    // 팀 선택 시 선수 목록 로드
    teamSelect.addEventListener("change", () => {
      const teamID = teamSelect.value;
      playerSelect.innerHTML = '<option value="">-- Select Player --</option>';

      // if (!teamID) {
      //   playerSelect.disabled = true;
      //   return;
      // }
      if (teamID) {
        fetch(`player_trend.php?action=getPlayers&teamID=${teamID}`)
          .then(res => res.json())
          .then(players => {
            players.forEach(player => {
                const option = document.createElement('option');
                // console.log(player);
                option.value = player.playerID;
                option.textContent = `${player.fullName}`;
                playerSelect.appendChild(option);
            });
            // gameSelect.disabled = false;
          })
          .catch(err => {
              console.error('fetch error:', err);
              showError('경기 목록을 불러오는데 실패했습니다.');
          }); 
      }
      playerSelect.disabled = false;
    });
    playerSelect.addEventListener("change",()=>{
      getResult.disabled=false;
    })
    // 선수 선택 시 그래프 표시
    getResult.addEventListener("click", () => {
      const playerID = playerSelect.value;

      if(playerID){
        fetch(`player_trend.php?action=getStats&playerID=${playerID}`)
          .then(res => res.json())
          .then(data => {
            console.log(data);
            const stats=data.stats;
            const labels = stats.map(s => s.yearID);
            const AB = stats.map(s => s.AB);
            const H = stats.map(s => s.H);
            const hr = stats.map(s => s.HR);
            const avg = stats.map(s => (s.battingAvg * 1000).toFixed(0));
              
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
              type: "line",
              data: {
                labels,
                datasets: [
                  {
                    label: "At Bats(AB)",
                    data: AB,
                    borderColor: "#36A2EB",
                    backgroundColor: "rgba(54,162,235,0.2)",
                    yAxisID: "y1",
                    tension: 0.3
                  },
                  {
                    label: "Hit(H)",
                    data: H,
                    borderColor: "#FF6384",
                    backgroundColor: "rgba(255,99,132,0.2)",
                    yAxisID: "y2",
                    tension: 0.3
                  },
                  {
                    label: "Home Run(HR)",
                    data: hr,
                    borderColor: "#FF9F40",
                    backgroundColor: "rgba(255,159,64,0.2)",
                    yAxisID: "y2",
                    tension: 0.3
                  },
                  {
                    label: "Batting Average(×1000)",
                    data: avg,
                    borderColor: "#36eb7bff",
                    backgroundColor: "rgba(54,162,235,0.2)",
                    yAxisID: "y1",
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
                    text: teamSelect.options[teamSelect.selectedIndex].text+ " - " + playerSelect.options[playerSelect.selectedIndex].text,
                    font:{size:20}
                  }
                }
              }
            });
          })
          .catch(err => {
              console.error('fetch error:', err);
              // console.log(data);
              showError('경기 목록을 불러오는데 실패했습니다.');
          }); 
      
      

    }
    });
    Chart.defaults.color = "#FAFAF8";
    </script>

    <?php
    $conn->close();
    ?>
</body>
</div>

</html>