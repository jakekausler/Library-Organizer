<?php
class Database {
	private static $servername = 'localhost';
	private static $username = 'jakekaus_root';
	private static $password = 'Jake021f2f1!';
	private static $dbname = 'jakekaus_library';
	private static $db;
	private $connection;
	private function __construct() {
		$this->connection = new mysqli(self::$servername,self::$username,self::$password,self::$dbname);
	}
	function __destruct() {
		$this->connection->close();
	}
	public static function getConnection() {
		if (self::$db==null) {
			self::$db = new Database();
		}
		return self::$db->connection;
	}
}
?>