<?php
	require_once 'functions.php';
	require_once 'login.php';
	checkLoginInformation();
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/exportstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
</head>
<?php
	setDefaultValues();
?>
<body>
	<div id="export-header">
		<?php echo makeLogo(); ?>
		<div id="login-area">
			<?php echo makeLoginArea(); ?>
		</div>
		<div id="export-control-buttons">
			<form action="index.php" method="get">
				<?php echo makeInputFields(); ?>
				<button id="export-return-button" class="export-control-button">
					Return
				</button>
			</form
			><form action="exportpage.php" method="get" target="_blank">
				<?php echo makeInputFields(); ?>
				<input type="hidden" name="export-action" value="books" />
				<button id="export-books-button" class="export-control-button">
					Export Books
				</button>
			</form
			><form action="exportpage.php" method="get" target="_blank">
				<?php echo makeInputFields(); ?>
				<input type="hidden" name="export-action" value="authors" />
				<button id="export-authors-button" class="export-control-button">
					Export Authors
				</button>
			</form>
		</div>
	</div>
	<div id="export">

	</div>
</body>