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

    // 선수 성적 데이터 조회 (DB 구조에 맞게: G, AB, H, HR만 사용)
    if ($_GET['action'] === 'getStats' && isset($_GET['playerID'])) {
        $playerID = $_GET['playerID'];

        $sql = "
            WITH PlayerStats AS (
                SELECT
                    playerID,
                    yearID,
                    teamID,
                    lgID,
                    G,
                    AB,
                    H,
                    HR,
                    CASE WHEN AB > 0 THEN ROUND(H / AB, 3) ELSE 0.000 END AS battingAvg,
                    LAG(HR, 1, 0) OVER (PARTITION BY playerID ORDER BY yearID) AS prev_HR,
                    LAG(CASE WHEN AB > 0 THEN ROUND(H / AB, 3) ELSE 0.000 END, 1, 0.0) OVER (PARTITION BY playerID ORDER BY yearID) AS prev_battingAvg
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
                ROUND(p.battingAvg, 3) AS battingAvg,
                ROUND(p.battingAvg - p.prev_battingAvg, 3) AS avg_change
            FROM
                PlayerStats p
            JOIN
                Teams t ON p.teamID = t.teamID AND p.yearID = t.yearID AND p.lgID = t.lgID
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
                echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(['error' => '쿼리 실행 오류: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

?>