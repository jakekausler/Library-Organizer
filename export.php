<?php
	require 'functions.php';
	require 'login.php';
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
			<form action="index.php" method="post">
				<?php echo makeInputFields(); ?>
				<button id="export-return-button" class="export-control-button">
					Return
				</button>
			</form>
		</div>
	</div>
	<div id="export">

	</div>
</body>