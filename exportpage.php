<?php
	require 'functions.php';
	if (isset($_POST['export-action'])) {
		if ($_POST['export-action']=='books') {
			exportBooks();
		} else if ($_POST['export-action']=='authors') {
			exportAuthors();
		}
	}
	exit;
?>