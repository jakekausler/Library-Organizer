<?php
	require_once 'functions.php';
	require_once 'login.php';
	checkLoginInformation();
	if (isset($GLOBALS['HoldingVar']['action'])) {
		if ($GLOBALS['HoldingVar']['action']=='remove') {
			removeBook();
		}
	}
	if ($GLOBALS['HoldingVar']['filter'] != "") {
		$GLOBALS['HoldingVar']['page']=1;
	}
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/searchstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<link rel="stylesheet" type="text/css" href="css/liststyles.css">
	<link rel="stylesheet" type="text/css" href="css/bookshelfstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
</head>
<?php
	setDefaultValues();
?>
<body>
	<div id="grid-header">
		<?php echo makeLogo(); ?>
		<div id="login-area">
			<?php echo makeLoginArea(); ?>
		</div>
		<div id="bookshelf-control-buttons">
			<form action=<?php echo '"'.$GLOBALS['HoldingVar']['previouspage'].'"'; ?> method="get">
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
			</form>
		</div>
	</div>
	<div id="bookcase-area" style="-webkit-overflow-scrolling: touch;">
	</div>
</body>

<script>
var bookcase = '<div class="bookcase"></div>';
var bookshelf = '<div class="bookshelf-container"><div class="bookshelf"></div></div>';
var fromLeft = 0;
var fromTop = 0;
var shelfThickness = 31;
var margin = 6;
var textMargin = 8;
var zoom = 0.25;
window.onload = function() {
	<?php echo stringShelves(); ?>
	makePage(shelves);
}
function makePage(shelves) {
	var i=0;
	$.each(shelves, function(index, bookcase) {
		makeShelf(bookcase, index);
	});
}
function makeShelf(caseStruct, caseNum) {
	caseStruct.width *= zoom;
	caseStruct.shelfHeight *= zoom;
	caseStruct.spacerHeight *= zoom;
	caseStruct.paddingRight *= zoom;
	caseStruct.paddingLeft *= zoom;
	caseStruct.bookMargin *= zoom;
	$bcase = $(bookcase);
	$bcase.width(caseStruct.width-caseStruct.paddingRight);
	$bcase.css('padding-right', caseStruct.paddingRight);
	$bcase.css('outline', caseStruct.spacerHeight+"px solid black");
	$bcase.height(caseStruct.shelfHeight*caseStruct.numShelves+caseStruct.spacerHeight*(caseStruct.numShelves-1));
	$bcase.html("");
	// var width = caseStruct.width-caseStruct.paddingRight;
	// var i = offset;
	var curr_shelf = 0;
	while (curr_shelf < caseStruct.numShelves) {
		// var curr_width = 0;
		// var curr_book = 0;
		// while (books[i] && books[i].width*zoom+curr_width <= caseStruct.width-caseStruct.paddingRight) {
		// 	books[i].width *= zoom;
		// 	books[i].height *= zoom;
		// 	var actualMargin = Math.min(caseStruct.bookMargin*2, books[i].width-caseStruct.bookMargin*2);
		// 	books[i].width -= actualMargin;
		// 	$b = makeBook(books[i]);
		// 	$b.css('bottom', 0);
		// 	$bc = $('<div class="book-container" />');
		// 	$bc.height(caseStruct.shelfHeight+caseStruct.spacerHeight);
		// 	$bc.width(books[i].width+actualMargin);
		// 	$bc.append($b);
		// 	$bcase.append($bc);
		// 	curr_width += books[i].width+actualMargin;
		// 	var bsd = {
		// 		title: books[i].text,
		// 		subtitle: books[i].subtitle,
		// 		booknum: curr_book,
		// 		shelfnum: curr_shelf,
		// 		casenum: caseNum
		// 	};
		// 	bookshelfdict.push(bsd);
		// 	i++;
		// 	curr_book++;
		// }
		curr_shelf++;
		if (curr_shelf < caseStruct.numShelves) {
			$shelf_spacer = $('<div class="shelf-spacer" />');
			$shelf_spacer.height(caseStruct.spacerHeight);
			$shelf_spacer.width(caseStruct.width);
			$shelf_spacer.css('top', curr_shelf*caseStruct.shelfHeight+caseStruct.spacerHeight*(curr_shelf));
			$bcase.append($shelf_spacer);
		}
	}
	$('#bookcase-area').append($bcase);
}
</script>