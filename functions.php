<?php
	require 'database.php';
	function getConnection()
	{
		return Database::getConnection();
	}
	function makeBookGrid()
	{
		$grid = '';
		$bookIds = getBookIds(true);
		foreach ($bookIds as $id) {
			$grid = $grid . makeGridEntry($id);
		}
		return $grid;
	}
	function getShelfSetIds() {
		$limit = FALSE;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$order = "Dewey, LastName, FirstName, MiddleNames, Series2, Volume, Title2";
		$titlechange = "CASE WHEN Title LIKE 'The %' THEN TRIM(SUBSTR(Title from 4)) ELSE CASE WHEN Title LIKE 'An %' THEN TRIM(SUBSTR(Title from 3)) ELSE CASE WHEN Title LIKE 'A %' THEN TRIM(SUBSTR(Title from 2)) ELSE Title END END END AS Title2";
		$serieschange = "CASE WHEN Series LIKE 'The %' THEN TRIM(SUBSTR(Series from 4)) ELSE CASE WHEN Series LIKE 'An %' THEN TRIM(SUBSTR(Series from 3)) ELSE CASE WHEN Series LIKE 'A %' THEN TRIM(SUBSTR(Series from 2)) ELSE Series END END END AS Series2";
		$authors = "(SELECT  PersonID, AuthorRoles.BookID, LastName, MiddleNames, FirstName FROM persons JOIN (SELECT written_by.BookID, AuthorID FROM written_by WHERE Role='Author') AS AuthorRoles ON AuthorRoles.AuthorID = persons.PersonID ORDER BY LastName , MiddleNames , FirstName ) AS Authors";
		$owned = "IsOwned="."1";
		$filter = "WHERE ".$owned;
		$sql = "SELECT books.BookID, ".$titlechange.", ".$serieschange." FROM books LEFT JOIN ".$authors." ON books.BookID = Authors.BookID ".$filter." GROUP BY books.BookID ORDER BY " . $order . ($limit?" LIMIT " . $_POST['number-to-get'] . " OFFSET " . (($_POST['page']-1)*$_POST['number-to-get']):'');
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$ids = array();
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['BookID'];
		}
		return $ids;
	}
	function getBookIds($limit)
	{
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		if ($_POST['sort']=='title') {
			$order = "Title2, LastName, FirstName, MiddleNames";
		} elseif ($_POST['sort']=='series') {
			$order = "if(Series2='' or Series2 is null,1,0), Series2, Volume, LastName, FirstName, MiddleNames, Title2";
		} else {
			$order = "Dewey, LastName, FirstName, MiddleNames, Series2, Volume, Title2";
		}
		$titlechange = "CASE WHEN Title LIKE 'The %' THEN TRIM(SUBSTR(Title from 4)) ELSE CASE WHEN Title LIKE 'An %' THEN TRIM(SUBSTR(Title from 3)) ELSE CASE WHEN Title LIKE 'A %' THEN TRIM(SUBSTR(Title from 2)) ELSE Title END END END AS Title2";
		$serieschange = "CASE WHEN Series LIKE 'The %' THEN TRIM(SUBSTR(Series from 4)) ELSE CASE WHEN Series LIKE 'An %' THEN TRIM(SUBSTR(Series from 3)) ELSE CASE WHEN Series LIKE 'A %' THEN TRIM(SUBSTR(Series from 2)) ELSE Series END END END AS Series2";
		$authors = "(SELECT  PersonID, AuthorRoles.BookID, LastName, MiddleNames, FirstName FROM persons JOIN (SELECT written_by.BookID, AuthorID FROM written_by WHERE Role='Author') AS AuthorRoles ON AuthorRoles.AuthorID = persons.PersonID ORDER BY LastName , MiddleNames , FirstName ) AS Authors";
		$read = ($_POST['read']=='both'?"":($_POST['read']=='yes'?"IsRead="."1":"IsRead="."0"));
		$reference = ($_POST['reference']=='both'?"":($_POST['reference']=='yes'?"IsReference="."1":"IsReference="."0"));
		$owned = ($_POST['owned']=='both'?"":($_POST['owned']=='yes'?"IsOwned="."1":"IsOwned="."0"));
		$loaned = ($_POST['loaned']=='both'?"":($_POST['loaned']=='yes'?"LoaneeFirst IS NOT NULL OR LoaneeLast IS NOT NULL":"LoaneeFirst IS NULL AND LoaneeLast IS NULL"));
		$reading = ($_POST['reading']=='both'?"":($_POST['reading']=='yes'?"IsReading="."1":"IsReading="."0"));
		$shipping = ($_POST['shipping']=='both'?"":($_POST['shipping']=='yes'?"IsShipping="."1":"IsShipping="."0"));
		$startDewey = 'Dewey >= "'.formatDewey($_POST['fromdewey']).'"';
		$endDewey = 'Dewey <= "'.formatDewey($_POST['todewey']).'"';
		$filter = "WHERE ";
		if ($read != "") {
			$filter = $filter.$read;
		}
		if ($reference != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$reference;
		}
		if ($owned != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$owned;
		}

		// $lrs_yes = array();
		// $lrs_no = array();
		// if ($loaned != "") {
		// 	if (strpos($loaned, "0") > -1) {
		// 		array_push($lrs_no, $loaned);
		// 	} else {
		// 		array_push($lrs_yes, $loaned);
		// 	}
		// }
		// if ($reading != "") {
		// 	if (strpos($reading, "0") > -1) {
		// 		array_push($lrs_no, $reading);
		// 	} else {
		// 		array_push($lrs_yes, $reading);
		// 	}
		// }
		// if ($shipping != "") {
		// 	if (strpos($shipping, "0") > -1) {
		// 		array_push($lrs_no, $shipping);
		// 	} else {
		// 		array_push($lrs_yes, $shipping);
		// 	}
		// }
		// $lrs = "";
		// if (count($lrs_yes) > 0) {
		// 	$lrs = "(".join(" OR ", $lrs_yes).")";
		// }
		// if (count($lrs_no) > 0) {
		// 	if ($lrs != "") {
		// 		$lrs = $lrs." AND ";
		// 	}
		// 	$lrs = $lrs.join(" AND ", $lrs_no);
		// }
		// if ($lrs != "") {
		// 	if ($filter != "WHERE ") {
		// 		$filter = $filter." AND ";
		// 	}
		// 	$filter = $filter.$lrs;
		// }

		if ($loaned != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$loaned;
		}
		if ($reading != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$reading;
		}
		if ($shipping != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$shipping;
		}

		if ($startDewey != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$startDewey;
		}
		if ($endDewey != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$endDewey;
		}
		$filterText = formFilterText();
		if ($filterText != "") {
			if ($filter != "WHERE ") {
				$filter = $filter." AND ";
			}
			$filter = $filter.$filterText;
		}
		if ($filter == "WHERE " || $filter == "WHERE") {
			$filter = "";
		}
		$sql = "SELECT books.BookID, ".$titlechange.", ".$serieschange." FROM books LEFT JOIN ".$authors." ON books.BookID = Authors.BookID ".$filter." GROUP BY books.BookID ORDER BY " . $order . ($limit?" LIMIT " . $_POST['number-to-get'] . " OFFSET " . (($_POST['page']-1)*$_POST['number-to-get']):'');
		// $GLOBALS['msg'] = $sql;
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$ids = array();
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['BookID'];
		}
		return $ids;
	}
	function formatDewey($d) {
		$retval = '';
		if (!is_numeric($d)) {
			if ($d=='FIC') {
				$retval = $d;
			}
		} else {
			$i = floatval($d);
			if ($i < 10) {
				$i = '00'.$i;
			} elseif ($i < 100) {
				$i = '0'.$i;
			}
			$retval = $i;
		}
		return $retval;
	}
	function formFilterText() {
		$s = "";
		$filters = explode(' ', $_POST['filter']);
		foreach ($filters as $filter) {
			if ($filter != "") {
				$s = $s."(Title LIKE '%{$filter}%' OR Subtitle LIKE '%{$filter}%' OR Series LIKE '%{$filter}%' OR Dewey LIKE '%{$filter}%') AND ";
			}
		}
		if (substr($s, strlen($s)-5)==" AND ") {
			$s = substr($s, 0, strlen($s)-5);
		}
		return $s;
	}
	function makeGridEntry($id='')
	{
		if ($id=='') {
			return '';
		} else {
			$book = getBook($id);
			$retval = 			'<form action="editor.php" method="post" id="grid-'.$id.'">';
			$retval = $retval . makeInputFields();
			$retval = $retval . '<input type="hidden" name="bookid" value="'.$id.'" />';
			$retval = $retval .	'<div class="grid-book" onclick="openEditor(\'grid-'.$id.'\')">';
			$retval = $retval . 	'<div class="book-image">';
			$retval = $retval . 		'<img src="'.($book['ImageURL']==''?'http://i.istockimg.com/file_thumbview_approve/64101897/5/stock-photo-64101897-blank-book-cover-empty-template-single-brochure-textbook.jpg':$book['ImageURL']).'" alt="'.$book['Title'].'"></img>';
			$retval = $retval .		'</div> ';
			$retval = $retval .		'<div class="book-maininfo">';
			$retval = $retval .			'<div class="book-title">'.$book['Title'].'</div>';
			$retval = $retval .			'<div class="book-subtitle">'.$book['Subtitle'].'</div>';
			$retval = $retval .			'<div class="book-series-info">';
			if ($book['Series'] != '') {
			$retval = $retval .				'<span class="book-series">'.$book['Series'].'</span>';
			}
			if ($book['Volume'] != '0') {
			$retval = $retval . 			': <span class="book-volume">'.$book['Volume'].'</span>';
			}
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-edition-info">';
			if ($book['Edition'] != '1') {
			$retval = $retval .				'<span class="book-edition">'.$book['Edition'].'</span><span class="book-edition-suffix">'.ordinalEnding($book['Edition']).'</span> Edition';
			}
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-authors">';
			$retval = $retval .				'Authors';
			$retval = $retval .				'<div class="book-authors-list">'.makeAuthorBox($id, -1).'</div>';
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-dewey-info">';
			$retval = $retval .				'<span class="book-dewey">'.$book['Dewey'].'</span>';
			if (($genre=getGenre($book['Dewey']))!='') {
			$retval = $retval .				': <span class="book-genre">'.$genre.'</span>';
			}
			$retval = $retval .			'</div>';
			$retval = $retval .		'</div>';
			$retval = $retval .		'<div class="book-otherinfo">';
			$retval = $retval .			'<div class="book-publisher-info">';
			$retval = $retval .				'<div class="book-publisher-house-info">';
			if (($publisher=getPublisher($book['PublisherID']))!='') {
			$retval = $retval .					'Published by <span class="book-publisher">'.$publisher.'</span>';
			}
			$retval = $retval .				'</div>';
			$retval = $retval .				'<div class="book-publisher-place-info">';
			if (($location=stringLocation(getCity($book['PublisherID']),getState($book['PublisherID']),getCountry($book['PublisherID'])))!='') {
			$retval = $retval .					'at <span class=book-publisher-place-location>'.$location.'</span> ';
			}
			if (($date=stringDate($book['Copyright']))!='') {
			$retval = $retval .					'in <span class="book-year">'.$date.'</span>';
			}
			$retval = $retval .				'</div>';
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-language-info">';
			if (($languageText=stringLanguage($book['PrimaryLanguage'],$book['SecondaryLanguage'],$book['OriginalLanguage']))!='') {
			$retval = $retval .				$languageText;
			}
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-dimension-info">';
			if (($dimensionText=stringDimensions($book['Pages'], $book['Width'], $book['Height'],$book['Depth'],$book['Weight']))!='') {
			$retval = $retval .				$dimensionText;
			}
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-isbn-info">';
			if ($book['ISBN']!='') {
			$retval = $retval .				'ISBN: <span class="book-isbn">'.$book['ISBN'].'</span>';
			}
			$retval = $retval .			'</div>';
			if ($book['Format']!='') {
			$retval = $retval .			'<div class="book-format">'.$book['Format'].'</div>';
			}
			$retval = $retval .			'<div class="book-read-info">';
			$retval = $retval .				'<span class="book-read">'.($book['IsRead']=='1'?'':'Not').'</span> Read';
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-reference-info">';
			$retval = $retval .				'<span class="book-reference">'.($book['IsReference']=='1'?'':'Not').'</span> Reference';
			$retval = $retval .			'</div>';
			$retval = $retval .			'<div class="book-owned-info">';
			$retval = $retval .				'<span class="book-owned">'.($book['IsOwned']=='1'?'':'Not').'</span> Owned';
			$retval = $retval .			'</div>';
			if ($book['LoaneeFirst'] != '' or $book['LoaneeLast'] != '') {
			$retval = $retval .			'<div class="book-loanee-info">';
			$retval = $retval .				'<span class="book-loanee">Loaned to: '.$book['LoaneeFirst'].' '.$book['LoaneeLast'].'</span>';
			$retval = $retval .			'</div>';
			}
			$retval = $retval .			'<div class="book-id-info">';
			$retval = $retval .				'<span class="book-id">Book Id: '.$book['BookID'].'</span>';
			$retval = $retval .			'</div>';
			$retval = $retval .		'</div>';
			$retval = $retval .	'</div></form>';
			return $retval;
		}
	}
	function getBook($id)
	{
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM books WHERE BookId=".$id;
		$result = $conn->query($sql);
		if (!$result) {
			return false;
			die("Query failed: " . $conn->error);
		}
		$book = null;
		while ($row = $result->fetch_assoc()) {
			$book = $row;
		}
		return $book;
	}
	function ordinalEnding($n) {
		$i = (int)$n;
		if ($i%10==1) {
			if ($i%100==1) {
				return 'th';
			} else {
				return 'st';
			}
		} elseif ($i%10==2) {
			if ($i%100==1) {
				return 'th';
			} else {
				return 'nd';
			}
		} elseif ($i%10==3) {
			if ($i%100==1) {
				return 'th';
			} else {
				return 'rd';
			}
		} else {
			return 'th';
		}
	}
	function makeAuthorBox($bookId='', $limit=-1) {
		$authors = getAuthors($bookId);
		usort($authors, "compareAuthors");
		$i=0;
		$authorBox = '<div class="author-box">';
		while (($i<$limit || $limit==-1) && $i<count($authors)) {
			$authorBox = $authorBox . '<div class="authorname"><li><span class="firstname">'.$authors[$i]['FirstName'].'</span> <span class="middlenames">';
			foreach (explode(';',$authors[$i]['MiddleNames']) as $mn) {
				$authorBox = $authorBox.$mn.' ';
			}
			$authorBox = $authorBox.'</span><span class="lastname">'.$authors[$i]['LastName'].'</span>: <span class="role">'.$authors[$i]['Role'].'</span></li></div>';
			$i+=1;
		}
		return $authorBox.'</div>';
	}
	function getAuthors($bookId) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT FirstName, MiddleNames, LastName, Role FROM written_by LEFT JOIN persons ON written_by.AuthorId=persons.PersonId WHERE BookId=".$bookId;
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$authors = array();
		while ($row = $result->fetch_assoc()) {
			$authors[] = $row;
		}
		return $authors;
	}
	function compareAuthors($a, $b) {
		if ($a['Role']=='Author' && $b['Role']!='Author') {
			return -1;
		} elseif ($b['Role']=='Author' && $a['Role']!='Author') {
			return 1;
		} else {
			if (strcmp($a['Role'], $b['Role'])==0) {
				if (strcmp($a['LastName'], $b['LastName'])==0) {
					if (strcmp($a['FirstName'], $b['FirstName'])==0) {
						return strcmp($a['MiddleNames'], $b['MiddleNames']);
					} else {
						return strcmp($a['FirstName'], $b['FirstName']);
					}
				} else {
					return strcmp($a['LastName'], $b['LastName']);
				}
			} else {
				return strcmp($a['Role'], $b['Role']);
			}
		}
	}
	function getGenre($dewey) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM dewey_numbers WHERE Number ='".$dewey."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$genre = '';
		while ($row = $result->fetch_assoc()) {
			$genre = $row['Genre'];
		}
		return $genre;
	}
	function getPublisher($publisherID) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM publishers WHERE PublisherID ='".$publisherID."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$publisher = '';
		while ($row = $result->fetch_assoc()) {
			$publisher = $row['Publisher'];
		}
		return $publisher;
	}
	function getCity($publisherID) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM publishers WHERE PublisherID ='".$publisherID."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$city = '';
		while ($row = $result->fetch_assoc()) {
			$city = $row['City'];
		}
		return $city;
	}
	function getState($publisherID) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM publishers WHERE PublisherID ='".$publisherID."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$state = '';
		while ($row = $result->fetch_assoc()) {
			$state = $row['State'];
		}
		return $state;
	}
	function getCountry($publisherID) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM publishers WHERE PublisherID ='".$publisherID."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$country = '';
		while ($row = $result->fetch_assoc()) {
			$country = $row['Country'];
		}
		return $country;
	}
	function stringLocation($city='',$state='',$country='') {
		if ($city==''&&$state==''&&$country=='') {
			return '';
		}
		$city = $city.', ';
		$state = $state.', ';
		$location = $city.$state.$country;
		return $location;
	}
	function stringDate($date) {
		return substr($date, 0, strpos($date, '-'));
	}
	function stringLanguage($pl,$sl,$ol) {
		if ($pl==''&&$sl==''&&$ol=='') {
			return '';
		} else {
			if ($sl=='') {
				$s = 'Written in ' . ($pl==''?$ol:$pl);
				if ($pl != '' && $ol != '' & $pl!=$ol) {
					$s = $s.', Originally in '.$ol;
				}
			} else {
				if ($pl=='') {
					$s = 'Written in '.$sl;
					if ($ol!=''&&$ol!=$sl) {
						$s = $s.', Originally in '.$ol;
					}
				} else {
					$s = 'Written in '.$pl.' and '.$sl;
					if ($ol!='') {
						$s = $s.', Originally in '.$ol;
					}
				}
			}
		}
		return $s;
	}
	function stringDimensions($pages, $width, $height, $depth, $weight) {
		$s = '';
		if ($pages != '0') {
			$s = $s.$pages.' pages';
		}
		$dimensions = $width.'mm x '.$height.'mm x '.$depth.'mm';
		if ($dimensions != '0in x 0in x 0in') {
			$s = $s.($s==''?'':', ').$dimensions;
		}
		$w = $weight.'oz';
		if ($w != '0oz') {
			$s = $s.($s==''?'':', ').$weight;
		}
		return $s;
	}
	function getPerson($personId) {
		if ($personId=='') {
			$personId=-1;
		}
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT FirstName, MiddleNames, LastName FROM persons WHERE PersonId=".$personId;
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$person = '';
		while ($row = $result->fetch_assoc()) {
			$person = $row;
		}
		return $person;
	}
	function stringPerson($person)
	{
		$retval = '';
		if ($person=='') {
			return $retval;
		} else {
			$retval = $authors[$i]['FirstName'].(strlen($authors[$i]['FirstName'])==1?'.':'').' ';
			foreach (explode(';',$person['MiddleNames']) as $mn) {
				$retval = $retval.$mn.(strlen($mn)==1?'.':'').' ';
			}
			$retval = $retval==' '?''.$person['LastName']:$retval.$person['LastName'];
			return $retval;
		}
	}
	function getStatistics()
	{
		$ids = getOwnedIds();
		$idstring = join(',', $ids);
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$statistics = array();
		$statistics['Formats'] = countFormats($idstring);
		$statistics['Roles'] = countRoles($idstring);
		$statistics['Languages'] = countLanguages($idstring);
		$statistics['OnLoan'] = countOnLoan($idstring);
		$statistics['Reading'] = countReading($idstring);
		$statistics['Shipping'] = countShipping($idstring);
		$statistics['Deweys'] = countDeweys($idstring);
		$statistics['Books'] = countBooks($idstring);
		$statistics['Series'] = countSeries($idstring);
		$statistics['Read'] = countRead($idstring);
		$statistics['Reference'] = countReference($idstring);
		$statistics['Owned'] = countOwned($idstring);
		$statistics['Publishers'] = countPublishers($idstring);
		$statistics['Dimensions'] = calculateDimensions($idstring);
		return $statistics;
	}
	function getOwnedIds() {
		$ids = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT BookID FROM books WHERE IsOwned=1";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			array_push($ids, $row['BookID']);
		}
		return $ids;
	}
	function countFormats($idstring)
	{
		$formats = array();
		$formatList = getFormats();
		foreach ($formatList as $format) {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT COUNT(*) AS Count FROM books WHERE BookID IN (".$idstring.") AND Format='".$format."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			while ($row = $result->fetch_assoc()) {
				$formats[$format] = $row['Count'];
			}
		}
		return $formats;
	}
	function countRoles($idstring)
	{
		$roles = array();
		$roleList = getRoles();
		foreach ($roleList as $role) {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT COUNT(*) AS Count FROM (SELECT DISTINCT(AuthorID) FROM written_by WHERE BookID IN (".$idstring.") AND Role='".$role."') a";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			while ($row = $result->fetch_assoc()) {
				$roles[$role.'s'] = $row['Count'];
			}
		}
		return $roles;
	}
	function countLanguages($idstring)
	{
		$languages = array();
		$languageList = getLanguages();
		foreach ($languageList as $language) {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT COUNT(*) AS Count FROM books WHERE BookID IN (".$idstring.") AND PrimaryLanguage='".$language."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			while ($row = $result->fetch_assoc()) {
				$languages['Primary'][$language] = $row['Count'];
			}
			$sql = "SELECT COUNT(*) AS Count FROM books WHERE BookID IN (".$idstring.") AND SecondaryLanguage='".$language."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			while ($row = $result->fetch_assoc()) {
				$languages['Secondary'][$language] = $row['Count'];
			}
			$sql = "SELECT COUNT(*) AS Count FROM books WHERE BookID IN (".$idstring.") AND OriginalLanguage='".$language."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			while ($row = $result->fetch_assoc()) {
				$languages['Original'][$language] = $row['Count'];
			}
		}
		return $languages;
	}
	function countOnLoan($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE (LoaneeFirst IS NOT NULL OR LoaneeLast IS NOT NULL) AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countReading($idstring) {
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE IsReading=1 AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countShipping($idstring) {
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE IsShipping=1 AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countBooks($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countDeweys($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(DISTINCT Dewey) AS Count FROM books WHERE BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countSeries($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(DISTINCT Series) AS Count FROM books WHERE BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countRead($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE IsRead=1 AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countOwned($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE IsOwned=1 AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countReference($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(*) AS Count FROM books WHERE IsReference=1 AND BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function countPublishers($idstring)
	{
		$count = 0;
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT COUNT(DISTINCT PublisherID) AS Count FROM books WHERE BookID IN (".$idstring.")";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$count = $row['Count'];
		}
		return $count;
	}
	function calculateDimensions($idstring) {
		$dimensionInfo = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM (
				(SELECT AVG(Width) As AvgWidth, MIN(Width) AS MinWidth, MAX(Width) AS MaxWidth FROM books WHERE Width>0) AS w,
    			(SELECT AVG(Height) As AvgHeight, MIN(Height) AS MinHeight, MAX(Height) AS MaxHeight FROM books WHERE Height>0) AS h,
    			(SELECT AVG(Depth) As AvgDepth, MIN(Depth) AS MinDepth, MAX(Depth) AS MaxDepth FROM books WHERE Depth>0) AS d,
    			(SELECT AVG(Weight) As AvgWeight, MIN(Weight) AS MinWeight, MAX(Weight) AS MaxWeight FROM books WHERE Weight>0) AS we)";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$dimensionInfo['Average Width'] = round(floatval($row['AvgWidth']), 2).' mm';
			$dimensionInfo['Minimum Width'] = $row['MinWidth'].' mm';
			$dimensionInfo['Maximum Width'] = $row['MaxWidth'].' mm';
			$dimensionInfo['Average Height'] = round(floatval($row['AvgHeight']), 2).' mm';
			$dimensionInfo['Minimum Height'] = $row['MinHeight'].' mm';
			$dimensionInfo['Maximum Height'] = $row['MaxHeight'].' mm';
			$dimensionInfo['Average Depth'] = round(floatval($row['AvgDepth']), 2).' mm';
			$dimensionInfo['Minimum Depth'] = $row['MinDepth'].' mm';
			$dimensionInfo['Maximum Depth'] = $row['MaxDepth'].' mm';
			$dimensionInfo['Average Weight'] = round(floatval($row['AvgWeight']), 2).' oz';
			$dimensionInfo['Minimum Weight'] = $row['MinWeight'].' oz';
			$dimensionInfo['Maximum Weight'] = $row['MaxWeight'].' oz';
			foreach ($dimensionInfo as $key => $value) {
				if (floatval(substr($value, 0, strpos($value, ' '))) <= 0) {
					unset($dimensionInfo[$key]);
				}
			}
		}
		return $dimensionInfo;
	}
	function getRoles()
	{
		$roles = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT Role from roles";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$roles[] = $row['Role'];
		}
		return $roles;
	}
	function getLanguages()
	{
		$languages = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT Langauge from languages";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$languages[] = $row['Langauge'];
		}
		return $languages;
	}
	function getSeries()
	{
		$series = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT Series from series";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$series[] = $row['Series'];
		}
		return $series;
	}
	function getFormats()
	{
		$formats = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT Format from formats";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$formats[] = $row['Format'];
		}
		return $formats;
	}
	function getPublishers()
	{
		$publishers = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT DISTINCT(Publisher) from publishers";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$publishers[] = $row['Publisher'];
		}
		return $publishers;
	}
	function getCities()
	{
		$cities = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT DISTINCT(City) from publishers";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$cities[] = $row['City'];
		}
		return $cities;
	}
	function getStates()
	{
		$states = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT DISTINCT(State) from publishers";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$states[] = $row['State'];
		}
		return $states;
	}
	function getCountries()
	{
		$countries = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT DISTINCT(Country) from publishers";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$countries[] = $row['Country'];
		}
		return $countries;
	}
	function getDeweys()
	{
		$deweys = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT Number from dewey_numbers";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$deweys[] = $row['Number'];
		}
		return $deweys;
	}
	function getPersons() {
		$persons = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT FirstName, MiddleNames, LastName from persons";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$persons[] = $row['FirstName'] + ' ' + str_replace(';', ' ', $row['MiddleNames']) + ' ' + $row['LastName'];
			if (strpos($persons[count($persons)-1], ' ')==0) {
				$persons[count($persons)-1] = substr($persons[count($persons)-1], 1);
			}
		}
		return $persons;
	}
	function makeStatisticDiv()
	{
		$stats = getStatistics();
		$statsDiv = 			'<div id="statistics">';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">General:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-general" class="substats">';
		$statsDiv = $statsDiv .			'<label>Books:</label><span class="stat">'.$stats['Books'].'</span>';
		$statsDiv = $statsDiv .			'<label>Series:</label><span class="stat">'.$stats['Series'].'</span>';
		$statsDiv = $statsDiv .			'<label>Publishers:</label><span class="stat">'.$stats['Publishers'].'</span>';
		$statsDiv = $statsDiv .			'<label>Deweys:</label><span class="stat">'.$stats['Deweys'].'</span>';
		$statsDiv = $statsDiv .			'<label>Read:</label><span class="stat">'.$stats['Read'].' ('.round($stats['Read']/$stats['Books']*100, 2).'%)'.'</span>';
		$statsDiv = $statsDiv .			'<label>Reading:</label><span class="stat">'.$stats['Reading'].' ('.round($stats['Reading']/$stats['Books']*100, 2).'%)'.'</span>';
		$statsDiv = $statsDiv .			'<label>Reference:</label><span class="stat">'.$stats['Reference'].' ('.round($stats['Reference']/$stats['Books']*100, 2).'%)'.'</span>';
		$statsDiv = $statsDiv .			'<label>On Loan:</label><span class="stat">'.$stats['OnLoan'].' ('.round($stats['OnLoan']/$stats['Books']*100, 2).'%)'.'</span>';
		$statsDiv = $statsDiv .			'<label>Shipping:</label><span class="stat">'.$stats['Shipping'].' ('.round($stats['Shipping']/$stats['Books']*100, 2).'%)'.'</span>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Formats:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-formats" class="substats">';
		foreach ($stats['Formats'] as $format => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<label>'.$format.':</label><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span>';
			}
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Primary Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-primary" class="substats">';
		foreach ($stats['Languages']['Primary'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<label>'.$language.':</label><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span>';
			}
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Secondary Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-secondary" class="substats">';
		foreach ($stats['Languages']['Secondary'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<label>'.$language.':</label><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span>';
			}
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Original Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-original" class="substats" class="substats">';
		foreach ($stats['Languages']['Original'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<label>'.$language.':</label><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span>';
			}
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Roles:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-roles" class="substats">';
		foreach ($stats['Roles'] as $role => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<label>'.$role.':</label><span class="stat">'.$val.'</span>';
			}
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Dimensions:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-dimensions" class="substats">';
		foreach ($stats['Dimensions'] as $dimension => $val) {
			$statsDiv = $statsDiv .		'<label>'.$dimension.':</label><span class="stat">'.$val.'</span>';
		}
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .	'</div>';
		return $statsDiv;
	}
	function countPages($limit)
	{
		if ($limit<=0) {
			return 0;
		} else {
			$numBooks = count(getBookIds(false));
			return ceil($numBooks/$limit);
		}
	}
	function setDefaultValues() {
		if (!isset($_POST['sort'])) {
			$_POST['sort'] = 'dewey';
		}
		if (!isset($_POST['number-to-get'])) {
			$_POST['number-to-get'] = 2000;
		}
		if (intval($_POST['number-to-get'])<=0) {
			$_POST['number-to-get'] = 2000;
		}
		if (!isset($_POST['filter'])) {
			$_POST['filter'] = '';
		}
		if (!isset($_POST['read'])) {
			$_POST['read'] = 'both';
		}
		if (!isset($_POST['reference'])) {
			$_POST['reference'] = 'both';
		}
		if (!isset($_POST['owned'])) {
			$_POST['owned'] = 'yes';
		}
		if (!isset($_POST['loaned'])) {
			$_POST['loaned'] = 'no';
		}
		if (!isset($_POST['shipping'])) {
			$_POST['shipping'] = 'no';
		}
		if (!isset($_POST['reading'])) {
			$_POST['reading'] = 'no';
		}
		if (!isset($_POST['page'])) {
			$_POST['page'] = 1;
		}
		if (intval($_POST['page'])<=0) {
			$_POST['page'] = 1;
		}
		if (!isset($_POST['view'])) {
			$_POST['view'] = 'list';
		}
		if (!isset($_POST['fromdewey'])) {
			$_POST['fromdewey'] = '0';
		}
		if (!isset($_POST['todewey'])) {
			$_POST['todewey'] = 'FIC';
		}
		if (!isset($_POST['currentid'])) {
			$_POST['currentid'] = '-1';
		}
		if (!isset($_POST['previouspage'])) {
			$_POST['previouspage'] = 'index.php';
		}
	}
	function getUser($username, $password) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT id,usr FROM library_members WHERE usr='".$username."' AND pass='".$password."'";
		$result = $conn->query($sql);
		if (!$result) {
			return $retval = array();
		}
		return $result->fetch_assoc();
	}
	function makeLogo()
	{
		$retval = 			'<a href="index.php" class="nostyle">';
		$retval = $retval .		'<div id="logo">';
		$retval = $retval .			'<h1>Library Organizer</h1>';
		$retval = $retval .		'</div>';
		$retval = $retval .	'</a>';
		return $retval;
	}
	function stringSelection($arr)
	{
		$retval = '';
		if (sizeof($arr)>0) {
			for ($i=0; $i<sizeof($arr); $i++) {
				$retval = $retval . '<option>'.$arr[$i].'</option>';
			}
		}
		return $retval;
	}
	function parsePerson($p) {
		$p = str_replace('.', '', $p);
		$fn = '';
		$mn = '';
		$ln = '';
		if ($cp=strpos($p, ',') !== FALSE) {
			$ln = substr($p, 0, $cp);
			if ($cp != strlen($p)-1) {
				$p = substr($p, strpos($p, ',')+2);
				$names = explode(',', $p);
				$fn = $names[0];
				$mn = implode(';',array_splice($names, 1));
			}
		} else {
			if ($sp=strpos($p, ' ') !== FALSE) {
				$names = explode($p, ' ');
				$fn = $names[0];
				$ln = $names[count($names)-1];
				for ($i=1; $i<count($names)-1; $i++) {
					$mn += $names[$i]+';';
				}
				if (strpos($mn, ';')==strlen($mn)) {
					$mn = substr($mn, 0, strlen($mn)-1);
				}
			} else {
				$ln = $p;
			}
		}
		$names = array();
		array_push($names, $fn);
		array_push($names, $mn);
		array_push($names, $ln);
		return $names;
	}
	function saveBook() {
		if ($book = getBook($_POST['bookid'])) {
			$err = updateBook($_POST['bookid']);
		} else {
			$err = addBook();
		}
		if ($err) {
			$GLOBALS['alert-message'] = 'Failed to save the book:\n'.str_replace("'", "\'", str_replace("\'", "'", $err));
		}
	}
	function removeBook() {
		$err = deleteBook();
		if (!$err) {
			$GLOBALS['alert-message'] = 'Successfully removed the book!';
		} else {
			$GLOBALS['alert-message'] = 'Failed to remove the book:\n'.str_replace(str_replace("'", "\'", $err));
		}
	}
	function formatDate($d) {
		return $d==''?'NULL':$d;
	}
	function addBook() {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}

		$title = str_replace("'", "\'", $_POST['title']);
		$subtitle = $_POST['subtitle']==''?'NULL':str_replace("'", "\'", $_POST['subtitle']);
		$copyright = formatDate($_POST['Copyright']);
		$pubid = addOrGetPublisher(str_replace("'", "\'", $_POST['Publisher']), str_replace("'", "\'", $_POST['City']), str_replace("'", "\'", $_POST['State']), str_replace("'", "\'", $_POST['Country']));
		$read = (isset($_POST['isread']) && $_POST['isread']=='1')?'1':'0';
		$reference = (isset($_POST['isreference']) && $_POST['isreference']=='1')?'1':'0';
		$owned = (isset($_POST['isowned']) && $_POST['isowned']=='1')?'1':'0';
		$reading = (isset($_POST['isreading']) && $_POST['isreading']=='1')?'1':'0';
		$shipping = (isset($_POST['isshipping']) && $_POST['isshipping']=='1')?'1':'0';
		$isbn = $_POST['isbn']==''?'NULL':$_POST['isbn'];
		$loaneefirst = $_POST['loaneefirst']==''?'NULL':str_replace("'", "\'", $_POST['loaneefirst']);
		$loaneelast = $_POST['loaneelast']==''?'NULL':str_replace("'", "\'", $_POST['loaneelast']);
		$dewey = $_POST['dewey']==''?'':formatDewey($_POST['dewey']);
		$pages = $_POST['pages'];
		$width = $_POST['width'];
		$height = $_POST['height'];
		$depth = $_POST['depth'];
		$weight = $_POST['weight'];
		$primary = str_replace("'", "\'", $_POST['primary-language']);
		$secondary = str_replace("'", "\'", $_POST['secondary-language']);
		$original = str_replace("'", "\'", $_POST['original-language']);
		$series = str_replace("'", "\'", $_POST['series']);
		$volume = $_POST['volume'];
		$format = str_replace("'", "\'", $_POST['format']);
		$edition = $_POST['edition'];

		$dewey=str_replace("'", "\'", addDewey($dewey));
		$series=str_replace("'", "\'", addSeries($series));
		$format=str_replace("'", "\'", addFormat($format));
		$primary=str_replace("'", "\'", addLanguage($primary));
		$secondary=str_replace("'", "\'", addLanguage($secondary));
		$original=str_replace("'", "\'", addLanguage($original));

		$sql = "INSERT INTO books (Title, Subtitle, Copyright, PublisherID, IsRead, IsReference, IsOwned, IsShipping, IsReading, ISBN, LoaneeFirst, LoaneeLast, Dewey, Pages, Width, Height, Depth, Weight, PrimaryLanguage, SecondaryLanguage, OriginalLanguage, Series, Volume, Format, Edition, ImageURL)".
				"VALUES ('".$title."', ".($subtitle=='NULL'?$subtitle:"'".$subtitle."'").", ".($copyright=='NULL'?$copyright:"'".$copyright."'").", ".$pubid.", ".$read.", ".$reference.", ".$owned.", ".$shipping.", ".$reading.", ".($isbn=='NULL'?$isbn:"'".$isbn."'").", ".$loaneefirst.", ".$loaneelast.", ".($dewey=='NULL'?$dewey:"'".$dewey."'").", ".$pages.", ".$width.", ".$height.", ".$depth.", ".$weight.", ".($primary=='NULL'?$primary:"'".$primary."'").", ".($secondary=='NULL'?$secondary:"'".$secondary."'").", ".($original=='NULL'?$original:"'".$original."'").", ".($series=='NULL'?$series:"'".$series."'").", ".$volume.", ".($format=='NULL'?$format:"'".$format."'").", ".$edition.", '".$imageurl."')";
		while (strpos($sql, '\\\\')) {
			$sql = str_replace('\\\\', '\\', $sql);
		}
		if ($conn->query($sql) === TRUE) {
			$bookId = $conn->insert_id;
			foreach ($_POST['authors'] as $author) {
				addWrittenBy($bookId, $author['firstname'], str_replace(' ', ';', $author['middlenames']), $author['lastname'], $author['role']);
			}
			$_POST['bookid']=$bookId;
			$sql = "UPDATE books SET ImageURL='res/bookimages/".$bookid.".jpg' WHERE BookID=".$bookid;
			if ($conn->query($sql) !== TRUE) {
				return "Error: " . $sql . "<br>" . $conn->error;
			}
			if ($_POST['imageurl'] != '') {
				$out = 'res/bookimages/'.$bookid.'.jpg';
				$contents = file_get_contents($_POST['imageurl']);
				if ($contents) {
					$byteCount = file_put_contents($out, $contents);
					if (!$byteCount) {
						return "Error: " . "Unable to get image";
					}
				} else {
					return "Error: " . "Unable to get image";
				}
			}
		} else {
			return "Error: " . $sql . "<br>" . $conn->error;
		}
		return null;
	}
	function addAuthor($author, $bookid) {
		$author = explode(' - ', $author);
		$person = parsePerson($author[0]);
		$role = $author[1];
		addWrittenBy($bookid, $person[0], $person[1], $person[2], $role);
	}
	function addOrGetPublisher($publisher, $city, $state, $country) {
		if ($publisher=='' && $city=='' && $state=='' && $country=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				return die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT PublisherID FROM publishers WHERE Publisher='".$publisher."' AND City='".$city."' AND State='".$state."' AND Country='".$country."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['PublisherID'];
			}
			if ($id == '') {
				$sql = "INSERT INTO publishers (Publisher, City, State, Country) VALUES ('".$publisher."','".$city."','".$state."','".$country."')";
				if ($conn->query($sql) === TRUE) {
					$id = $conn->insert_id;
				}
			}
			return $id;
		}
	}
	function addOrGetPerson($first, $middle, $last) {
		if ($first=='' && $middle=='' && $last=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				echo die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT PersonID FROM persons WHERE FirstName='".$first."' AND MiddleNames='".$middle."' AND LastName='".$last."'";
			$result = $conn->query($sql);
			if (!$result) {
				echo die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['PersonID'];
			}
			if ($id == '') {
				$sql = "INSERT INTO persons (FirstName, MiddleNames, LastName) VALUES ('".$first."','".$middle."','".$last."')";
				if ($conn->query($sql) === TRUE) {
					$id = $conn->insert_id;
				}
			}
			return $id;
		}
	}
	function addDewey($d) {
		if ($d=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM dewey_numbers WHERE Number='".$d."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['Number'];
			}
			if ($id == '') {
				$sql = "INSERT INTO dewey_numbers (Number) VALUES ('".$d."')";
				$conn->query($sql);
			}
			return $d;
		}
	}
	function addFormat($f) {
		if ($f=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM formats WHERE Format='".$f."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['Format'];
			}
			if ($id == '') {
				$sql = "INSERT INTO formats (Format) VALUES ('".$f."')";
				$conn->query($sql);
			}
			return $f;
		}
	}
	function addLanguage($l) {
		if ($l=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM languages WHERE Langauge='".$l."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['Langauge'];
			}
			if ($id == '') {
				$sql = "INSERT INTO languages (Langauge) VALUES ('".$l."')";
				$conn->query($sql);
			}
			return $l;
		}
	}
	function addRole($r) {
		if ($r=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				return die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM roles WHERE Role='".$r."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['Role'];
			}
			if ($id == '') {
				$sql = "INSERT INTO roles (Role) VALUES ('".$r."')";
				$conn->query($sql);
			}
			return $r;
		}
	}
	function addSeries($s) {
		if ($s=='') {
			return 'NULL';
		} else {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				return die("Connection failed: " . $conn->connect_error);
			}
			$sql = "SELECT * FROM series WHERE Series='".$s."'";
			$result = $conn->query($sql);
			if (!$result) {
				die("Query failed: " . $conn->error);
			}
			$id = '';
			while ($row = $result->fetch_assoc()) {
				$id = $row['Series'];
			}
			if ($id == '') {
				$sql = "INSERT INTO series (Series) VALUES ('".$s."')";
				$conn->query($sql);
			}
			return $s;
		}
	}
	function addWrittenBy($bookid, $first, $middle, $last, $role) {
		$pid = addOrGetPerson($first, $middle, $last);
		addRole($role);
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM written_by WHERE BookId='".$bookid."' AND AuthorId='".$pid."' AND Role='".$role."'";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$id = '';
		while ($row = $result->fetch_assoc()) {
			$id = $row['BookId'];
		}
		if ($id == '') {
			$sql = "INSERT INTO written_by (BookId, AuthorId, Role) VALUES ('".$bookid."','".$pid."','".$role."')";
			$conn->query($sql);
		}
	}
	function removeAllWrittenBy($bookid) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}
		$sql = "DELETE FROM written_by WHERE BookId='".$bookid."'";
		$conn->query($sql);
	}
	function updateBook($id) {
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}

		removeAllWrittenBy($id);

		foreach ($_POST['authors'] as $author) {
			addWrittenBy($id, $author['firstname'], str_replace(' ', ';', $author['middlenames']), $author['lastname'], $author['role']);
		}

		$title = str_replace("'", "\'", $_POST['title']);
		$subtitle = $_POST['subtitle']==''?'NULL':str_replace("'", "\'", $_POST['subtitle']);
		$copyright = formatDate($_POST['Copyright']);
		$pubid = addOrGetPublisher(str_replace("'", "\'", $_POST['Publisher']), str_replace("'", "\'", $_POST['City']), str_replace("'", "\'", $_POST['State']), str_replace("'", "\'", $_POST['Country']));
		$read = (isset($_POST['isread']) && $_POST['isread']=='1')?'1':'0';
		$reference = (isset($_POST['isreference']) && $_POST['isreference']=='1')?'1':'0';
		$owned = (isset($_POST['isowned']) && $_POST['isowned']=='1')?'1':'0';
		$reading = (isset($_POST['isreading']) && $_POST['isreading']=='1')?'1':'0';
		$shipping = (isset($_POST['isshipping']) && $_POST['isshipping']=='1')?'1':'0';
		$isbn = $_POST['isbn']==''?'NULL':$_POST['isbn'];
		$loaneefirst = $_POST['loaneefirst']==''?'NULL':str_replace("'", "\'", $_POST['loaneefirst']);
		$loaneelast = $_POST['loaneelast']==''?'NULL':str_replace("'", "\'", $_POST['loaneelast']);
		$dewey = $_POST['dewey']==''?'':formatDewey($_POST['dewey']);
		$pages = $_POST['pages'];
		$width = $_POST['width'];
		$height = $_POST['height'];
		$depth = $_POST['depth'];
		$weight = $_POST['weight'];
		$primary = str_replace("'", "\'", $_POST['primary-language']);
		$secondary = str_replace("'", "\'", $_POST['secondary-language']);
		$original = str_replace("'", "\'", $_POST['original-language']);
		$series = str_replace("'", "\'", $_POST['series']);
		$volume = $_POST['volume'];
		$format = str_replace("'", "\'", $_POST['format']);
		$edition = $_POST['edition'];

		$dewey=str_replace("'", "\'", addDewey($dewey));
		$series=str_replace("'", "\'", addSeries($series));
		$format=str_replace("'", "\'", addFormat($format));
		$primary=str_replace("'", "\'", addLanguage($primary));
		$secondary=str_replace("'", "\'", addLanguage($secondary));
		$original=str_replace("'", "\'", addLanguage($original));

		$sql = "UPDATE books SET".
				" Title=".($title=='NULL'?$title:"'".$title."'").
				", Subtitle=".($subtitle=='NULL'?$subtitle:"'".$subtitle."'").
				", Copyright=".($copyright=='NULL'?$copyright:"'".$copyright."'").
				", PublisherID=".$pubid.
				", IsRead=".$read.
				", IsReference=".$reference.
				", IsOwned=".$owned.
				", IsShipping=".$shipping.
				", IsReading=".$reading.
				", isbn=".($isbn=='NULL'?$isbn:"'".$isbn."'").
				", LoaneeFirst=".$loaneefirst.
				", LoaneeLast=".$loaneelast.
				", Dewey=".($dewey=='NULL'?$dewey:"'".$dewey."'").
				", Pages=".$pages.
				", Width=".$width.
				", Height=".$height.
				", Depth=".$depth.
				", Weight=".$weight.
				", PrimaryLanguage=".($primary=='NULL'?$primary:"'".$primary."'").
				", SecondaryLanguage=".($secondary=='NULL'?$secondary:"'".$secondary."'").
				", OriginalLanguage=".($original=='NULL'?$original:"'".$original."'").
				", Series=".($series=='NULL'?$series:"'".$series."'").
				", Volume=".$volume.
				", Format=".($format=='NULL'?$format:"'".$format."'").
				", Edition=".$edition.
				" WHERE BookId=".$id;
		while (strpos($sql, '\\\\')) {
			$sql = str_replace('\\\\', '\\', $sql);
		}
		if ($conn->query($sql) === TRUE) {
			if ($_POST['imageurl'] != '') {
				$out = 'res/bookimages/'.$id.'.jpg';
				$contents = file_get_contents($_POST['imageurl']);
				if ($contents) {
					$byteCount = file_put_contents($out, $contents);
					if (!$byteCount) {
						return "Error: " . "Unable to get image";
					}
				} else {
					return "Error: " . "Unable to get image";
				}
			}
			return null;
		} else {
			return "Error: " . $sql . "<br>" . $conn->error;
		}
		return null;
	}
	function deleteBook() {
		if ($_POST['bookid']) {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				return die("Connection failed: " . $conn->connect_error);
			}
			$sql = 'DELETE FROM books WHERE BookID='.$_POST['bookid'];
			if ($conn->query($sql) === TRUE) {
				return null;
			} else {
				return "Error: " . $sql . "<br>" . $conn->error;
			}
			return null;
		}
		return null;
	}
	function fillShelf() {
		if (isset($_POST['reloadShelf']) && $_POST['reloadShelf']=='true') {
			$selectedIds = getBookIds(TRUE);
			$allIds = getShelfSetIds();
			$shelves = getShelves();
			$retval = '<script>';
			$retval = $retval . 	'var selectedBooks = [';
			foreach ($selectedIds as $id) {
				$retval = $retval . 	'{';
				$retval = $retval .			'id: "'.$id.'"';
				$retval = $retval . 	'},';
			}
			if (strrpos($retval, ',', -strlen($retval)) !== FALSE) {
				$retval = rtrim($retval);
			}
			$retval = $retval . 	'];';
			$retval = $retval . 	'var allBooks = [';
			foreach ($allIds as $id) {
				$book = getBook($id);
				$retval = $retval . 	'{';
				$retval = $retval . 		'text: "'.str_replace('"', ",", $book['Title']).'",';
				$retval = $retval . 		'width: '.(intval($book['Width'])<=0?'25':$book['Width']).',';
				$retval = $retval . 		'height: '.(intval($book['Height'])<=0?'200':$book['Height']).',';
				$retval = $retval . 		'image: "'.str_replace('"', ",", $book['ImageURL']).'",';
				$retval = $retval .			'id: "'.$id.'"';
				$retval = $retval . 	'},';
			}
			if (strrpos($retval, ',', -strlen($retval)) !== FALSE) {
				$retval = rtrim($retval);
			}
			$retval = $retval . 	'];';
			$retval = $retval . 	'var shelves = [';
			foreach ($shelves as $shelf) {
				$retval = $retval . 	'{';
				$retval = $retval . 		'width: '.$shelf['Width'].',';
				$retval = $retval . 		'shelfHeight: '.$shelf['ShelfHeight'].',';
				$retval = $retval . 		'numShelves: '.$shelf['NumShelves'].',';
				$retval = $retval . 		'spacerHeight: '.$shelf['SpacerHeight'].',';
				$retval = $retval . 		'paddingLeft: '.$shelf['PaddingLeft'].',';
				$retval = $retval . 		'paddingRight: '.$shelf['PaddingRight'].',';
				$retval = $retval . 		'bookMargin: '.$shelf['BookMargin'];
				$retval = $retval . 	'},';
			}
			if (strrpos($retval, ',', -strlen($retval)) !== FALSE) {
				$retval = rtrim($retval);
			}
			$retval = $retval . 	'];';
			$retval = $retval . 	'loadPage(selectedBooks, true, allBooks, shelves)';
			return $retval.'</script>';
		} else {
			$ids = getBookIds(TRUE);
			$retval = '<script>';
			$retval = $retval . 	'var books = [';
			foreach ($ids as $id) {
				$retval = $retval . '{id: "'.$id.'"},';
			}
			if (strrpos($retval, ',', -strlen($retval)) !== FALSE) {
				$retval = rtrim($retval);
			}
			$retval = $retval . 	'];';
			$retval = $retval . 	'loadPage(books, false)';
			return $retval.'</script>';
		}
	}
	function getShelves() {
		$shelves = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * from bookcases ORDER BY CaseNumber";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$shelves[] = $row;
		}
		return $shelves;
	}
	function makeEditorForm() {
		$retval = 			'<form action="editor.php" method="post" id="editorForm">';
		$retval = $retval . makeInputFields();
		$retval = $retval . '</form>';
		return $retval;
	}
	function makeBookGridView() {
		$grid = '';
		$bookIds = getBookIds(true);
		foreach ($bookIds as $id) {
			$grid = $grid . makeGridViewEntry($id);
		}
		return $grid;
	}
	function makeGridViewEntry($id) {
		if ($id=='') {
			return '';
		} else {
			$book = getBook($id);
			$retval = 			'<form action="editor.php" method="post" id="grid-view-'.$id.'">';
			$retval = $retval . 	makeInputFields();
			$retval = $retval . 	'<input type="hidden" name="bookid" value="'.$id.'" />';
			$retval = $retval .		'<div class="grid-view-entry" onclick="openEditor(\'grid-view-'.$id.'\')">';
			$retval = $retval . 		'<div class="grid-view-entry-picture">';
			$retval = $retval . 			'<img src="'.($book['ImageURL']==''?'http://i.istockimg.com/file_thumbview_approve/64101897/5/stock-photo-64101897-blank-book-cover-empty-template-single-brochure-textbook.jpg':$book['ImageURL']).'" alt="'.$book['Title'].'"></img>';
			$retval = $retval . 		'</div>';
			$retval = $retval . 		'<div class="grid-view-entry-title">';
			$retval = $retval . 			'<div class="book-view-entry-title">'.$book['Title'].'</div>';
			$retval = $retval . 		'</div>';
			$retval = $retval . 	'</div>';
			$retval = $retval . '</form>';
			return $retval;
		}
	}
	function writeToFile($file, $data) {
		$f = fopen($file, "w") or die("Unable to open file ".$file);
		fwrite($f, $data);
		fclose($f);
	}
	function readFromFile($file) {
		return file_get_contents($file);
	}
	function makeInputFields() {
		$retval = 				'<input type="hidden" name="sort" value="'.$_POST['sort'].'">';
		$retval = $retval . 	'<input type="hidden" name="number-to-get" value="'.$_POST['number-to-get'].'">';
		$retval = $retval . 	'<input type="hidden" name="filter" value="'.$_POST['filter'].'">';
		$retval = $retval . 	'<input type="hidden" name="read" value="'.$_POST['read'].'">';
		$retval = $retval . 	'<input type="hidden" name="reference" value="'.$_POST['reference'].'">';
		$retval = $retval . 	'<input type="hidden" name="owned" value="'.$_POST['owned'].'">';
		$retval = $retval . 	'<input type="hidden" name="shipping" value="'.$_POST['shipping'].'">';
		$retval = $retval . 	'<input type="hidden" name="reading" value="'.$_POST['reading'].'">';
		$retval = $retval . 	'<input type="hidden" name="page" value="'.$_POST['page'].'">';
		$retval = $retval . 	'<input type="hidden" name="view" value="'.$_POST['view'].'">';
		$retval = $retval . 	'<input type="hidden" name="loaned" value="'.$_POST['loaned'].'">';
		$retval = $retval . 	'<input type="hidden" name="fromdewey" value="'.$_POST['fromdewey'].'">';
		$retval = $retval . 	'<input type="hidden" name="todewey" value="'.$_POST['todewey'].'">';
		$retval = $retval . 	'<input type="hidden" name="currentid" value="'.$_POST['currentid'].'">';
		return $retval;
	}
?>