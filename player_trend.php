<?php
/*
 * player_trend.php: Window 함수를 사용하여 특정 선수의 연도별 성적 추이를 보여줌
 * 프론트엔드: 팀 선택 → 선수 선택 (드롭다운), 꺾은선 그래프
 */

// 1. DB 연결
require_once 'db_connect.php';

// 2. AJAX 요청 처리 (팀 목록, 선수 목록, 성적 데이터)
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 팀 목록 조회
    if ($_GET['action'] === 'getTeams') {
        $sql = "SELECT DISTINCT t.teamID, t.name 
                FROM Teams t 
                JOIN Batting b ON t.teamID = b.teamID AND t.yearID = b.yearID
                ORDER BY t.name";
        $result = $conn->query($sql);
        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
        echo json_encode($teams);
        exit;
    }

    // 특정 팀의 선수 목록 조회
    if ($_GET['action'] === 'getPlayers' && isset($_GET['teamID'])) {
        $teamID = $_GET['teamID'];
        $sql = "SELECT DISTINCT b.playerID, m.nameFirst, m.nameLast
                FROM Batting b
                JOIN Master m ON b.playerID = m.playerID
                WHERE b.teamID = ?
                ORDER BY m.nameLast, m.nameFirst";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $teamID);
        $stmt->execute();
        $result = $stmt->get_result();
        $players = [];
        while ($row = $result->fetch_assoc()) {
            $players[] = [
                'playerID' => $row['playerID'],
                'fullName' => $row['nameFirst'] . ' ' . $row['nameLast']
            ];
        }
        echo json_encode($players);
        exit;
    }

    // 선수 성적 데이터 조회
    if ($_GET['action'] === 'getStats' && isset($_GET['playerID'])) {
        $playerID = $_GET['playerID'];

        $sql = "
            WITH PlayerStats AS (
                SELECT
                    yearID,
                    teamID,
                    G,
                    AB,
                    H,
                    HR,
                    RBI,
                    SO,
                    (H / NULLIF(AB, 0)) AS battingAvg,
                    LAG(HR, 1, 0) OVER (PARTITION BY playerID ORDER BY yearID) AS prev_HR,
                    LAG(RBI, 1, 0) OVER (PARTITION BY playerID ORDER BY yearID) AS prev_RBI,
                    LAG((H / NULLIF(AB, 0)), 1, 0.0) OVER (PARTITION BY playerID ORDER BY yearID) AS prev_battingAvg
                FROM
                    Batting
                WHERE
                    playerID = ?
            )
            SELECT
                p.yearID,
                t.name AS teamName,
                p.G,
                p.AB,
                p.H,
                p.HR,
                (p.HR - p.prev_HR) AS hr_change,
                p.RBI,
                (p.RBI - p.prev_RBI) AS rbi_change,
                p.battingAvg,
                (p.battingAvg - p.prev_battingAvg) AS avg_change,
                p.SO
            FROM
                PlayerStats p
            JOIN
                Teams t ON p.teamID = t.teamID AND p.yearID = t.yearID
            WHERE
                p.AB > 50
            ORDER BY
                p.yearID ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['error' => '쿼리 준비 오류: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("s", $playerID);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $stats = $result->fetch_all(MYSQLI_ASSOC);

            if (empty($stats)) {
                echo json_encode(['error' => "'$playerID'에 대한 데이터가 없거나, AB 50 이상인 시즌이 없습니다."]);
            } else {
                echo json_encode(['success' => true, 'stats' => $stats]);
            }
        } else {
            echo json_encode(['error' => '쿼리 실행 오류: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

?>
<!--테스트용 HTML 코드-->
<!--<!DOCTYPE html>-->
<!--<html lang="ko">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <title>선수 성적 추이 (Window 함수)</title>-->
<!--    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
<!--    <style>-->
<!--        * {-->
<!--            margin: 0;-->
<!--            padding: 0;-->
<!--            box-sizing: border-box;-->
<!--        }-->
<!---->
<!--        body {-->
<!--            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;-->
<!--            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);-->
<!--            min-height: 100vh;-->
<!--            padding: 20px;-->
<!--        }-->
<!---->
<!--        .container {-->
<!--            max-width: 1200px;-->
<!--            margin: 0 auto;-->
<!--            background: white;-->
<!--            border-radius: 20px;-->
<!--            box-shadow: 0 20px 60px rgba(0,0,0,0.3);-->
<!--            padding: 40px;-->
<!--        }-->
<!---->
<!--        h1 {-->
<!--            color: #333;-->
<!--            margin-bottom: 10px;-->
<!--            font-size: 2.5em;-->
<!--        }-->
<!---->
<!--        .subtitle {-->
<!--            color: #666;-->
<!--            margin-bottom: 30px;-->
<!--            font-size: 1.1em;-->
<!--        }-->
<!---->
<!--        .form-section {-->
<!--            background: #f8f9fa;-->
<!--            padding: 30px;-->
<!--            border-radius: 15px;-->
<!--            margin-bottom: 30px;-->
<!--        }-->
<!---->
<!--        .form-row {-->
<!--            display: flex;-->
<!--            gap: 20px;-->
<!--            margin-bottom: 20px;-->
<!--            flex-wrap: wrap;-->
<!--        }-->
<!---->
<!--        .form-group {-->
<!--            flex: 1;-->
<!--            min-width: 200px;-->
<!--        }-->
<!---->
<!--        label {-->
<!--            display: block;-->
<!--            font-weight: 600;-->
<!--            color: #333;-->
<!--            margin-bottom: 8px;-->
<!--            font-size: 0.95em;-->
<!--        }-->
<!---->
<!--        select, button {-->
<!--            width: 100%;-->
<!--            padding: 12px 15px;-->
<!--            border: 2px solid #ddd;-->
<!--            border-radius: 8px;-->
<!--            font-size: 1em;-->
<!--            transition: all 0.3s;-->
<!--        }-->
<!---->
<!--        select:focus {-->
<!--            outline: none;-->
<!--            border-color: #667eea;-->
<!--            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);-->
<!--        }-->
<!---->
<!--        select:disabled {-->
<!--            background: #e9ecef;-->
<!--            cursor: not-allowed;-->
<!--        }-->
<!---->
<!--        button {-->
<!--            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);-->
<!--            color: white;-->
<!--            border: none;-->
<!--            cursor: pointer;-->
<!--            font-weight: 600;-->
<!--            text-transform: uppercase;-->
<!--            letter-spacing: 1px;-->
<!--        }-->
<!---->
<!--        button:hover:not(:disabled) {-->
<!--            transform: translateY(-2px);-->
<!--            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);-->
<!--        }-->
<!---->
<!--        button:disabled {-->
<!--            background: #ccc;-->
<!--            cursor: not-allowed;-->
<!--            transform: none;-->
<!--        }-->
<!---->
<!--        .chart-container {-->
<!--            background: white;-->
<!--            padding: 20px;-->
<!--            border-radius: 15px;-->
<!--            margin-bottom: 30px;-->
<!--            box-shadow: 0 2px 10px rgba(0,0,0,0.05);-->
<!--            display: none;-->
<!--        }-->
<!---->
<!--        .chart-container.active {-->
<!--            display: block;-->
<!--        }-->
<!---->
<!--        .chart-wrapper {-->
<!--            position: relative;-->
<!--            height: 400px;-->
<!--        }-->
<!---->
<!--        .stats-table {-->
<!--            width: 100%;-->
<!--            border-collapse: collapse;-->
<!--            margin-top: 20px;-->
<!--            background: white;-->
<!--            border-radius: 10px;-->
<!--            overflow: hidden;-->
<!--            box-shadow: 0 2px 10px rgba(0,0,0,0.05);-->
<!--            display: none;-->
<!--        }-->
<!---->
<!--        .stats-table.active {-->
<!--            display: table;-->
<!--        }-->
<!---->
<!--        .stats-table th {-->
<!--            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);-->
<!--            color: white;-->
<!--            padding: 15px;-->
<!--            text-align: left;-->
<!--            font-weight: 600;-->
<!--        }-->
<!---->
<!--        .stats-table td {-->
<!--            padding: 12px 15px;-->
<!--            border-bottom: 1px solid #f0f0f0;-->
<!--        }-->
<!---->
<!--        .stats-table tr:hover {-->
<!--            background: #f8f9fa;-->
<!--        }-->
<!---->
<!--        .change-up {-->
<!--            color: #28a745;-->
<!--            font-weight: 600;-->
<!--        }-->
<!---->
<!--        .change-down {-->
<!--            color: #dc3545;-->
<!--            font-weight: 600;-->
<!--        }-->
<!---->
<!--        .change-zero {-->
<!--            color: #6c757d;-->
<!--        }-->
<!---->
<!--        .error-message {-->
<!--            background: #f8d7da;-->
<!--            color: #721c24;-->
<!--            padding: 15px 20px;-->
<!--            border-radius: 8px;-->
<!--            margin-top: 20px;-->
<!--            border-left: 4px solid #dc3545;-->
<!--        }-->
<!---->
<!--        .loading {-->
<!--            text-align: center;-->
<!--            padding: 40px;-->
<!--            color: #667eea;-->
<!--            font-size: 1.2em;-->
<!--        }-->
<!---->
<!--        .loading::after {-->
<!--            content: '';-->
<!--            animation: dots 1.5s steps(4, end) infinite;-->
<!--        }-->
<!---->
<!--        @keyframes dots {-->
<!--            0%, 20% { content: '.'; }-->
<!--            40% { content: '..'; }-->
<!--            60%, 100% { content: '...'; }-->
<!--        }-->
<!--    </style>-->
<!--</head>-->
<!--<body>-->
<!--<div class="container">-->
<!--    <h1> 선수 연도별 성적 추이</h1>-->
<!--    <p class="subtitle">Window 함수(LAG)를 활용한 선수별 성적 분석 및 추이 시각화</p>-->
<!---->
<!--    <div class="form-section">-->
<!--        <div class="form-row">-->
<!--            <div class="form-group">-->
<!--                <label for="teamSelect"> 팀 선택</label>-->
<!--                <select id="teamSelect">-->
<!--                    <option value="">팀을 선택하세요</option>-->
<!--                </select>-->
<!--            </div>-->
<!---->
<!--            <div class="form-group">-->
<!--                <label for="playerSelect"> 선수 선택</label>-->
<!--                <select id="playerSelect" disabled>-->
<!--                    <option value="">먼저 팀을 선택하세요</option>-->
<!--                </select>-->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--        <button id="searchBtn" disabled> 성적 추이 조회</button>-->
<!--    </div>-->
<!---->
<!--    <div id="loadingDiv" class="loading" style="display:none;">-->
<!--        데이터를 불러오는 중입니다-->
<!--    </div>-->
<!---->
<!--    <div id="errorDiv" class="error-message" style="display:none;"></div>-->
<!---->
<!--    <div id="chartContainer" class="chart-container">-->
<!--        <h2 style="margin-bottom: 20px; color: #333;"> 성적 추이 그래프</h2>-->
<!--        <div class="chart-wrapper">-->
<!--            <canvas id="statsChart"></canvas>-->
<!--        </div>-->
<!--    </div>-->
<!---->
<!--    <table id="statsTable" class="stats-table">-->
<!--        <thead>-->
<!--        <tr>-->
<!--            <th>연도</th>-->
<!--            <th>팀</th>-->
<!--            <th>경기(G)</th>-->
<!--            <th>타수(AB)</th>-->
<!--            <th>안타(H)</th>-->
<!--            <th>타율(AVG)</th>-->
<!--            <th>타율 변화</th>-->
<!--            <th>홈런(HR)</th>-->
<!--            <th>홈런 변화</th>-->
<!--            <th>타점(RBI)</th>-->
<!--            <th>타점 변화</th>-->
<!--            <th>삼진(SO)</th>-->
<!--        </tr>-->
<!--        </thead>-->
<!--        <tbody id="statsTableBody"></tbody>-->
<!--    </table>-->
<!--</div>-->
<!---->
<!--<script>-->
<!--    let chart = null;-->
<!---->
<!--    // 페이지 로드 시 팀 목록 불러오기-->
<!--    window.addEventListener('DOMContentLoaded', loadTeams);-->
<!---->
<!--    function loadTeams() {-->
<!--        fetch('?action=getTeams')-->
<!--            .then(res => res.json())-->
<!--            .then(teams => {-->
<!--                const select = document.getElementById('teamSelect');-->
<!--                teams.forEach(team => {-->
<!--                    const option = document.createElement('option');-->
<!--                    option.value = team.teamID;-->
<!--                    option.textContent = `${team.name} (${team.teamID})`;-->
<!--                    select.appendChild(option);-->
<!--                });-->
<!--            })-->
<!--            .catch(err => showError('팀 목록을 불러오는데 실패했습니다.'));-->
<!--    }-->
<!---->
<!--    // 팀 선택 시 선수 목록 불러오기-->
<!--    document.getElementById('teamSelect').addEventListener('change', function() {-->
<!--        const teamID = this.value;-->
<!--        const playerSelect = document.getElementById('playerSelect');-->
<!--        const searchBtn = document.getElementById('searchBtn');-->
<!---->
<!--        playerSelect.innerHTML = '<option value="">선수를 선택하세요</option>';-->
<!--        playerSelect.disabled = true;-->
<!--        searchBtn.disabled = true;-->
<!---->
<!--        if (teamID) {-->
<!--            fetch(`?action=getPlayers&teamID=${teamID}`)-->
<!--                .then(res => res.json())-->
<!--                .then(players => {-->
<!--                    players.forEach(player => {-->
<!--                        const option = document.createElement('option');-->
<!--                        option.value = player.playerID;-->
<!--                        option.textContent = player.fullName;-->
<!--                        playerSelect.appendChild(option);-->
<!--                    });-->
<!--                    playerSelect.disabled = false;-->
<!--                })-->
<!--                .catch(err => showError('선수 목록을 불러오는데 실패했습니다.'));-->
<!--        }-->
<!--    });-->
<!---->
<!--    // 선수 선택 시 버튼 활성화-->
<!--    document.getElementById('playerSelect').addEventListener('change', function() {-->
<!--        const searchBtn = document.getElementById('searchBtn');-->
<!--        searchBtn.disabled = !this.value;-->
<!--    });-->
<!---->
<!--    // 조회 버튼 클릭-->
<!--    document.getElementById('searchBtn').addEventListener('click', function() {-->
<!--        const playerID = document.getElementById('playerSelect').value;-->
<!--        if (!playerID) return;-->
<!---->
<!--        showLoading(true);-->
<!--        hideError();-->
<!--        hideResults();-->
<!---->
<!--        fetch(`?action=getStats&playerID=${playerID}`)-->
<!--            .then(res => res.json())-->
<!--            .then(data => {-->
<!--                showLoading(false);-->
<!---->
<!--                if (data.error) {-->
<!--                    showError(data.error);-->
<!--                } else if (data.success) {-->
<!--                    displayChart(data.stats);-->
<!--                    displayTable(data.stats);-->
<!--                }-->
<!--            })-->
<!--            .catch(err => {-->
<!--                showLoading(false);-->
<!--                showError('데이터를 불러오는데 실패했습니다.');-->
<!--            });-->
<!--    });-->
<!---->
<!--    function displayChart(stats) {-->
<!--        const ctx = document.getElementById('statsChart').getContext('2d');-->
<!---->
<!--        // 기존 차트 제거-->
<!--        if (chart) {-->
<!--            chart.destroy();-->
<!--        }-->
<!---->
<!--        const years = stats.map(s => s.yearID);-->
<!--        const hrs = stats.map(s => parseFloat(s.HR));-->
<!--        const rbis = stats.map(s => parseFloat(s.RBI));-->
<!--        const avgs = stats.map(s => (parseFloat(s.battingAvg) * 1000).toFixed(0)); // 타율을 1000배-->
<!---->
<!--        chart = new Chart(ctx, {-->
<!--            type: 'line',-->
<!--            data: {-->
<!--                labels: years,-->
<!--                datasets: [-->
<!--                    {-->
<!--                        label: '홈런 (HR)',-->
<!--                        data: hrs,-->
<!--                        borderColor: 'rgb(255, 99, 132)',-->
<!--                        backgroundColor: 'rgba(255, 99, 132, 0.1)',-->
<!--                        tension: 0.4,-->
<!--                        yAxisID: 'y'-->
<!--                    },-->
<!--                    {-->
<!--                        label: '타점 (RBI)',-->
<!--                        data: rbis,-->
<!--                        borderColor: 'rgb(54, 162, 235)',-->
<!--                        backgroundColor: 'rgba(54, 162, 235, 0.1)',-->
<!--                        tension: 0.4,-->
<!--                        yAxisID: 'y'-->
<!--                    },-->
<!--                    {-->
<!--                        label: '타율 (x1000)',-->
<!--                        data: avgs,-->
<!--                        borderColor: 'rgb(75, 192, 192)',-->
<!--                        backgroundColor: 'rgba(75, 192, 192, 0.1)',-->
<!--                        tension: 0.4,-->
<!--                        yAxisID: 'y1'-->
<!--                    }-->
<!--                ]-->
<!--            },-->
<!--            options: {-->
<!--                responsive: true,-->
<!--                maintainAspectRatio: false,-->
<!--                interaction: {-->
<!--                    mode: 'index',-->
<!--                    intersect: false,-->
<!--                },-->
<!--                plugins: {-->
<!--                    title: {-->
<!--                        display: true,-->
<!--                        text: '연도별 성적 추이',-->
<!--                        font: { size: 16 }-->
<!--                    },-->
<!--                    legend: {-->
<!--                        display: true,-->
<!--                        position: 'top'-->
<!--                    }-->
<!--                },-->
<!--                scales: {-->
<!--                    y: {-->
<!--                        type: 'linear',-->
<!--                        display: true,-->
<!--                        position: 'left',-->
<!--                        title: {-->
<!--                            display: true,-->
<!--                            text: 'HR / RBI'-->
<!--                        }-->
<!--                    },-->
<!--                    y1: {-->
<!--                        type: 'linear',-->
<!--                        display: true,-->
<!--                        position: 'right',-->
<!--                        title: {-->
<!--                            display: true,-->
<!--                            text: '타율 (x1000)'-->
<!--                        },-->
<!--                        grid: {-->
<!--                            drawOnChartArea: false,-->
<!--                        }-->
<!--                    }-->
<!--                }-->
<!--            }-->
<!--        });-->
<!---->
<!--        document.getElementById('chartContainer').classList.add('active');-->
<!--    }-->
<!---->
<!--    function displayTable(stats) {-->
<!--        const tbody = document.getElementById('statsTableBody');-->
<!--        tbody.innerHTML = '';-->
<!---->
<!--        stats.forEach(stat => {-->
<!--            const row = tbody.insertRow();-->
<!--            row.innerHTML = `-->
<!--                    <td>${stat.yearID}</td>-->
<!--                    <td>${stat.teamName}</td>-->
<!--                    <td>${stat.G}</td>-->
<!--                    <td>${stat.AB}</td>-->
<!--                    <td>${stat.H}</td>-->
<!--                    <td>${parseFloat(stat.battingAvg).toFixed(3)}</td>-->
<!--                    <td>${formatChange(parseFloat(stat.avg_change), 3)}</td>-->
<!--                    <td>${stat.HR}</td>-->
<!--                    <td>${formatChange(parseFloat(stat.hr_change))}</td>-->
<!--                    <td>${stat.RBI}</td>-->
<!--                    <td>${formatChange(parseFloat(stat.rbi_change))}</td>-->
<!--                    <td>${stat.SO}</td>-->
<!--                `;-->
<!--        });-->
<!---->
<!--        document.getElementById('statsTable').classList.add('active');-->
<!--    }-->
<!---->
<!--    function formatChange(value, decimals = 0) {-->
<!--        if (value > 0) {-->
<!--            return `<span class="change-up">▲ +${value.toFixed(decimals)}</span>`;-->
<!--        } else if (value < 0) {-->
<!--            return `<span class="change-down">▼ ${value.toFixed(decimals)}</span>`;-->
<!--        } else {-->
<!--            return `<span class="change-zero">-</span>`;-->
<!--        }-->
<!--    }-->
<!---->
<!--    function showLoading(show) {-->
<!--        document.getElementById('loadingDiv').style.display = show ? 'block' : 'none';-->
<!--    }-->
<!---->
<!--    function showError(message) {-->
<!--        const errorDiv = document.getElementById('errorDiv');-->
<!--        errorDiv.textContent = message;-->
<!--        errorDiv.style.display = 'block';-->
<!--    }-->
<!---->
<!--    function hideError() {-->
<!--        document.getElementById('errorDiv').style.display = 'none';-->
<!--    }-->
<!---->
<!--    function hideResults() {-->
<!--        document.getElementById('chartContainer').classList.remove('active');-->
<!--        document.getElementById('statsTable').classList.remove('active');-->
<!--    }-->
<!--</script>-->
<!--</body>-->
<!--</html>-->