<?php
	require 'functions.php';
	require 'login.php';
	checkLoginInformation();
	if (isset($_POST['action'])) {
		if ($_POST['action']=='save') {
			saveBook();
		}
	}
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/editorstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
</head>
<?php
	setDefaultValues();
	if (!isset($_POST['bookid'])) {
		$_POST['bookid'] = -1;
	}
	if (intval($_POST['bookid'])<=0) {
		$_POST['bookid'] = -1;
	}
	$_POST['currentid']=$_POST['bookid'];
?>
<body>
	<div id="editor-header">
		<?php echo makeLogo(); ?>
		<div id="login-area">
			<?php echo makeLoginArea(); ?>
		</div>
		<div id="editor-control-buttons">
			<form action=<?php echo '"'.$_POST['previouspage'].'"'; ?> method="post">
				<?php echo makeInputFields(); ?>
				<button onclick="cancel()" id="editor-cancel-button" class="editor-control-button">
					Return
				</button>
			</form
			><form action="index.php" method="post">
				<?php echo makeInputFields(); ?>
				<button type="button" onclick="save()" id="editor-save-button" class="editor-control-button" <?php echo !$_SESSION['id']?'disabled':'' ?>>
					Save
				</button>
			</form
			><form action="index.php" method="post">
				<?php echo makeInputFields(); ?>
				<button type="button" onclick="removeBook()" id="editor-remove-button" class="editor-control-button" <?php echo (!$_SESSION['id'] || $_POST['bookid']==-1)?'disabled':'' ?>>
					Remove
				</button>
			</form>
		</div>
	</div>
	<?php
	if (!$_SESSION['id']) {
		echo getRestrictedContent();
	} else {
	?>
		<div id="editor">
			<form id="editor-form" method="post" action="index.php">
				<?php
					if ($_POST['bookid']!=-1) {
						$book = getBook($_POST['bookid']);
					} else {
						$book = NULL;
					}
				?>
				<div id="editor-book-and-image-info" class="editor-area">
					<div class="editor-header">Book Information</div>
					<div id="editor-book-info">
						<div id="editor-title">
							<div class="entry"><label>Title:</label><input name="title" id="title-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['Title']).'"'?> /></div>
						</div>
						<div id="editor-subtitle">
							<div class="entry"><label>Subtitle:</label><input name="subtitle" id="subtitle-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['Subtitle']).'"'?> /></div>
						</div>
						<div id="editor-series">
							<div class="entry">
								<div class="combo-select">
									<label>Series:</label>
									<input onkeydown="trackChanges('series')" oninput="trackChanges('series')" onpaste="trackChanges('series')" name="series" id="series-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['Series']).'"'?> />
									<div class="select"><select id="series-select" onchange="chooseOption('series')"><?php echo stringSelection(getSeries()); ?></select></div>
								</div>
							</div>
						</div>
						<div id="editor-volume">
							<div class="entry"><label>Volume:</label><input name="volume" id="volume-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Volume']).'"'?> /></div>
						</div>
					</div>
					<div id="editor-image-info">
						<div id="editor-image>">
							<img id="editor-image-actual" src=<?php echo '"'.($book==NULL?'':($book['ImageURL']==''?'':$book['ImageURL'])).'"'?> alt=<?php echo '"'.($book==NULL?'':$book['Title']).'"'?>>
							<input name="imageurl" id="image-entry" type="hidden" value="" />
						</div>
						<div id="editor-image-buttons">
							<button type="button" onclick="changeImage()">
								Change
							</button>
						</div>
					</div>
				</div>
				<div id="editor-author-info" class="editor-area">
					<div class="editor-header">Author Information</div>
					<?php
						echo makeAuthorBox($_POST['bookid']);
					?>
					<div id="editor-author-buttons">
						<button type="button" onclick="addAuthor()">
							Add
						</button
						><button type="button" onclick="removeAuthor()">
							Remove
						</button>
					</div>
				</div>
				<div id="editor-edition-info" class="editor-area">
					<div class="editor-header">Edition Information</div>
					<div id="editor-copyright">
						<div class="entry"><label>Copyright:</label><input name="copyright" id="copyright-entry" type="text" value=<?php echo '"'.($book==NULL?'':stringDate($book['Copyright'])).'"'?> /></div>
					</div>
					<div id="editor-format">
						<div class="entry">
							<div class="combo-select">
								<label>Format:</label>
								<input onkeydown="trackChanges('format')" oninput="trackChanges('format')" onpaste="trackChanges('format')" name="format" id="format-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['Format']).'"'?> />
								<div class="select"><select id="format-select" onchange="chooseOption('format')"><?php echo stringSelection(getFormats()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-publisher">
						<div class="entry">
							<div class="combo-select">
								<label>Publisher:</label>
								<input onkeydown="trackChanges('publisher')" oninput="trackChanges('publisher')" onpaste="trackChanges('publisher')" name="publisher" id="publisher-entry" type="text" value=<?php echo '"'.($book==NULL?'':getPublisher($book['PublisherID'])).'"'?> />
								<div class="select"><select id="publisher-select" onchange="chooseOption('publisher')"><?php echo stringSelection(getPublishers()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-city">
						<div class="entry">
							<div class="combo-select">
								<label>City:</label>
								<input onkeydown="trackChanges('city')" oninput="trackChanges('city')" onpaste="trackChanges('city')" name="city" id="city-entry" type="text" value=<?php echo '"'.($book==NULL?'':getCity($book['PublisherID'])).'"'?> />
								<div class="select"><select id="city-select" onchange="chooseOption('city')"><?php echo stringSelection(getCities()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-state">
						<div class="entry">
							<div class="combo-select">
								<label>State:</label>
								<input onkeydown="trackChanges('state')" oninput="trackChanges('state')" onpaste="trackChanges('state')" name="state" id="state-entry" type="text" value=<?php echo '"'.($book==NULL?'':getState($book['PublisherID'])).'"'?> />
								<div class="select"><select id="state-select" onchange="chooseOption('state')"><?php echo stringSelection(getStates()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-country">
						<div class="entry">
							<div class="combo-select">
								<label>Country:</label>
								<input onkeydown="trackChanges('country')" oninput="trackChanges('country')" onpaste="trackChanges('country')" name="country" id="country-entry" type="text" value=<?php echo '"'.($book==NULL?'':getCountry($book['PublisherID'])).'"'?> />
								<div class="select"><select id="country-select" onchange="chooseOption('country')"><?php echo stringSelection(getCountries()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-edition">
						<div class="entry"><label>Edition:</label><input name="edition" id="edition-entry" type="text" value=<?php echo '"'.($book==NULL?'1':$book['Edition']).'"'?> /></div>
					</div>
				</div>
				<div id="editor-language-info" class="editor-area">
					<div class="editor-header">Language Information</div>
					<div id="editor-primary-language">
						<div class="entry">
							<div class="combo-select">
								<label>Primary Language:</label>
								<input onkeydown="trackChanges('primary-language')" oninput="trackChanges('primary-language')" onpaste="trackChanges('primary-language')" name="primary-language" id="primary-language-entry" type="text" value=<?php echo '"'.($book==NULL?'English':$book['PrimaryLanguage']).'"'?> />
								<div class="select"><select id="primary-language-select" onchange="chooseOption('primary-language')"><?php echo stringSelection(getLanguages()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-secondary-language">
						<div class="entry">
							<div class="combo-select">
								<label>Secondary Language:</label>
								<input onkeydown="trackChanges('secondary-language')" oninput="trackChanges('secondary-language')" onpaste="trackChanges('secondary-language')" name="secondary-language" id="secondary-language-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['SecondaryLanguage']).'"'?> />
								<div class="select"><select id="secondary-language-select" onchange="chooseOption('secondary-language')"><?php echo stringSelection(getLanguages()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-original-language">
						<div class="entry">
							<div class="combo-select">
								<label>Original Language:</label>
								<input onkeydown="trackChanges('original-language')" oninput="trackChanges('original-language')" onpaste="trackChanges('original-language')" name="original-language" id="original-language-entry" type="text" value=<?php echo '"'.($book==NULL?'English':$book['OriginalLanguage']).'"'?> />
								<div class="select"><select id="original-language-select" onchange="chooseOption('original-language')"><?php echo stringSelection(getLanguages()); ?></select></div>
							</div>
						</div>
					</div>
				</div>
				<div id="editor-dimension-info" class="editor-area">
					<div class="editor-header">Dimension Information</div>
					<div id="editor-pages">
						<div class="entry"><label>Pages:</label><input name="pages" id="pages-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Pages']).'"'?> /></div>
					</div>
					<div id="editor-width">
						<div class="entry"><label>Width:</label><input name="width" id="width-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Width']).'"'?> /></div>
					</div>
					<div id="editor-height">
						<div class="entry"><label>Height:</label><input name="height" id="height-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Height']).'"'?> /></div>
					</div>
					<div id="editor-depth">
						<div class="entry"><label>Depth:</label><input name="depth" id="depth-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Depth']).'"'?> /></div>
					</div>
					<div id="editor-weight">
						<div class="entry"><label>Weight:</label><input name="weight" id="weight-entry" type="text" value=<?php echo '"'.($book==NULL?'0':$book['Weight']).'"'?> /></div>
					</div>
				</div>
				<div id="editor-misc-info" class="editor-area">
					<div class="editor-header">Misc Information</div>
					<div id="editor-dewey">
						<div class="entry">
							<div class="combo-select">
								<label>Dewey:</label>
								<input onkeydown="trackChanges('dewey')" oninput="trackChanges('dewey')" onpaste="trackChanges('dewey')" name="dewey" id="dewey-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['Dewey']).'"'?> />
								<div class="select"><select id="dewey-select" onchange="chooseOption('dewey')"><?php echo stringSelection(getDeweys()); ?></select></div>
							</div>
						</div>
					</div>
					<div id="editor-isbn">
						<div class="entry"><label>ISBN:</label><input name="isbn" id="isbn-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['ISBN']).'"'?> /></div>
					</div>
					<div id="editor-loanee">
						<div class="entry">
							<div><label>Loanee First:</label><input name="loaneefirst" id="loanee-first-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['LoaneeFirst']).'"'?> /></div>
							<div><label>Loanee Last:</label><input name="loaneelast" id="loanee-last-entry" type="text" value=<?php echo '"'.($book==NULL?'':$book['LoaneeLast']).'"'?> /></div>
						</div>
					</div>
					<div id="editor-checkboxes">
						<div id="editor-read">
							<div class="checkbox"><input name="isread" id="read-check" type="checkbox" value="1" <?php echo ($book==NULL?'':($book['IsRead']==0?'':'checked'))?> />Read</div>
						</div>
						<div id="editor-reference">
							<div class="checkbox"><input name="isreference" id="reference-check" type="checkbox" value="1" <?php echo ($book==NULL?'':($book['IsReference']==0?'':'checked'))?> />Reference</div>
						</div>
						<div id="editor-owned">
							<div class="checkbox"><input name="isowned" id="owned-check" type="checkbox" value="1" <?php echo ($book==NULL?'':($book['IsOwned']==0?'':'checked'))?> />Owned</div>
						</div>
						<div id="editor-reading">
							<div class="checkbox"><input name="isreading" id="reading-check" type="checkbox" value="1" <?php echo ($book==NULL?'':($book['IsReading']==0?'':'checked'))?> />Reading</div>
						</div>
						<div id="editor-shipping">
							<div class="checkbox"><input name="isshipping" id="shipping-check" type="checkbox" value="1" <?php echo ($book==NULL?'':($book['IsShipping']==0?'':'checked'))?> />Shipping</div>
						</div>
					</div>
				</div>
				<?php echo makeInputFields(); ?>
				<input type="hidden" name="bookid" value=<?php echo '"'.$_POST['bookid'].'"'; ?> />
			</form>
		</div>
	<?php
	}
	?>
</body>
<script>
	window.onload = function() {
		selectCorrect();
		if (<?php echo ($book==NULL?'false':'true'); ?>) {
			makeAuthorSelect();
		}
		<?php
		if (isset($GLOBALS['alert-message'])) {
			echo "alert('".$GLOBALS['alert-message']."');";
		}
		if (isset($GLOBALS['msg'])) {
			echo "alert('".$GLOBALS['msg']."');";
		}
		?>
	}
	function makeAuthorSelect() {
		$('.author-box').children().each(function(i, item){$(item).click(function(){selectAuthor(i)})});
		if ($('.author-box').children().length > 0) {
			selectAuthor(0);
		}
	}
	function selectAuthor(index) {
		$('.author-box').children().each(function(i, item){$(item).removeClass('author-selected')});
		$($('.author-box').children()[index]).addClass('author-selected');
	}
	function selectCorrect() {
		var seriesSelect = document.getElementById("series-select");
		var seriesInput = document.getElementById("series-entry");
		for (var i=0; i<seriesSelect.options.length; i++) {
			if (seriesSelect.options[i].text==seriesInput.value) {
				seriesSelect.value = seriesSelect.options[i].text;
				break;
			}
		}
		var formatSelect = document.getElementById("format-select");
		var formatInput = document.getElementById("format-entry");
		for (var i=0; i<formatSelect.options.length; i++) {
			if (formatSelect.options[i].text==formatInput.value) {
				formatSelect.value = formatSelect.options[i].text;
				break;
			}
		}
		var publisherSelect = document.getElementById("publisher-select");
		var publisherInput = document.getElementById("publisher-entry");
		for (var i=0; i<publisherSelect.options.length; i++) {
			if (publisherSelect.options[i].text==publisherInput.value) {
				publisherSelect.value = publisherSelect.options[i].text;
				break;
			}
		}
		var citySelect = document.getElementById("city-select");
		var cityInput = document.getElementById("city-entry");
		for (var i=0; i<citySelect.options.length; i++) {
			if (citySelect.options[i].text==cityInput.value) {
				citySelect.value = citySelect.options[i].text;
				break;
			}
		}
		var stateSelect = document.getElementById("state-select");
		var stateInput = document.getElementById("state-entry");
		for (var i=0; i<stateSelect.options.length; i++) {
			if (stateSelect.options[i].text==stateInput.value) {
				stateSelect.value = stateSelect.options[i].text;
				break;
			}
		}
		var countrySelect = document.getElementById("country-select");
		var countryInput = document.getElementById("country-entry");
		for (var i=0; i<countrySelect.options.length; i++) {
			if (countrySelect.options[i].text==countryInput.value) {
				countrySelect.value = countrySelect.options[i].text;
				break;
			}
		}
		var primaryLanguageSelect = document.getElementById("primary-language-select");
		var primaryLanguageInput = document.getElementById("primary-language-entry");
		for (var i=0; i<primaryLanguageSelect.options.length; i++) {
			if (primaryLanguageSelect.options[i].text==primaryLanguageInput.value) {
				primaryLanguageSelect.value = primaryLanguageSelect.options[i].text;
				break;
			}
		}
		var secondaryLanguageSelect = document.getElementById("secondary-language-select");
		var secondaryLanguageInput = document.getElementById("secondary-language-entry");
		for (var i=0; i<secondaryLanguageSelect.options.length; i++) {
			if (secondaryLanguageSelect.options[i].text==secondaryLanguageInput.value) {
				secondaryLanguageSelect.value = secondaryLanguageSelect.options[i].text;
				break;
			}
		}
		var originalLanguageSelect = document.getElementById("original-language-select");
		var originalLanguageInput = document.getElementById("original-language-entry");
		for (var i=0; i<originalLanguageSelect.options.length; i++) {
			if (originalLanguageSelect.options[i].text==originalLanguageInput.value) {
				originalLanguageSelect.value = originalLanguageSelect.options[i].text;
				break;
			}
		}
		var deweySelect = document.getElementById("dewey-select");
		var deweyInput = document.getElementById("dewey-entry");
		for (var i=0; i<deweySelect.options.length; i++) {
			if (deweySelect.options[i].text==deweyInput.value) {
				deweySelect.value = deweySelect.options[i].text;
				break;
			}
		}
	}
	function cancel() {
		return true;
	}
	function save() {
		if (!reportErrors(validateFields())) {
			var input = document.createElement("input");
			input.setAttribute("name", "action");
			input.setAttribute("value", "save");
			document.getElementById("editor-form").appendChild(input);
			input = document.createElement("input");
			input.setAttribute("name", "previouspage");
			input.setAttribute("value", <?php echo '"'.$_POST['previouspage'].'"';?>);
			document.getElementById("editor-form").appendChild(input);
			$('.author-box').children().each(function(i, item){
				input = document.createElement("input");
				input.setAttribute('name', 'authors['+i+'][firstname]');
				input.setAttribute('value', $(item).find('.firstname').text());
				document.getElementById("editor-form").appendChild(input);
				input = document.createElement("input");
				input.setAttribute('name', 'authors['+i+'][middlenames]');
				input.setAttribute('value', $(item).find('.middlenames').text());
				document.getElementById("editor-form").appendChild(input);
				input = document.createElement("input");
				input.setAttribute('name', 'authors['+i+'][lastname]');
				input.setAttribute('value', $(item).find('.lastname').text());
				document.getElementById("editor-form").appendChild(input);
				input = document.createElement("input");
				input.setAttribute('name', 'authors['+i+'][role]');
				input.setAttribute('value', $(item).find('.role').text());
				document.getElementById("editor-form").appendChild(input);
			});
			console.log(document.getElementById("editor-form"));
			document.getElementById("editor-form").setAttribute("action", "editor.php");
			document.getElementById("editor-form").submit();
		}
		return false;
	}
	function removeBook() {
		console.log('here');
		if (confirm("Are you sure you would like to remove this book? This cannot be undone.")) {
			var input = document.createElement("input");
			input.setAttribute("name", "action");
			input.setAttribute("value", "remove");
			document.getElementById("editor-form").appendChild(input);
			document.getElementById("editor-form").submit();
			return true;
		}
		return false;
	}
	function changeImage() {
		var imageurl = promptURL()
		if (imageurl != '' && imageurl.endsWith('.jpg')) {
			document.getElementById('image-entry').value = imageurl;
			document.getElementById('editor-image-actual').src = imageurl;
		}
	}
	function promptURL() {
		return prompt("Enter the image url:");
	}
	function addAuthor() {
		var fn = prompt("Enter the person's first name");
		var mn = prompt("Enter the person's middle names, separtated by semicolons");
		var ln = prompt("Enter the person's last name");
		var role = prompt("Enter the person's role");
		var author = '<div class=authorname"><li><span class="firstname">'+fn+'</span> <span class="middlenames">';
		mn.split(';').forEach(function(name){
			author += name + ' ';
		});
		author += '</span><span class="lastname">'+ln+'</span>: <span class="role">'+role+'</span></div>';
		$('.author-box').append(author);
		$('.author-box :last-child').click(function(){selectAuthor($('.author-box').children().length-1)});
	}
	function removeAuthor() {
		$('.author-selected').remove();
		if ($('.author-box').children().length > 0) {
			selectAuthor(0);
		}
	}
	function chooseOption(fieldName) {
		var selection = document.getElementById(fieldName+'-select').value;
		document.getElementById(fieldName+'-entry').value = selection;
	}
	function trackChanges(fieldName) {
		var text = document.getElementById(fieldName+'-entry').value;
		select = document.getElementById(fieldName+'-select');
		for (var i=0; i<select.options.length; i++) {
			if (select.options[i].text==text) {
				select.value = select.options[i].text;
				return true;
			}
		}
		select.value = select.options[0].text;
		return false;
	}
	function validateFields() {
		var err = [];
		if (document.getElementById('title-entry').value=='') {
			err.push('Title must not be empty');
		}
		if (!$.isNumeric(document.getElementById('edition-entry').value) || Math.floor(document.getElementById('edition-entry').value)!=document.getElementById('edition-entry').value || parseInt(document.getElementById('edition-entry').value)<0) {
			err.push('Edition must be an integer that is at least zero!')
		}
		if (!$.isNumeric(document.getElementById('pages-entry').value) || Math.floor(document.getElementById('pages-entry').value)!=document.getElementById('pages-entry').value || parseInt(document.getElementById('pages-entry').value)<0) {
			err.push('Pages must be an integer that is at least zero!')
		}
		if (!$.isNumeric(document.getElementById('width-entry').value) || parseInt(document.getElementById('width-entry').value)<0) {
			err.push('Width must be a number that is at least zero!')
		}
		if (!$.isNumeric(document.getElementById('height-entry').value) || parseInt(document.getElementById('height-entry').value)<0) {
			err.push('Height must be a number that is at least zero!')
		}
		if (!$.isNumeric(document.getElementById('depth-entry').value) || parseInt(document.getElementById('depth-entry').value)<0) {
			err.push('Depth must be a number that is at least zero!')
		}
		if (!$.isNumeric(document.getElementById('weight-entry').value) || parseInt(document.getElementById('weight-entry').value)<0) {
			err.push('Weight must be a number that is at least zero!')
		}
		return err;
	}
	function reportErrors(err) {
		if (err.length > 0) {
			alert(err.join('\n'));
			return true;
		}
		return false;
	}
</script>