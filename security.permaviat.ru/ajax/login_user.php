<?php
	session_start();
	include("../settings/connect_datebase.php");
	
	$login = $_POST['login'];
	$password = $_POST['password'];
	$ip = getClientIP();

	$mysqli->query("CREATE TABLE IF NOT EXISTS `login_attempts_ip` (
		`ip` VARCHAR(45) NOT NULL,
		`last_request_time` INT NOT NULL,
		`request_count` INT DEFAULT 1,
		PRIMARY KEY (`ip`)
	)");

	$mysqli->query("DELETE FROM `login_attempts_ip` WHERE `last_request_time` < " . (time() - 10));

	$stmt_ip = $mysqli->prepare("SELECT `request_count`, `last_request_time` FROM `login_attempts_ip` WHERE `ip` = ?");
	$stmt_ip->bind_param("s", $ip);
	$stmt_ip->execute();
	$res_ip = $stmt_ip->get_result()->fetch_assoc();

	$current_time = time();
	if ($res_ip) {
		if ($current_time == $res_ip['last_request_time']) {
			if ($res_ip['request_count'] >= 3) {
				http_response_code(429);
				die("Слишком много запросов с вашего IP. Подождите.");
			}
			$mysqli->query("UPDATE `login_attempts_ip` SET `request_count` = `request_count` + 1 WHERE `ip` = '$ip'");
		} else {
			$mysqli->query("UPDATE `login_attempts_ip` SET `last_request_time` = $current_time, `request_count` = 1 WHERE `ip` = '$ip'");
		}
	} else {
		$stmt_insert_ip = $mysqli->prepare("INSERT INTO `login_attempts_ip` (ip, last_request_time, request_count) VALUES (?, ?, 1)");
		$stmt_insert_ip->bind_param("si", $ip, $current_time);
		$stmt_insert_ip->execute();
	}

	$res = $mysqli->query("SHOW COLUMNS FROM `users` LIKE 'failed_attempts'");
	if($res && $res->num_rows == 0) {
		$mysqli->query("ALTER TABLE `users` ADD COLUMN `failed_attempts` INT DEFAULT 0");
		$mysqli->query("ALTER TABLE `users` ADD COLUMN `locked_until` DATETIME DEFAULT NULL");
	}
	
	$stmt = $mysqli->prepare("SELECT * FROM `users` WHERE `login` = ?");
	$stmt->bind_param("s", $login);
	$stmt->execute();
	$query_user = $stmt->get_result();
	$user = $query_user->fetch_assoc();
	
	$id = -1;
	if ($user) {
		if ($user['locked_until'] != null && strtotime($user['locked_until']) > time()) {
			echo md5(md5(-1));
			exit();
		}

		if ($user['password'] === $password) {
			$id = $user['id'];
			$_SESSION['user'] = $id;
			$stmt_update = $mysqli->prepare("UPDATE `users` SET `failed_attempts` = 0, `locked_until` = NULL WHERE `id` = ?");
			$stmt_update->bind_param("i", $id);
			$stmt_update->execute();
		} else {
			$attempts = $user['failed_attempts'] + 1;
			if ($attempts >= 5) {
				$locked_until = date("Y-m-d H:i:s", time() + 300);
				$stmt_lock = $mysqli->prepare("UPDATE `users` SET `failed_attempts` = ?, `locked_until` = ? WHERE `id` = ?");
				$stmt_lock->bind_param("isi", $attempts, $locked_until, $user['id']);
				$stmt_lock->execute();
			} else {
				$stmt_retry = $mysqli->prepare("UPDATE `users` SET `failed_attempts` = ? WHERE `id` = ?");
				$stmt_retry->bind_param("ii", $attempts, $user['id']);
				$stmt_retry->execute();
			}
		}
	}
	
	echo md5(md5($id));
?>