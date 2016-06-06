<?php
class Database {
	private static $servername = '';
    private static $username = '';
    private static $password = '';
    private static $dbname = '';
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