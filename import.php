<?php
	require 'functions.php';
	require 'login.php';
	checkLoginInformation();
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/importstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
</head>
<?php
	setDefaultValues();
?>
<body>
	<div id="import-header">
		<?php echo makeLogo(); ?>
		<div id="login-area">
			<?php echo makeLoginArea(); ?>
		</div>
		<div id="import-control-buttons">
			<form action="index.php" method="post">
				<?php echo makeInputFields(); ?>
				<button id="import-return-button" class="import-control-button">
					Return
				</button>
			</form>
		</div>
	</div>
	<?php
	if (!$_SESSION['id']) {
		echo getRestrictedContent();
	} else {
	?>
		<div id="import">
			You are a member and can see this!
		</div>
	<?php
	}
	?>
</body>