<?php
/*
 * player_trend.php: Window 함수를 사용하여 특정 선수의 연도별 성적 추이를 보여줌
 * 프론트엔드: 팀 선택 → 선수 선택 (드롭다운), 꺾은선 그래프
 */

// 1. DB 연결
require_once 'db_connect.php';

// 2. AJAX 요청 처리 (팀 목록, 선수 목록, 성적 데이터)
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

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

    // 특정 팀의 선수 목록 조회 (데이터가 있는 선수만)
    if ($_GET['action'] === 'getPlayers' && isset($_GET['teamID'])) {
        $teamID = $_GET['teamID'];
        $sql = "SELECT DISTINCT b.playerID, m.nameFirst, m.nameLast
                FROM Batting b
                JOIN Master m ON b.playerID = m.playerID
                WHERE b.teamID = ? AND b.AB > 25
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

    // 선수 성적 데이터 조회 (연도별 합산 - stint 처리)
    if ($_GET['action'] === 'getStats' && isset($_GET['playerID'])) {
        $playerID = $_GET['playerID'];

        // 단계 1: 연도별 집계 데이터 먼저 생성
        $aggregateSql = "
            SELECT
                playerID,
                yearID,
                SUM(G) AS total_G,
                SUM(AB) AS total_AB,
                SUM(H) AS total_H,
                SUM(HR) AS total_HR,
                CASE WHEN SUM(AB) > 0 THEN ROUND(SUM(H) / SUM(AB), 3) ELSE 0.000 END AS battingAvg
            FROM
                Batting
            WHERE
                playerID = ?
            GROUP BY
                playerID, yearID
            HAVING
                SUM(AB) >= 25
        ";

        $stmt = $conn->prepare($aggregateSql);
        if ($stmt === false) {
            echo json_encode(['error' => '쿼리 준비 오류: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("s", $playerID);
        $stmt->execute();
        $result = $stmt->get_result();
        $aggregateData = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($aggregateData)) {
            echo json_encode(['error' => "'$playerID'에 대한 데이터가 없거나, 연간 AB 25 이상인 시즌이 없습니다.'"]);
            exit;
        }

        // 단계 2: 각 연도별로 팀 이름 조회 및 Window 함수 계산 (PHP에서 처리)
        $stats = [];
        $prevHR = 0;
        $prevAvg = 0.0;

        foreach ($aggregateData as $index => $row) {
            $yearID = $row['yearID'];
            $currentHR = (int)$row['total_HR'];
            $currentAvg = (float)$row['battingAvg'];

            // 해당 연도에 가장 많이 뛴 팀 조회
            $teamSql = "
                SELECT t.name
                FROM Batting b
                JOIN Teams t ON b.teamID = t.teamID AND b.yearID = t.yearID AND b.lgID = t.lgID
                WHERE b.playerID = ? AND b.yearID = ?
                ORDER BY b.AB DESC
                LIMIT 1
            ";
            $teamStmt = $conn->prepare($teamSql);
            $teamStmt->bind_param("si", $playerID, $yearID);
            $teamStmt->execute();
            $teamResult = $teamStmt->get_result();
            $teamRow = $teamResult->fetch_assoc();
            $teamName = $teamRow ? $teamRow['name'] : 'Unknown';
            $teamStmt->close();

            // Window 함수 역할: 이전 값과 비교
            $hrChange = $currentHR - $prevHR;
            $avgChange = round($currentAvg - $prevAvg, 3);

            $stats[] = [
                'yearID' => $yearID,
                'teamName' => $teamName,
                'G' => $row['total_G'],
                'AB' => $row['total_AB'],
                'H' => $row['total_H'],
                'HR' => $currentHR,
                'hr_change' => $hrChange,
                'battingAvg' => number_format($currentAvg, 3),
                'avg_change' => number_format($avgChange, 3)
            ];

            // 다음 반복을 위해 현재 값 저장
            $prevHR = $currentHR;
            $prevAvg = $currentAvg;
        }

        echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

?>