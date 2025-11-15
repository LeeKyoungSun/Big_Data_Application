<?php
session_start();
include 'db_connect.php';

$userId = $_POST['userId'];
$userPW = $_POST['userPW'];

// 아이디로 사용자 조회
$sql = "SELECT * FROM Users WHERE userId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    
    // 비밀번호 검증
    if (password_verify($userPW, $row['userPW'])) {
        // 로그인 성공 -> 세션에 정보 저장
        $_SESSION['userid'] = $row['userId'];
        $_SESSION['username'] = $row['userName'];
        
        echo "<script>
                alert('welcome " . $_SESSION['username'] . "');
                location.href = 'index.php'; // 메인 페이지로 이동
              </script>";
    } else {
        // 로그인 실패 1. 비밀번호 불일치
        echo "<script>
                alert('Wrong Password.');
                window.history.back();
              </script>";
    }
} else {
    // 로그인 실패 2. 아이디 없음
    echo "<script>
            alert('ID is not exist.');
            window.history.back();
          </script>";
}
$stmt->close();
$conn->close();
?>