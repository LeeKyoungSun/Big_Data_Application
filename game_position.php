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
        .header {
            /* display: flex; */
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
        }
        
        .chart-container{
            /* display: flex; */
            /* width: 40%; */
            /* color:var(--text-color); */
            /* margin-top: 40px; */
        }
        
        .results-container {
            display: none ;
        }
        .results-container.active {
            display: block;
        }
        .results-container.active .charts-wrapper{
            /* display: flex !important; */
            /* flex-direction: column; */
            /* width: 80%; */
        }
        th{
            background-color: var(--accent-color);

        }
        th,td{
            text-align: center; 
            border: 1px solid #ddd; padding: 8px; 
        }
        #rosterTableBody tr:last-child{
            background-color: var(--secondary-accent-color);
        }
        .chart-wrapper{
            max-width: 600px;
            height: auto;
        }
        /* #player-list{
            text-align: ;
        } */
    </style>
</head>

<body>
    <div class="layout">
        <?php
        include 'pages/nav.php';
        ?>
        <h1>Get Position Distribution <br/>& Players List.</h1>
        <div>
            Get Position distribution & Players who played the game.<br/>
            you can filter the games by the year and game Num. 
        </div>

        
   <div class="form-section">
       <div class="form-row">
           <div class="form-group">
               <label for="yearSelect"> Year </label>
               <select id="yearSelect">
                   <option value="">-- Select Year --</option>
               </select>
           </div>

           <div class="form-group">
               <label for="gameSelect"> League</label>
               <select id="gameSelect" disabled>
                   <option value="">-- Select Year First--</option>
               </select>
           </div>
       </div>

       <button id="btn" disabled> Get Result </button>
   </div>

   <div id="loadingDiv" class="loading" style="display:none;">
       Loading Data
   </div>

   <div id="errorDiv" class="error-message" style="display:none;"></div>

   <div id="resultsContainer" class="results-container">
        <br/>
        <h2>Position distribution</h2>
        <div class="chart-wrapper">
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
            <h2>Position Roster Table</h2>

            <table class="chart-container roster-table">
                <thead>
                <tr>
                    <th>Position</th>
                    <th>Number of Players</th>
                    <th>Rate</th>
                </tr>
                </thead>
                <tbody id="rosterTableBody"></tbody>
            </table>

        </div>
        <br/>
        <h2>Player List</h2>
        <!-- <div>여기에 경기별 player 명단 들어갈 예정</div> -->
        <div>
            <table>
                <thread>
                    <tr>
                    <th>Position</th>
                    <th>Player Name</th>
                    </tr>
                </thread>
                <tbody id="player-list"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let pieChart = null;
    let barChart = null;

    // 페이지 로드 시 연도 목록 불러오기
    window.addEventListener('DOMContentLoaded', loadYears);

    function loadYears() {
        fetch('game_roster.php?action=getYears')
            .then(res => res.json())
            .then(years => {
                const select = document.getElementById('yearSelect');
                years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = `${year}`;
                    select.appendChild(option);
                });
            })
            .catch(err => showError('연도 목록을 불러오는데 실패했습니다.'));
    }

    // 연도 선택 시 경기 목록 불러오기
    document.getElementById('yearSelect').addEventListener('change', function() {
        const yearID = this.value;
        const gameSelect = document.getElementById('gameSelect');
        const searchBtn = document.getElementById('btn');

        gameSelect.innerHTML = '<option value="">Select Game</option>';
        gameSelect.disabled = true;
        searchBtn.disabled = true;

        if (yearID) {
            fetch(`game_roster.php?action=getGames&yearID=${yearID}`)
                .then(res => res.json())
                .then(games => {
                    games.forEach(gameID => {
                        const option = document.createElement('option');
                        option.value = gameID;
                        const gameType = gameID.includes('ALS') ? 'American League All-Star' :
                            gameID.includes('NLS') ? 'National League All-Star' : gameID;
                        option.textContent = gameType;
                        gameSelect.appendChild(option);
                    });
                    gameSelect.disabled = false;
                })
                .catch(err => {
                    console.error('fetch error:', err);
                    showError('경기 목록을 불러오는데 실패했습니다.');
                }); 
        }
    });

    // 경기 선택 시 버튼 활성화
    document.getElementById('gameSelect').addEventListener('change', function() {
        const searchBtn = document.getElementById('btn');
        searchBtn.disabled = !this.value;
    });

    // 조회 버튼 클릭
    document.getElementById('btn').addEventListener('click', function() {
        const gameID = document.getElementById('gameSelect').value;
        var num=3;
        if (!gameID) return;

        showLoading(true);
        hideError();
        hideResults();

        fetch(`game_roster.php?action=getRoster&gameID=${gameID}`)
            .then(res => res.json())
            .then(data => {
                showLoading(false);
                console.log(data);
                if (data.error) {
                    console.log(data.error);
                } 
                else if (data.success) {
                    displayResults(data.roster);
                    num=data.roster.length-1;
                }
            })
            .then(fetch(`game_roster.php?action=getPlayers&gameID=${gameID}`)
                .then(res => res.json())
                .then(data => {
                    showLoading(false);
                    console.log(data);
                    if (data.error) {
                        console.log(data.error);
                    } 
                    else if (data.success) {
                        console.log(num);
                        displayList(data.players, num);
                    }
                })
            )
            .catch(err => {
                showLoading(false);
                console.error('fetch error:', err);
                showError('데이터를 불러오는데 실패했습니다.');
            });
            
        
    });

    function displayResults(roster) {
        console.log("roster: ",roster);
        const positions = roster.filter(r => r.position !== 'Total');
        const total = roster.find(r => r.position === 'Total');
        const totalCount = total ? parseInt(total.playerCount) : 0;
        console.log(totalCount);
        displayPieChart(positions, totalCount);
        // displayBarChart(positions);
        displayTable(roster, totalCount);
        

        document.getElementById('resultsContainer').classList.add('active');
    }

    function displayPieChart(positions, totalCount) {
        const ctx = document.getElementById('pieChart').getContext('2d');

        if (pieChart) {
            pieChart.destroy();
        }

        const labels = positions.map(p => p.position);
        const data = positions.map(p => parseInt(p.playerCount));

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                        'rgba(255, 99, 255, 0.8)',
                        'rgba(99, 255, 132, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                color: "#FFFF"
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = ((value / totalCount) * 100).toFixed(1);
                                return `${label}: ${value}명 (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function displayTable(roster, totalCount) {
        const tbody = document.getElementById('rosterTableBody');
        tbody.innerHTML = '';

        roster.forEach(item => {
            const row = tbody.insertRow();
            const isTotal = item.position === 'Total';

            if (isTotal) {
                row.classList.add('total-row');
            }

            const count = parseInt(item.playerCount);
            const percentage = totalCount > 0 ? ((count *100)/ totalCount).toFixed(1) : '0.0';

            row.innerHTML = `
                <td><span class="position-badge">${item.position}</span></td>
                <td><strong>${count}</strong></td>
                <td>${isTotal ? '100.0' : percentage}%</td>
            `;
        });
    }
    function displayList(players,positionNum) {
        const tbody = document.getElementById('player-list');
        tbody.innerHTML = `
              
                `
        
        for (let i=0;i<positionNum;i++){
            const row = tbody.insertRow();
            row.innerHTML = `
                <tr>
                    <td>${i}</td>
                    <td><span id="player-name-${i}"> </span></td>
                </tr>
                `;
            }

        players.forEach(item => {
            // const row = tbody.insertRow();
            const playerName = (item.firstName +" "+item.lastName);
            const position = item.position;
            const nameList = document.getElementById(`player-name-${position}`);
            nameList.append(playerName,", ");
        });
    }
    function showLoading(show) {
        document.getElementById('loadingDiv').style.display = show ? 'block' : 'none';
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorDiv');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    function hideError() {
        document.getElementById('errorDiv').style.display = 'none';
    }

    function hideResults() {
            document.getElementById('resultsContainer').classList.remove('active');
            console.log(document.getElementById('resultsContainer').classList);
    }
    </script>


        <?php
        $conn->close();
        ?>
    </div>
</body>


</html>