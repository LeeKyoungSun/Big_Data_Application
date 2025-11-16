<?php
/*
 * game_roster.php: GROUP BY WITH ROLLUP을 사용하여 경기별 출전 명단과 포지션별 인원수를 집계
 * 프론트엔드: 연도 선택 → 경기 선택 (드롭다운), 원 그래프
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

    // 경기별 포지션 분포 데이터 조회
    if ($_GET['action'] === 'getRoster' && isset($_GET['gameID'])) {
        $gameID = $_GET['gameID'];

        // 방법 1: 포지션별 집계만 (WITH ROLLUP 사용)
        $sql = "
            SELECT
                position,
                playerCount
            FROM (
                SELECT
                    CASE
                        WHEN a.startingPos IS NULL THEN 'Total'
                        ELSE CAST(a.startingPos AS CHAR)
                    END AS position,
                    COUNT(*) AS playerCount,
                    a.startingPos AS sortPos
                FROM
                    AllstarFull a
                WHERE
                    a.gameID = ?
                GROUP BY
                    a.startingPos WITH ROLLUP
            ) AS subquery
            ORDER BY
                CASE WHEN sortPos IS NULL THEN 1 ELSE 0 END,
                playerCount DESC,
                sortPos ASC
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

    // 경기별 출전 선수 명단 조회 (firstName, lastName 포함)
    if ($_GET['action'] === 'getPlayers' && isset($_GET['gameID'])) {
        $gameID = $_GET['gameID'];

        $sql = "
            SELECT
                a.playerID,
                m.nameFirst AS firstName,
                m.nameLast AS lastName,
                CAST(a.startingPos AS CHAR) AS position
            FROM
                AllstarFull a
            JOIN
                Master m ON a.playerID = m.playerID
            WHERE
                a.gameID = ?
            ORDER BY
                a.startingPos ASC,
                m.nameLast ASC,
                m.nameFirst ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(['error' => '쿼리 준비 오류: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("s", $gameID);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $players = [];

            while ($row = $result->fetch_assoc()) {
                $players[] = [
                    'playerID' => $row['playerID'],
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName'],
                    'position' => $row['position']
                ];
            }

            if (empty($players)) {
                echo json_encode(['error' => "'$gameID'에 대한 선수 데이터가 없습니다."]);
            } else {
                echo json_encode(['success' => true, 'players' => $players], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(['error' => '쿼리 실행 오류: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

?>