<?php
	session_start();
	include("../settings/connect_datebase.php");
	
	if (isset($_SESSION['last_reg_time'])) {
		if (time() - $_SESSION['last_reg_time'] < 10) {
			http_response_code(429);
			die("Слишком частые регистрации. Подождите.");
		}
	}
	$_SESSION['last_reg_time'] = time();

	$login = $_POST['login'];
	$password = $_POST['password'];
	
	// ищем пользователя
	$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='".$login."'");
	$id = -1;
	
	if($user_read = $query_user->fetch_row()) {
		echo $id;
	} else {
		$mysqli->query("INSERT INTO `users`(`login`, `password`, `roll`) VALUES ('".$login."', '".$password."', 0)");
		
		$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='".$login."' AND `password`= '".$password."';");
		$user_new = $query_user->fetch_row();
		$id = $user_new[0];
			
		if($id != -1) $_SESSION['user'] = $id; // запоминаем пользователя
		echo $id;
	}
?>