<?php
/*
 * game_roster.php: GROUP BY WITH ROLLUP을 사용하여 경기별 출전 명단과 포지션별 인원수를 집계

 */

// 1. DB 연결
require_once 'db_connect.php';

// 2. AJAX 요청 처리
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

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

    // 경기별 포지션 분포 데이터 조회 (startingPos는 INT 타입)
    if ($_GET['action'] === 'getRoster' && isset($_GET['gameID'])) {
        $gameID = $_GET['gameID'];

        $sql = "
            SELECT
                CASE
                    WHEN a.startingPos IS NULL THEN 'Total'
                    ELSE CAST(a.startingPos AS CHAR)
                END AS position,
                '' AS firstName,
                '' AS lastName,
                COUNT(*) AS playerCount
            FROM
                AllstarFull a
            WHERE
                a.gameID = ?
            GROUP BY
                a.startingPos WITH ROLLUP
            ORDER BY
                CASE WHEN a.startingPos IS NULL THEN 1 ELSE 0 END,
                playerCount DESC,
                a.startingPos ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['error' => '쿼리 준비 오류: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("s", $gameID);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $roster = [];

            while ($row = $result->fetch_assoc()) {
                $roster[] = [
                    'position' => $row['position'],
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName'],
                    'playerCount' => (int)$row['playerCount']
                ];
            }

            if (empty($roster)) {
                echo json_encode(['error' => "'$gameID'에 대한 데이터가 없습니다."]);
            } else {
                echo json_encode(['success' => true, 'roster' => $roster], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(['error' => '쿼리 실행 오류: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

?>