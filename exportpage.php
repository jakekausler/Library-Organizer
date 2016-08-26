<?php
	require_once 'functions.php';
	if (isset($GLOBALS['HoldingVar']['export-action'])) {
		if ($GLOBALS['HoldingVar']['export-action']=='books') {
			exportBooks();
		} else if ($GLOBALS['HoldingVar']['export-action']=='authors') {
			exportAuthors();
		}
	}
	exit;
?>