<?php
/*
 * game_roster.php: GROUP BY WITH ROLLUP을 사용하여 경기별 출전 명단과 포지션별 인원수를 집계
 * 프론트엔드: 연도 선택 → 경기 선택 (드롭다운), 원 그래프
 */

// 1. DB 연결
require_once 'db_connect.php';

// 2. AJAX 요청 처리
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 연도 목록 조회
    if ($_GET['action'] === 'getYears') {
        $sql = "SELECT DISTINCT yearID FROM AllstarFull ORDER BY yearID DESC";
        $result = $conn->query($sql);
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['yearID'];
        }
        echo json_encode($years);
        exit;
    }

    // 특정 연도의 경기 목록 조회
    if ($_GET['action'] === 'getGames' && isset($_GET['yearID'])) {
        $yearID = $_GET['yearID'];
        $sql = "SELECT DISTINCT gameID FROM AllstarFull WHERE yearID = ? ORDER BY gameID";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $yearID);
        $stmt->execute();
        $result = $stmt->get_result();
        $games = [];
        while ($row = $result->fetch_assoc()) {
            $games[] = $row['gameID'];
        }
        echo json_encode($games);
        exit;
    }

    // 경기별 포지션 분포 데이터 조회
    if ($_GET['action'] === 'getRoster' && isset($_GET['gameID'])) {
        $gameID = $_GET['gameID'];

        $sql = "
            SELECT
                IF(GROUPING(a.startingPos) = 1, '총계 (Total)', a.startingPos) AS position,
                IF(GROUPING(m.nameFirst) = 1, '', m.nameFirst) AS firstName,
                IF(GROUPING(m.nameLast) = 1, '', m.nameLast) AS lastName,
                COUNT(a.playerID) AS playerCount
            FROM
                AllstarFull a
            JOIN
                Master m ON a.playerID = m.playerID
            WHERE
                a.gameID = ?
            GROUP BY
                a.startingPos, m.nameFirst, m.nameLast WITH ROLLUP
            HAVING
                GROUPING(a.startingPos) = 1 OR GROUPING(m.nameFirst) = 1
            ORDER BY
                GROUPING(a.startingPos) ASC,
                playerCount DESC,
                position ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['error' => '쿼리 준비 오류: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("s", $gameID);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $roster = $result->fetch_all(MYSQLI_ASSOC);

            if (empty($roster)) {
                echo json_encode(['error' => "'$gameID'에 대한 데이터가 없습니다."]);
            } else {
                echo json_encode(['success' => true, 'roster' => $roster]);
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
<!--    <title>경기별 출전 명단 및 포지션 분포</title>-->
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
<!--            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);-->
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
<!--            border-color: #f5576c;-->
<!--            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);-->
<!--        }-->
<!---->
<!--        select:disabled {-->
<!--            background: #e9ecef;-->
<!--            cursor: not-allowed;-->
<!--        }-->
<!---->
<!--        button {-->
<!--            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);-->
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
<!--            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);-->
<!--        }-->
<!---->
<!--        button:disabled {-->
<!--            background: #ccc;-->
<!--            cursor: not-allowed;-->
<!--            transform: none;-->
<!--        }-->
<!---->
<!--        .results-container {-->
<!--            display: none;-->
<!--        }-->
<!---->
<!--        .results-container.active {-->
<!--            display: block;-->
<!--        }-->
<!---->
<!--        .charts-wrapper {-->
<!--            display: grid;-->
<!--            grid-template-columns: 1fr 1fr;-->
<!--            gap: 30px;-->
<!--            margin-bottom: 30px;-->
<!--        }-->
<!---->
<!--        @media (max-width: 768px) {-->
<!--            .charts-wrapper {-->
<!--                grid-template-columns: 1fr;-->
<!--            }-->
<!--        }-->
<!---->
<!--        .chart-container {-->
<!--            background: white;-->
<!--            padding: 20px;-->
<!--            border-radius: 15px;-->
<!--            box-shadow: 0 2px 10px rgba(0,0,0,0.05);-->
<!--        }-->
<!---->
<!--        .chart-wrapper {-->
<!--            position: relative;-->
<!--            height: 350px;-->
<!--        }-->
<!---->
<!--        .roster-table {-->
<!--            width: 100%;-->
<!--            border-collapse: collapse;-->
<!--            background: white;-->
<!--            border-radius: 10px;-->
<!--            overflow: hidden;-->
<!--            box-shadow: 0 2px 10px rgba(0,0,0,0.05);-->
<!--        }-->
<!---->
<!--        .roster-table th {-->
<!--            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);-->
<!--            color: white;-->
<!--            padding: 15px;-->
<!--            text-align: left;-->
<!--            font-weight: 600;-->
<!--        }-->
<!---->
<!--        .roster-table td {-->
<!--            padding: 12px 15px;-->
<!--            border-bottom: 1px solid #f0f0f0;-->
<!--        }-->
<!---->
<!--        .roster-table tr:hover {-->
<!--            background: #f8f9fa;-->
<!--        }-->
<!---->
<!--        .roster-table tr.total-row {-->
<!--            background: #f8f9fa;-->
<!--            font-weight: 700;-->
<!--            font-size: 1.1em;-->
<!--        }-->
<!---->
<!--        .roster-table tr.total-row td {-->
<!--            border-top: 3px solid #f5576c;-->
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
<!--            color: #f5576c;-->
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
<!---->
<!--        .position-badge {-->
<!--            display: inline-block;-->
<!--            padding: 4px 10px;-->
<!--            border-radius: 12px;-->
<!--            font-weight: 600;-->
<!--            font-size: 0.9em;-->
<!--        }-->
<!--    </style>-->
<!--</head>-->
<!--<body>-->
<!--<div class="container">-->
<!--    <h1> 경기별 출전 명단 및 포지션 분포</h1>-->
<!--    <p class="subtitle">GROUP BY WITH ROLLUP을 활용한 포지션별 인원 집계 및 시각화</p>-->
<!---->
<!--    <div class="form-section">-->
<!--        <div class="form-row">-->
<!--            <div class="form-group">-->
<!--                <label for="yearSelect"> 연도 선택</label>-->
<!--                <select id="yearSelect">-->
<!--                    <option value="">연도를 선택하세요</option>-->
<!--                </select>-->
<!--            </div>-->
<!---->
<!--            <div class="form-group">-->
<!--                <label for="gameSelect"> 경기 선택</label>-->
<!--                <select id="gameSelect" disabled>-->
<!--                    <option value="">먼저 연도를 선택하세요</option>-->
<!--                </select>-->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--        <button id="searchBtn" disabled> 포지션 분포 조회</button>-->
<!--    </div>-->
<!---->
<!--    <div id="loadingDiv" class="loading" style="display:none;">-->
<!--        데이터를 불러오는 중입니다-->
<!--    </div>-->
<!---->
<!--    <div id="errorDiv" class="error-message" style="display:none;"></div>-->
<!---->
<!--    <div id="resultsContainer" class="results-container">-->
<!--        <div class="charts-wrapper">-->
<!--            <div class="chart-container">-->
<!--                <h2 style="margin-bottom: 15px; color: #333;"> 포지션 분포 (원 그래프)</h2>-->
<!--                <div class="chart-wrapper">-->
<!--                    <canvas id="pieChart"></canvas>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <div class="chart-container">-->
<!--                <h2 style="margin-bottom: 15px; color: #333;"> 포지션별 인원 (막대 그래프)</h2>-->
<!--                <div class="chart-wrapper">-->
<!--                    <canvas id="barChart"></canvas>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--        <h2 style="margin-bottom: 15px; color: #333;"> 상세 데이터 테이블</h2>-->
<!--        <table class="roster-table">-->
<!--            <thead>-->
<!--            <tr>-->
<!--                <th>포지션</th>-->
<!--                <th>인원수</th>-->
<!--                <th>비율</th>-->
<!--            </tr>-->
<!--            </thead>-->
<!--            <tbody id="rosterTableBody"></tbody>-->
<!--        </table>-->
<!--    </div>-->
<!--</div>-->
<!---->
<!--<script>-->
<!--    let pieChart = null;-->
<!--    let barChart = null;-->
<!---->
<!--    // 페이지 로드 시 연도 목록 불러오기-->
<!--    window.addEventListener('DOMContentLoaded', loadYears);-->
<!---->
<!--    function loadYears() {-->
<!--        fetch('?action=getYears')-->
<!--            .then(res => res.json())-->
<!--            .then(years => {-->
<!--                const select = document.getElementById('yearSelect');-->
<!--                years.forEach(year => {-->
<!--                    const option = document.createElement('option');-->
<!--                    option.value = year;-->
<!--                    option.textContent = `${year}년`;-->
<!--                    select.appendChild(option);-->
<!--                });-->
<!--            })-->
<!--            .catch(err => showError('연도 목록을 불러오는데 실패했습니다.'));-->
<!--    }-->
<!---->
<!--    // 연도 선택 시 경기 목록 불러오기-->
<!--    document.getElementById('yearSelect').addEventListener('change', function() {-->
<!--        const yearID = this.value;-->
<!--        const gameSelect = document.getElementById('gameSelect');-->
<!--        const searchBtn = document.getElementById('searchBtn');-->
<!---->
<!--        gameSelect.innerHTML = '<option value="">경기를 선택하세요</option>';-->
<!--        gameSelect.disabled = true;-->
<!--        searchBtn.disabled = true;-->
<!---->
<!--        if (yearID) {-->
<!--            fetch(`?action=getGames&yearID=${yearID}`)-->
<!--                .then(res => res.json())-->
<!--                .then(games => {-->
<!--                    games.forEach(gameID => {-->
<!--                        const option = document.createElement('option');-->
<!--                        option.value = gameID;-->
<!--                        // gameID 형식: 2015ALS (연도+리그+경기유형)-->
<!--                        const gameType = gameID.includes('ALS') ? 'American League All-Star' :-->
<!--                            gameID.includes('NLS') ? 'National League All-Star' : gameID;-->
<!--                        option.textContent = gameType;-->
<!--                        gameSelect.appendChild(option);-->
<!--                    });-->
<!--                    gameSelect.disabled = false;-->
<!--                })-->
<!--                .catch(err => showError('경기 목록을 불러오는데 실패했습니다.'));-->
<!--        }-->
<!--    });-->
<!---->
<!--    // 경기 선택 시 버튼 활성화-->
<!--    document.getElementById('gameSelect').addEventListener('change', function() {-->
<!--        const searchBtn = document.getElementById('searchBtn');-->
<!--        searchBtn.disabled = !this.value;-->
<!--    });-->
<!---->
<!--    // 조회 버튼 클릭-->
<!--    document.getElementById('searchBtn').addEventListener('click', function() {-->
<!--        const gameID = document.getElementById('gameSelect').value;-->
<!--        if (!gameID) return;-->
<!---->
<!--        showLoading(true);-->
<!--        hideError();-->
<!--        hideResults();-->
<!---->
<!--        fetch(`?action=getRoster&gameID=${gameID}`)-->
<!--            .then(res => res.json())-->
<!--            .then(data => {-->
<!--                showLoading(false);-->
<!---->
<!--                if (data.error) {-->
<!--                    showError(data.error);-->
<!--                } else if (data.success) {-->
<!--                    displayResults(data.roster);-->
<!--                }-->
<!--            })-->
<!--            .catch(err => {-->
<!--                showLoading(false);-->
<!--                showError('데이터를 불러오는데 실패했습니다.');-->
<!--            });-->
<!--    });-->
<!---->
<!--    function displayResults(roster) {-->
<!--        // 총계 행 제거하고 포지션별 데이터만 추출-->
<!--        const positions = roster.filter(r => r.position !== '총계 (Total)');-->
<!--        const total = roster.find(r => r.position === '총계 (Total)');-->
<!--        const totalCount = total ? parseInt(total.playerCount) : 0;-->
<!---->
<!--        // 원 그래프 표시-->
<!--        displayPieChart(positions, totalCount);-->
<!---->
<!--        // 막대 그래프 표시-->
<!--        displayBarChart(positions);-->
<!---->
<!--        // 테이블 표시-->
<!--        displayTable(roster, totalCount);-->
<!---->
<!--        document.getElementById('resultsContainer').classList.add('active');-->
<!--    }-->
<!---->
<!--    function displayPieChart(positions, totalCount) {-->
<!--        const ctx = document.getElementById('pieChart').getContext('2d');-->
<!---->
<!--        if (pieChart) {-->
<!--            pieChart.destroy();-->
<!--        }-->
<!---->
<!--        const labels = positions.map(p => p.position);-->
<!--        const data = positions.map(p => parseInt(p.playerCount));-->
<!--        const percentages = positions.map(p => ((parseInt(p.playerCount) / totalCount) * 100).toFixed(1));-->
<!---->
<!--        pieChart = new Chart(ctx, {-->
<!--            type: 'pie',-->
<!--            data: {-->
<!--                labels: labels,-->
<!--                datasets: [{-->
<!--                    data: data,-->
<!--                    backgroundColor: [-->
<!--                        'rgba(255, 99, 132, 0.8)',-->
<!--                        'rgba(54, 162, 235, 0.8)',-->
<!--                        'rgba(255, 206, 86, 0.8)',-->
<!--                        'rgba(75, 192, 192, 0.8)',-->
<!--                        'rgba(153, 102, 255, 0.8)',-->
<!--                        'rgba(255, 159, 64, 0.8)',-->
<!--                        'rgba(199, 199, 199, 0.8)',-->
<!--                        'rgba(83, 102, 255, 0.8)',-->
<!--                        'rgba(255, 99, 255, 0.8)',-->
<!--                        'rgba(99, 255, 132, 0.8)'-->
<!--                    ],-->
<!--                    borderWidth: 2,-->
<!--                    borderColor: '#fff'-->
<!--                }]-->
<!--            },-->
<!--            options: {-->
<!--                responsive: true,-->
<!--                maintainAspectRatio: false,-->
<!--                plugins: {-->
<!--                    title: {-->
<!--                        display: false-->
<!--                    },-->
<!--                    legend: {-->
<!--                        position: 'bottom',-->
<!--                        labels: {-->
<!--                            padding: 15,-->
<!--                            font: {-->
<!--                                size: 12-->
<!--                            }-->
<!--                        }-->
<!--                    },-->
<!--                    tooltip: {-->
<!--                        callbacks: {-->
<!--                            label: function(context) {-->
<!--                                const label = context.label || '';-->
<!--                                const value = context.parsed || 0;-->
<!--                                const percentage = ((value / totalCount) * 100).toFixed(1);-->
<!--                                return `${label}: ${value}명 (${percentage}%)`;-->
<!--                            }-->
<!--                        }-->
<!--                    }-->
<!--                }-->
<!--            }-->
<!--        });-->
<!--    }-->
<!---->
<!--    function displayBarChart(positions) {-->
<!--        const ctx = document.getElementById('barChart').getContext('2d');-->
<!---->
<!--        if (barChart) {-->
<!--            barChart.destroy();-->
<!--        }-->
<!---->
<!--        const labels = positions.map(p => p.position);-->
<!--        const data = positions.map(p => parseInt(p.playerCount));-->
<!---->
<!--        barChart = new Chart(ctx, {-->
<!--            type: 'bar',-->
<!--            data: {-->
<!--                labels: labels,-->
<!--                datasets: [{-->
<!--                    label: '인원수',-->
<!--                    data: data,-->
<!--                    backgroundColor: 'rgba(245, 87, 108, 0.8)',-->
<!--                    borderColor: 'rgba(245, 87, 108, 1)',-->
<!--                    borderWidth: 1-->
<!--                }]-->
<!--            },-->
<!--            options: {-->
<!--                responsive: true,-->
<!--                maintainAspectRatio: false,-->
<!--                plugins: {-->
<!--                    legend: {-->
<!--                        display: false-->
<!--                    }-->
<!--                },-->
<!--                scales: {-->
<!--                    y: {-->
<!--                        beginAtZero: true,-->
<!--                        ticks: {-->
<!--                            stepSize: 1-->
<!--                        },-->
<!--                        title: {-->
<!--                            display: true,-->
<!--                            text: '인원 수'-->
<!--                        }-->
<!--                    },-->
<!--                    x: {-->
<!--                        title: {-->
<!--                            display: true,-->
<!--                            text: '포지션'-->
<!--                        }-->
<!--                    }-->
<!--                }-->
<!--            }-->
<!--        });-->
<!--    }-->
<!---->
<!--    function displayTable(roster, totalCount) {-->
<!--        const tbody = document.getElementById('rosterTableBody');-->
<!--        tbody.innerHTML = '';-->
<!---->
<!--        roster.forEach(item => {-->
<!--            const row = tbody.insertRow();-->
<!--            const isTotal = item.position === '총계 (Total)';-->
<!---->
<!--            if (isTotal) {-->
<!--                row.classList.add('total-row');-->
<!--            }-->
<!---->
<!--            const count = parseInt(item.playerCount);-->
<!--            const percentage = totalCount > 0 ? ((count / totalCount) * 100).toFixed(1) : '0.0';-->
<!---->
<!--            row.innerHTML = `-->
<!--                    <td><span class="position-badge" style="background: ${isTotal ? '#f5576c' : '#e9ecef'}; color: ${isTotal ? 'white' : '#333'}">${item.position}</span></td>-->
<!--                    <td><strong>${count}명</strong></td>-->
<!--                    <td>${isTotal ? '100.0' : percentage}%</td>-->
<!--                `;-->
<!--        });-->
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
<!--        document.getElementById('resultsContainer').classList.remove('active');-->
<!--    }-->
<!--</script>-->
<!--</body>-->
<!--</html>-->