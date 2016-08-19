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
			<p>
			Please choose a file. Remember that columns should be labeld with exact names (as in the MySQL database). If importing authors, ensure that the column is named 'Authors' and that the string format for each author is as follows, with each separated by a semicolon:
				<blockquote>
					{LastName}, {FirstName} {MiddleNames}: {Role}
				</blockquote>
			</p>
			<input id="import-upload" type="file" />
		</div>
	<?php
	}
	?>
</body>

<script>
	$("#import-upload").change(function() {
		var file = this.files[0];
		var fr = new FileReader();
		fr.readAsText(file);
		fr.onload = function() {
			alert(JSON.stringify(fr.result.replace('\r', '')));
			$.ajax({
				type: 'POST',
				url: 'ajaxrequests.php',
				data: {
					contents: fr.result.replace('\r', ''),
					action: 'importBooks'
				},
				success: function(d) {
					if (d != "") {
						alert(d);
					}
				}
			});
		}
	});
</script>