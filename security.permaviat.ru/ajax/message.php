<?
    session_start();
	include("../settings/connect_datebase.php");

    if (isset($_SESSION['last_comment_time'])) {
		if (time() - $_SESSION['last_comment_time'] < 15) {
			http_response_code(429);
			die("Слишком частые комментарии. Подождите.");
		}
	}
	$_SESSION['last_comment_time'] = time();

    $IdUser = $_SESSION['user'];
    $Message = $_POST["Message"];
    $IdPost = $_POST["IdPost"];

    $mysqli->query("INSERT INTO `comments`(`IdUser`, `IdPost`, `Messages`) VALUES ({$IdUser}, {$IdPost}, '{$Message}');");
?>