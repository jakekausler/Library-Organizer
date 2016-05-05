<?php
require 'functions.php';
if (isset($_POST['action']) && !empty($_POST['action'])) {
	switch ($_POST['action']) {
		case 'saveShelves':
			writeToFile('shelves.txt', $_POST['contents']);
			break;
		case 'readShelves':
			echo readFromFile('shelves.txt');
			break;
		default:
			break;
	}
}
?>