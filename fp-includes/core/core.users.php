<?php

class user_lister extends fs_filelister {

	var $_varname = 'cache';

	var $_cachefile = null;

	var $_directory = USERS_DIR;

	function bdb_entrylister() {
		$this->_cachefile = CACHE_DIR . 'userlist.php';
		parent::__construct();
	}

	function _checkFile($directory, $file) {
		if (fnmatch('*.php', $file)) {
			array_push($this->_list, basename($file, EXT));
			return 0;
		}
	}

}

function user_list() {
	$obj = new user_lister();
	if ($users = $obj->getList()) {
		return $entry_arr;
	} else
		return false;
}

function user_pwd($userid, $pwd) {
	return wp_hash($userid . $pwd);
}

function user_login($userid, $pwd, $params = null) {
	global $loggedin;

	$loggedin = false;

	$user = user_get($userid);

	if (isset($user) && user_pwd($userid, $pwd) == $user ['password']) {

		$loggedin = true;

		// session_regenerate_id();

		$expire = time() + 31536000;

		setcookie(USER_COOKIE, $userid, $expire, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(PASS_COOKIE, $user ['password'], $expire, COOKIEPATH, COOKIE_DOMAIN);
	}

	return $loggedin;
}

function user_logout() {
	global $loggedin;

	if (user_loggedin()) {

		setcookie(USER_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(PASS_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	}

	$loggedin = false;

	sess_close();
}

function user_loggedin() {
	global $loggedin, $fp_user;

	if ($loggedin)
		return $fp_user;

	if (empty($_COOKIE [USER_COOKIE]) || empty($_COOKIE [PASS_COOKIE])) {
		$fp_user = null;
		return $loggedin = false;
	}

	$fp_user = user_get($_COOKIE [USER_COOKIE]);

	if (!$fp_user) {
		return false;
	}

	if ($_COOKIE [PASS_COOKIE] == $fp_user ['password']) {
		$loggedin = true;
		return $fp_user;
	}

	$fp_user = null;
	$loggedin = false;
	return false;
}

function user_get($userid = null) {
	if ($userid == null && ($user = user_loggedin())) {
		return $user;
	}

	// We need to include the user file.
	// At first: Get files in fp_content/users (array_slice removes first elements "." and "..")
	$userfiles = array_slice(scandir(USERS_DIR), 2);
	// If PHP file for given user exists ...
	if (in_array($userid . '.php', $userfiles)) {
		// ... include it
		include (USERS_DIR . $userid . ".php");
		return $user;
	}
}

function user_add($user) {
	$user ['password'] = user_pwd($user ['userid'], $user ['password']);

	return system_save(USERS_DIR . $user ['userid'] . ".php", compact('user'));
}

?>
