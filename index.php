<?php
	require_once 'functions.php';
	require_once 'login.php';
	checkLoginInformation();
	if (isset($GLOBALS['HoldingVar']['action'])) {
		if ($GLOBALS['HoldingVar']['action']=='remove') {
			removeBook();
		}
	}
	// if ($GLOBALS['HoldingVar']['filter'] != "") {
	// 	$GLOBALS['HoldingVar']['page']=1;
	// }
?>
<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/searchstyles.css">
	<link rel="stylesheet" type="text/css" href="css/gridstyles.css">
	<link rel="stylesheet" type="text/css" href="css/commonstyles.css">
	<link rel="stylesheet" type="text/css" href="css/gridviewstyles.css">
	<link rel="stylesheet" type="text/css" href="css/bookshelfstyles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	<script src="bookshelffunctions.js"></script>
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
		<div id="grid-control-buttons">
			<form action="editor.php" method="get">
				<input type="hidden" name="action" value="index.php" />
				<?php echo makeInputFields(); ?>
				<button id="grid-add-button" class="grid-control-button">
					Add Book
				</button>
			</form
			><form action="import.php" method="get">
				<?php echo makeInputFields(); ?>
				<button id="grid-import-button" class="grid-control-button">
					Import
				</button>
			</form
			><form action="export.php" method="get">
				<?php echo makeInputFields(); ?>
				<button id="grid-export-button" class="grid-control-button">
					Export
				</button>
			</form
			><form action="statistics.php" method="get">
				<?php echo makeInputFields(); ?>
				<button id="grid-statistics-button" class="grid-control-button">
					Statistics
				</button>
			</form>
		</div>
		<form id="sort-and-filter-form" action="index.php" method="get">
			<div id="sort-and-filter">
				<div id="sort">
					<label id="sort-label">
						Sort By
					</label>
					<div class="button-group">
						<input type="radio" name="sort" value="title" <?php echo ($GLOBALS['HoldingVar']['sort']=='title')?'checked':''; ?>>Title
						<input type="radio" name="sort" value="series" <?php echo ($GLOBALS['HoldingVar']['sort']=='series')?'checked':''; ?>>Series
						<input type="radio" name="sort" value="dewey" <?php echo ($GLOBALS['HoldingVar']['sort']=='dewey')?'checked':''; ?>>Dewey
					</div>
					<div id="number-to-get" class="sort-group">
						<label id="number-to-get-label">
							Number to Get
						</label
							><input id="number-to-get" class="pagination-input" type="text" name="number-to-get" value=<?php echo '"'.$GLOBALS['HoldingVar']['number-to-get'].'"'?>/>
					</div>
					<div id="page-group" class="sort-group">
						<label id="page-label">
							Page
						</label
							><input id="page" class="pagination-input" type="text" name="page" value=<?php echo '"'.$GLOBALS['HoldingVar']['page'].'"'?>/> of <?php echo countPages(intval($GLOBALS['HoldingVar']['number-to-get']));?>
					</div>
					<div id="from-dewey" class="sort-group">
						<label id="from-dewey-label">
							From Dewey
						</label
							><input id="from-dewey-input" class="dewey-input" type="text" name="fromdewey" value=<?php echo '"'.$GLOBALS['HoldingVar']['fromdewey'].'"'?>/>
					</div>
					<div id="to-dewey" class="sort-group">
						<label id="to-dewey-label">
							To Dewey
						</label
							><input id="to-dewey-input" class="dewey-input" type="text" name="todewey" value=<?php echo '"'.$GLOBALS['HoldingVar']['todewey'].'"'?>/>
					</div>
					<label id="has-image-label">
						Has Image?
					</label>
					<div class="button-group">
						<input type="radio" name="has-image" value="yes" <?php echo ($GLOBALS['HoldingVar']['has-image']=='yes')?'checked':''; ?>>Yes
						<input type="radio" name="has-image" value="no" <?php echo ($GLOBALS['HoldingVar']['has-image']=='no')?'checked':''; ?>>No
						<input type="radio" name="has-image" value="both" <?php echo ($GLOBALS['HoldingVar']['has-image']=='both')?'checked':''; ?>>Both
					</div>
				</div>
				<div id="filter">
					<div id="filter-text">
						<label id="filter-text-label">
							Search for
						</label
						><input type="text" name="filter" value=<?php echo '"'.$GLOBALS['HoldingVar']['filter'].'"'?>/>
					</div>
					<div id="read-buttons" class="filter-radio-group">
						<label id="read-buttons-label">
							Read:
						</label>
						<div class="button-group">
							<input type="radio" name="read" value="yes" <?php echo ($GLOBALS['HoldingVar']['read']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="read" value="no" <?php echo ($GLOBALS['HoldingVar']['read']=='no')?'checked':'' ?>>No
							<input type="radio" name="read" value="both" <?php echo ($GLOBALS['HoldingVar']['read']=='both')?'checked':'' ?>>Both
						</div>
					</div>
					<div id="reference-buttons" class="filter-radio-group">
						<label id="reference-buttons-label">
							Reference:
						</label>
						<div class="button-group">
							<input type="radio" name="reference" value="yes" <?php echo ($GLOBALS['HoldingVar']['reference']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="reference" value="no" <?php echo ($GLOBALS['HoldingVar']['reference']=='no')?'checked':'' ?>>No
							<input type="radio" name="reference" value="both" <?php echo ($GLOBALS['HoldingVar']['reference']=='both')?'checked':'' ?>>Both
						</div>
					</div>
					<div id="owned-buttons" class="filter-radio-group">
						<label id="owned-buttons-label">
							Owned:
						</label>
						<div class="button-group">
							<input type="radio" name="owned" value="yes" <?php echo ($GLOBALS['HoldingVar']['owned']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="owned" value="no" <?php echo ($GLOBALS['HoldingVar']['owned']=='no')?'checked':'' ?>>No
							<input type="radio" name="owned" value="both" <?php echo ($GLOBALS['HoldingVar']['owned']=='both')?'checked':'' ?>>Both
						</div>
					</div>
					<div id="loaned-buttons" class="filter-radio-group">
						<label id="loaned-buttons-label">
							Loaned:
						</label>
						<div class="button-group">
							<input type="radio" name="loaned" value="yes" <?php echo ($GLOBALS['HoldingVar']['loaned']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="loaned" value="no" <?php echo ($GLOBALS['HoldingVar']['loaned']=='no')?'checked':'' ?>>No
							<input type="radio" name="loaned" value="both" <?php echo ($GLOBALS['HoldingVar']['loaned']=='both')?'checked':'' ?>>Both
						</div>
					</div>
					<div id="shipping-buttons" class="filter-radio-group">
						<label id="shipping-buttons-label">
							Shipping:
						</label>
						<div class="button-group">
							<input type="radio" name="shipping" value="yes" <?php echo ($GLOBALS['HoldingVar']['shipping']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="shipping" value="no" <?php echo ($GLOBALS['HoldingVar']['shipping']=='no')?'checked':'' ?>>No
							<input type="radio" name="shipping" value="both" <?php echo ($GLOBALS['HoldingVar']['shipping']=='both')?'checked':'' ?>>Both
						</div>
					</div>
					<div id="reading-buttons" class="filter-radio-group">
						<label id="reading-buttons-label">
							Reading:
						</label>
						<div class="button-group">
							<input type="radio" name="reading" value="yes" <?php echo ($GLOBALS['HoldingVar']['reading']=='yes')?'checked':'' ?>>Yes
							<input type="radio" name="reading" value="no" <?php echo ($GLOBALS['HoldingVar']['reading']=='no')?'checked':'' ?>>No
							<input type="radio" name="reading" value="both" <?php echo ($GLOBALS['HoldingVar']['reading']=='both')?'checked':'' ?>>Both
						</div>
					</div>
				</div>
				<input type="hidden" id="viewInput" name="view" value="list" />
				<input type="hidden" id="currentidInput" name="currentid" value=<?php echo '"'.$GLOBALS['HoldingVar']['currentid'].'"'; ?> />
				<div id="filter-and-sort-submit">
					<button>
						Filter and Sort
					</button>
				</div>
			</div>
		</form>
		<div id="view-change">
			<button onclick="viewList()">
				List
			</button
			><button <disabled onclick="viewGrid()">
				Grid
			</button
			><button onclick="viewShelf()">
				Shelf
			</button>
		</div>
		<div id="page-navigation">
			<button <?php echo intval($GLOBALS['HoldingVar']['page'])==1?'disabled':''; ?> onclick=<?php echo "previousPage(".intval($GLOBALS['HoldingVar']['page']).")";?>>
				Previous Page
			</button
			><button <?php echo intval($GLOBALS['HoldingVar']['page'])==countPages(intval($GLOBALS['HoldingVar']['number-to-get']))?'disabled':''; ?> onclick=<?php echo "nextPage(".intval($GLOBALS['HoldingVar']['page']).")";?>>
				Next Page
			</button>
		</div>
	</div>
	<div id="grid" style="-webkit-overflow-scrolling: touch; visibility: hidden; display: none;">
	<?php
		echo makeBookGrid();
	?>
	</div>
	<div id="grid-view" style="-webkit-overflow-scrolling: touch; visibility: hidden; display: none;">
	<?php
		echo makeBookGridView();
	?>
	</div>
	<form id='bookshelf-form' action='bookshelf.php' method='POST'>
		<?php echo makeInputFields(); ?>
	</form>
	<?php
		echo makeEditorForm();
	?>
	<div id="page-navigation">
		<button <?php echo intval($GLOBALS['HoldingVar']['page'])==1?'disabled':''; ?> onclick=<?php echo "previousPage(".intval($GLOBALS['HoldingVar']['page']).")";?>>
			Previous Page
		</button
		><button <?php echo intval($GLOBALS['HoldingVar']['page'])==countPages(intval($GLOBALS['HoldingVar']['number-to-get']))?'disabled':''; ?> onclick=<?php echo "nextPage(".intval($GLOBALS['HoldingVar']['page']).")";?>>
			Next Page
		</button>
	</div>
</body>
<script type="text/javascript">
	<?php
		if (isset($GLOBALS['msg'])) {
			echo 'alert("'.str_replace('"', '\"', $GLOBALS['msg']).'");';
		}
	?>
</script>
</html>

<script>
	window.onload = function() {
		<?php
		if ($GLOBALS['HoldingVar']['view']=='list') {
			echo 'viewList();';
		} elseif ($GLOBALS['HoldingVar']['view']=='grid') {
			echo 'viewGrid();';
		} elseif ($GLOBALS['HoldingVar']['view']=='shelf') {
			echo 'viewShelf();';
		}
		if (isset($GLOBALS['alert-message'])) {
			echo "alert('".$GLOBALS['alert-message']."');";
		}
		?>
		if ($('#grid-view-'+<?php echo $GLOBALS['HoldingVar']['currentid']; ?>).length) {
			$('#grid').scrollTop($('#grid-view-'+<?php echo $GLOBALS['HoldingVar']['currentid']; ?>).offset().top);
			$('#grid').scrollTop($('#grid-'+<?php echo $GLOBALS['HoldingVar']['currentid']; ?>).offset().top);
		}
	}
	function openEditor (id) {
		$input = $('<input type="hidden" name="previouspage" value="index.php" />');
		$(id).append($input);
		document.getElementById(id).submit();
	}
	function nextPage(current) {
		console.log(current);
		document.getElementById("page").value=current+1;
		document.getElementById("sort-and-filter-form").submit();
	}
	function previousPage(current) {
		document.getElementById("page").value=current-1;
		document.getElementById("sort-and-filter-form").submit();
	}
	function viewList() {
		hideall();
		$('#viewInput').attr('value', 'list');
		$('#grid').css('visibility', 'visible');
		$('#grid').css('display', 'block');
	}
	function viewGrid() {
		hideall();
		$('#viewInput').attr('value', 'grid');
		$('#grid-view').css('visibility', 'visible');
		$('#grid-view').css('display', 'block');
	}
	function viewShelf() {
		hideall();
		$('#viewInput').attr('value', 'shelf');
		document.getElementById('bookshelf-form').submit();
	}
	function hideall() {
		$('#grid').css('visibility', 'hidden');
		$('#grid').css('display', 'none');
		$('#grid-view').css('visibility', 'hidden');
		$('#grid-view').css('display', 'none');
	}
</script>