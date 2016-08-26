<?php
	require_once 'functions.php';
	require_once 'login.php';
	checkLoginInformation();
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/statsstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
</head>
<?php
	setDefaultValues();
?>
<body>
	<div id="statistics-header">
		<?php echo makeLogo(); ?>
		<div id="login-area">
			<?php echo makeLoginArea(); ?>
		</div>
		<div id="statistics-control-buttons">
			<form action="index.php" method="get">
				<?php echo makeInputFields(); ?>
				<button id="statistics-return-button" class="statistics-control-button">
					Return
				</button>
			</form>
		</div>
	</div>
	<?php echo makeStatisticDiv(); ?>
</body>