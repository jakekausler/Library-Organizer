<?php
require 'functions.php';
function checkLoginInformation() {
	session_name('LibraryLogin');
	session_set_cookie_params(2*7*24*60*60); // Two week cookie
	session_start();
	if ($_SESSION['id'] && !isset($_COOKIE['libraryRemember']) && !$_SESSION['rememberMe']) {
		// If logged in, but don't have the remember cookie and didn't check the remember box, remove the session
		$_SESSION = array();
		session_destroy();
	}
	if (isset($_GET['logout'])) {
		$_SESSION = array();
		session_destroy();
		header("Location: index.php");
		exit;
	}
	if ($_POST['submit']=='Login') {
		$err = array();
		if (!$_POST['username'] || !$_POST['password']) {
			$err[] = 'You must supply both a username and password!';
		}
		if (!count($err)) {
			$_POST['rememberMe'] = (int)$_POST['rememberMe'];
			$row = getUser($_POST['username'], $_POST['password']);
			if ($row['usr']) {
				$_SESSION['usr'] = $row['usr'];
				$_SESSION['id'] = $row['id'];
				$_SESSION['rememberMe'] = $_POST['rememberMe'];
				setcookie('libraryRemember', $_POST['rememberMe']);
			} else {
				$err[] = 'Wrong username and/or password!';
			}
		}
		if ($err) {
			$_SESSION['msg']['login-err'] = implode('<br />', $err);
		}
	}
}

function makeLoginArea() {
	$retval = '';
	if (!$_SESSION['id']) { // If not logged in
		$retval = $retval . '<div id="login-form">';
		$retval = $retval . 	'<form action="" method="post">';
		$retval = $retval . 		'<span id="login-label">Member Login</span>';
		$retval = $retval . 		'<label for="username">Username:</label>';
		$retval = $retval . 		'<input type="text" name="username" id="username" value="" />';
		$retval = $retval . 		'<label for="password">Password:</label>';
		$retval = $retval .			'<input type="password" name="password" id="password" value="" />';
		$retval = $retval . 		'<div class="remember-and-submit">';
		$retval = $retval . 			'<input name="rememberMe" id="rememberMe" type="checkbox" checked value="1" /><label for="rememberMe">&nbsp;Remember Me</label>';
		$retval = $retval .				'<input name="submit" value="Login" type="hidden" />';
		$retval = $retval . 			'<button>Login</button>';
		$retval = $retval . 		'</div>';
		$retval = $retval . 		makeInputFields();
		if ($_SESSION['msg']['login-err']) {
			$retval = $retval . 	'<div class="err">'.$_SESSION['msg']['login-err'].'</div>';
			unset($_SESSION['msg']['login-err']);
		}
		$retval = $retval . 	'</form>';
		$retval = $retval . '</div>';
	} else { // If logged in
		$retval = $retval . '<div id="member-area">';
		$retval = $retval . 	'<span id="member-text">You are signed in as '.$_SESSION['usr'].'.</span></br>';
		$retval = $retval . 	'<a href="?logout">Log Out</a>';
		$retval = $retval . '</div>';
	}
	return $retval;
}

function getRestrictedContent()
{
	$retval = 			'';
	$retval = $retval . '<div class="restricted-content">';
	$retval = $retval . 	'This page is only available to admins.';
	$retval = $retval . '</div>';
	return $retval;
}
?>