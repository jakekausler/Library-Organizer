<?php
	require_once 'database.php';
	require_once('lib/image-color-extract/colors.inc.php');
	$GLOBALS['HoldingVar'] = [];
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$GLOBALS['HoldingVar'] = $_GET;
			break;
		case 'POST':
			$GLOBALS['HoldingVar'] = $_POST;
			break;
	}
	if (!function_exists('getBookConnection')) {
	function getBookConnection()
	{
		return BookDatabase::getConnection();
	}
	}
	if (!function_exists('getBinderConnection')) {
	function getBinderConnection()
	{
		return BinderDatabase::getConnection();
	}
	}
	if (!function_exists('makeBookGrid')) {
	function makeBookGrid()
	{
		$grid = '';
		$bookIds = getBookIds(true);
		foreach ($bookIds as $id) {
			$grid = $grid . makeGridEntry($id);
		}
		return $grid;
	}
	}
	if (!function_exists('getShelfSetIds')) {
	function getShelfSetIds() {
		$limit = FALSE;
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$order = "Dewey, LastName, FirstName, MiddleNames, Series2, Volume, Title2";
		$titlechange = "CASE WHEN Title LIKE 'The %' THEN TRIM(SUBSTR(Title from 4)) ELSE CASE WHEN Title LIKE 'An %' THEN TRIM(SUBSTR(Title from 3)) ELSE CASE WHEN Title LIKE 'A %' THEN TRIM(SUBSTR(Title from 2)) ELSE Title END END END AS Title2";
		$serieschange = "CASE WHEN Series LIKE 'The %' THEN TRIM(SUBSTR(Series from 4)) ELSE CASE WHEN Series LIKE 'An %' THEN TRIM(SUBSTR(Series from 3)) ELSE CASE WHEN Series LIKE 'A %' THEN TRIM(SUBSTR(Series from 2)) ELSE Series END END END AS Series2";
		$authors = "(SELECT  PersonID, AuthorRoles.BookID, LastName, MiddleNames, FirstName FROM persons JOIN (SELECT written_by.BookID, AuthorID FROM written_by WHERE Role='Author') AS AuthorRoles ON AuthorRoles.AuthorID = persons.PersonID ORDER BY LastName , MiddleNames , FirstName ) AS Authors";
		$owned = "IsOwned="."1";
		$filter = "WHERE ".$owned;
		$sql = "SELECT books.BookID, ".$titlechange.", ".$serieschange." FROM books LEFT JOIN ".$authors." ON books.BookID = Authors.BookID ".$filter." GROUP BY books.BookID ORDER BY " . $order . ($limit?" LIMIT " . $GLOBALS['HoldingVar']['number-to-get'] . " OFFSET " . (($GLOBALS['HoldingVar']['page']-1)*$GLOBALS['HoldingVar']['number-to-get']):'');
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
	}
	if (!function_exists('getBookIds')) {
	function getBookIds($limit)
	{
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		if ($GLOBALS['HoldingVar']['sort']=='title') {
			$order = "Title2, LastName, FirstName, MiddleNames";
		} elseif ($GLOBALS['HoldingVar']['sort']=='series') {
			$order = "if(Series2='' or Series2 is null,1,0), Series2, Volume, LastName, FirstName, MiddleNames, Title2";
		} else {
			$order = "Dewey, LastName, FirstName, MiddleNames, Series2, Volume, Title2";
		}
		$titlechange = "CASE WHEN Title LIKE 'The %' THEN TRIM(SUBSTR(Title from 4)) ELSE CASE WHEN Title LIKE 'An %' THEN TRIM(SUBSTR(Title from 3)) ELSE CASE WHEN Title LIKE 'A %' THEN TRIM(SUBSTR(Title from 2)) ELSE Title END END END AS Title2";
		$serieschange = "CASE WHEN Series LIKE 'The %' THEN TRIM(SUBSTR(Series from 4)) ELSE CASE WHEN Series LIKE 'An %' THEN TRIM(SUBSTR(Series from 3)) ELSE CASE WHEN Series LIKE 'A %' THEN TRIM(SUBSTR(Series from 2)) ELSE Series END END END AS Series2";
		$authors = "(SELECT  PersonID, AuthorRoles.BookID, LastName, MiddleNames, FirstName FROM persons JOIN (SELECT written_by.BookID, AuthorID FROM written_by WHERE Role='Author') AS AuthorRoles ON AuthorRoles.AuthorID = persons.PersonID ORDER BY LastName , MiddleNames , FirstName ) AS Authors";
		$read = ($GLOBALS['HoldingVar']['read']=='both'?"":($GLOBALS['HoldingVar']['read']=='yes'?"IsRead="."1":"IsRead="."0"));
		$reference = ($GLOBALS['HoldingVar']['reference']=='both'?"":($GLOBALS['HoldingVar']['reference']=='yes'?"IsReference="."1":"IsReference="."0"));
		$owned = ($GLOBALS['HoldingVar']['owned']=='both'?"":($GLOBALS['HoldingVar']['owned']=='yes'?"IsOwned="."1":"IsOwned="."0"));
		$loaned = ($GLOBALS['HoldingVar']['loaned']=='both'?"":($GLOBALS['HoldingVar']['loaned']=='yes'?"LoaneeFirst IS NOT NULL OR LoaneeLast IS NOT NULL":"LoaneeFirst IS NULL AND LoaneeLast IS NULL"));
		$reading = ($GLOBALS['HoldingVar']['reading']=='both'?"":($GLOBALS['HoldingVar']['reading']=='yes'?"IsReading="."1":"IsReading="."0"));
		$shipping = ($GLOBALS['HoldingVar']['shipping']=='both'?"":($GLOBALS['HoldingVar']['shipping']=='yes'?"IsShipping="."1":"IsShipping="."0"));
		$startDewey = 'Dewey >= "'.formatDewey($GLOBALS['HoldingVar']['fromdewey']).'"';
		$endDewey = 'Dewey <= "'.formatDewey($GLOBALS['HoldingVar']['todewey']).'"';
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
		
}		if ($shipping != "") {
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
		$sql = "SELECT books.BookID, ".$titlechange.", ".$serieschange." FROM books LEFT JOIN ".$authors." ON books.BookID = Authors.BookID ".$filter." GROUP BY books.BookID ORDER BY " . $order . ($limit?" LIMIT " . $GLOBALS['HoldingVar']['number-to-get'] . " OFFSET " . (($GLOBALS['HoldingVar']['page']-1)*$GLOBALS['HoldingVar']['number-to-get']):'');
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
	}
	if (!function_exists('formatDewey')) {
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
	}
	if (!function_exists('formFilterText')) {
	function formFilterText() {
		$s = "";
		$filters = explode(' ', $GLOBALS['HoldingVar']['filter']);
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
	}
	if (!function_exists('makeGridEntry')) {
	function makeGridEntry($id='')
	{
		if ($id=='') {
			return '';
		} else {
			$book = getBook($id);
			$retval = 			'<form action="editor.php" method="get" id="grid-'.$id.'">';
			$retval = $retval . makeInputFields();
			$retval = $retval . '<input type="hidden" name="bookid" value="'.$id.'" />';
			$retval = $retval .	'<div class="grid-book" onclick="openEditor(\'grid-'.$id.'\')">';
			$retval = $retval . 	'<div class="book-image">';
			$retval = $retval . 		'<img src="'.($book['ImageURL']==''?'':$book['ImageURL']).'" alt="'.$book['Title'].'"></img>';
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
	}
	if (!function_exists('getBook')) {
	function getBook($id)
	{
		$conn = getBookConnection();
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
	}
	if (!function_exists('ordinalEnding')) {
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
	}
	if (!function_exists('makeAuthorBox')) {
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
	}
	if (!function_exists('getAuthors')) {
	function getAuthors($bookId) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('compareAuthors')) {
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
	}
	if (!function_exists('getGenre')) {
	function getGenre($dewey) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('getPublisher')) {
	function getPublisher($publisherID) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('getCity')) {
	function getCity($publisherID) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('getState')) {
	function getState($publisherID) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('getCountry')) {
	function getCountry($publisherID) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('stringLocation')) {
	function stringLocation($city='',$state='',$country='') {
		if ($city==''&&$state==''&&$country=='') {
			return '';
		}
		$city = $city.', ';
		$state = $state.', ';
		$location = $city.$state.$country;
		return $location;
	}
	}
	if (!function_exists('stringDate')) {
	function stringDate($date) {
		return substr($date, 0, strpos($date, '-'));
	}
	}
	if (!function_exists('stringLanguage')) {
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
	}
	if (!function_exists('stringDimensions')) {
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
	}
	if (!function_exists('getPerson')) {
	function getPerson($personId) {
		if ($personId=='') {
			$personId=-1;
		}
		$conn = getBookConnection();
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
	}
	if (!function_exists('stringPerson')) {
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
	}
	if (!function_exists('getStatistics')) {
	function getStatistics()
	{
		$ids = getOwnedIds();
		$idstring = join(',', $ids);
		$conn = getBookConnection();
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
	}
	if (!function_exists('getOwnedIds')) {
	function getOwnedIds() {
		$ids = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('countFormats')) {
	function countFormats($idstring)
	{
		$formats = array();
		$formatList = getFormats();
		foreach ($formatList as $format) {
			$conn = getBookConnection();
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
	}
	if (!function_exists('countRoles')) {
	function countRoles($idstring)
	{
		$roles = array();
		$roleList = getRoles();
		foreach ($roleList as $role) {
			$conn = getBookConnection();
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
	}
	if (!function_exists('countLanguages')) {
	function countLanguages($idstring)
	{
		$languages = array();
		$languageList = getLanguages();
		foreach ($languageList as $language) {
			$conn = getBookConnection();
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
	}
	if (!function_exists('countOnLoan')) {
	function countOnLoan($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countReading')) {
	function countReading($idstring) {
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countShipping')) {
	function countShipping($idstring) {
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countBooks')) {
	function countBooks($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countDeweys')) {
	function countDeweys($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countSeries')) {
	function countSeries($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countRead')) {
	function countRead($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countOwned')) {
	function countOwned($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countReference')) {
	function countReference($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('countPublishers')) {
	function countPublishers($idstring)
	{
		$count = 0;
		$conn = getBookConnection();
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
	}
	if (!function_exists('calculateDimensions')) {
	function calculateDimensions($idstring) {
		$dimensionInfo = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getRoles')) {
	function getRoles()
	{
		$roles = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getLanguages')) {
	function getLanguages()
	{
		$languages = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getSeries')) {
	function getSeries()
	{
		$series = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getFormats')) {
	function getFormats()
	{
		$formats = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getPublishers')) {
	function getPublishers()
	{
		$publishers = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getCities')) {
	function getCities()
	{
		$cities = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getStates')) {
	function getStates()
	{
		$states = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getCountries')) {
	function getCountries()
	{
		$countries = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getDeweys')) {
	function getDeweys()
	{
		$deweys = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('getPersons')) {
	function getPersons() {
		$persons = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('makeStatisticDiv')) {
	function makeStatisticDiv()
	{
		$stats = getStatistics();
		$statsDiv = 			'<div id="statistics">';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">General:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-general" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Books:</span></td><td><span class="stat">'.$stats['Books'].'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Series:</span></td><td><span class="stat">'.$stats['Series'].'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Publishers:</span></td><td><span class="stat">'.$stats['Publishers'].'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Deweys:</span></td><td><span class="stat">'.$stats['Deweys'].'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Read:</span></td><td><span class="stat">'.$stats['Read'].' ('.round($stats['Read']/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Reading:</span></td><td><span class="stat">'.$stats['Reading'].' ('.round($stats['Reading']/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Reference:</span></td><td><span class="stat">'.$stats['Reference'].' ('.round($stats['Reference']/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">On Loan:</span></td><td><span class="stat">'.$stats['OnLoan'].' ('.round($stats['OnLoan']/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
		$statsDiv = $statsDiv .			'<tr><td><span class="stat-label">Shipping:</span></td><td><span class="stat">'.$stats['Shipping'].' ('.round($stats['Shipping']/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Formats:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-formats" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Formats'] as $format => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$format.':</span></td><td><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
			}
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Primary Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-primary" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Languages']['Primary'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$language.':</span></td><td><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
			}
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Secondary Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-secondary" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Languages']['Secondary'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$language.':</span></td><td><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
			}
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Original Languages:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-original" class="substats" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Languages']['Original'] as $language => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$language.':</span></td><td><span class="stat">'.$val.' ('.round($val/$stats['Books']*100, 2).'%)'.'</span></td></tr>';
			}
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Roles:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-roles" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Roles'] as $role => $val) {
			if ($val>0) {
				$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$role.':</span></td><td><span class="stat">'.$val.'</span></td></tr>';
			}
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'<div class="stat-group">';
		$statsDiv = $statsDiv .		'<div><label class="heading">Dimensions:</label></div>';
		$statsDiv = $statsDiv .		'<div id="statistics-dimensions" class="substats">';
		$statsDiv = $statsDiv .		'<table>';
		foreach ($stats['Dimensions'] as $dimension => $val) {
			$statsDiv = $statsDiv .		'<tr><td><span class="stat-label">'.$dimension.':</span></td><td><span class="stat">'.$val.'</span></td></tr>';
		}
		$statsDiv = $statsDiv .		'</table>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .		'</div>';
		$statsDiv = $statsDiv .	'</div>';
		return $statsDiv;
	}
	}
	if (!function_exists('countPages')) {
	function countPages($limit)
	{
		if ($limit<=0) {
			return 0;
		} else {
			$numBooks = count(getBookIds(false));
			return ceil($numBooks/$limit);
		}
	}
	}
	if (!function_exists('setDefaultValues')) {
	function setDefaultValues() {
		if (!isset($GLOBALS['HoldingVar']['sort'])) {
			$GLOBALS['HoldingVar']['sort'] = 'dewey';
		}
		if (!isset($GLOBALS['HoldingVar']['number-to-get'])) {
			$GLOBALS['HoldingVar']['number-to-get'] = 50;
		}
		if (intval($GLOBALS['HoldingVar']['number-to-get'])<=0) {
			$GLOBALS['HoldingVar']['number-to-get'] = 50;
		}
		if (!isset($GLOBALS['HoldingVar']['filter'])) {
			$GLOBALS['HoldingVar']['filter'] = '';
		}
		if (!isset($GLOBALS['HoldingVar']['read'])) {
			$GLOBALS['HoldingVar']['read'] = 'both';
		}
		if (!isset($GLOBALS['HoldingVar']['reference'])) {
			$GLOBALS['HoldingVar']['reference'] = 'both';
		}
		if (!isset($GLOBALS['HoldingVar']['owned'])) {
			$GLOBALS['HoldingVar']['owned'] = 'yes';
		}
		if (!isset($GLOBALS['HoldingVar']['loaned'])) {
			$GLOBALS['HoldingVar']['loaned'] = 'no';
		}
		if (!isset($GLOBALS['HoldingVar']['shipping'])) {
			$GLOBALS['HoldingVar']['shipping'] = 'no';
		}
		if (!isset($GLOBALS['HoldingVar']['reading'])) {
			$GLOBALS['HoldingVar']['reading'] = 'no';
		}
		if (!isset($GLOBALS['HoldingVar']['page'])) {
			$GLOBALS['HoldingVar']['page'] = 1;
		}
		if (intval($GLOBALS['HoldingVar']['page'])<=0) {
			$GLOBALS['HoldingVar']['page'] = 1;
		}
		if (!isset($GLOBALS['HoldingVar']['view'])) {
			$GLOBALS['HoldingVar']['view'] = 'list';
		}
		if (!isset($GLOBALS['HoldingVar']['fromdewey'])) {
			$GLOBALS['HoldingVar']['fromdewey'] = '0';
		}
		if (!isset($GLOBALS['HoldingVar']['todewey'])) {
			$GLOBALS['HoldingVar']['todewey'] = 'FIC';
		}
		if (!isset($GLOBALS['HoldingVar']['currentid'])) {
			$GLOBALS['HoldingVar']['currentid'] = '-1';
		}
		if (!isset($GLOBALS['HoldingVar']['previouspage'])) {
			$GLOBALS['HoldingVar']['previouspage'] = 'index.php';
		}
	}
	}
	if (!function_exists('getUser')) {
	function getUser($username, $password) {
		$conn = getBookConnection();
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
	}
	if (!function_exists('makeLogo')) {
	function makeLogo()
	{
		$retval = 			'<a href="index.php" class="nostyle">';
		$retval = $retval .		'<div id="logo">';
		$retval = $retval .			'<h1>Library Organizer</h1>';
		$retval = $retval .		'</div>';
		$retval = $retval .	'</a>';
		return $retval;
	}
	}
	if (!function_exists('stringSelection')) {
	function stringSelection($arr)
	{
		$retval = '';
		if (count($arr)>0) {
			for ($i=0; $i<count($arr); $i++) {
				$retval = $retval . '<option>'.$arr[$i].'</option>';
			}
		}
		return $retval;
	}
	}
	if (!function_exists('parsePerson')) {
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
	}
	if (!function_exists('saveBook')) {
	function saveBook() {
		if ($book = getBook($GLOBALS['HoldingVar']['bookid'])) {
			$err = updateBook($GLOBALS['HoldingVar']['bookid']);
		} else {
			$err = addBook();
		}
		if ($err) {
			$GLOBALS['alert-message'] = 'Failed to save the book:\n'.str_replace("'", "\'", str_replace("\'", "'", $err));
		}
	}
	}
	if (!function_exists('removeBook')) {
	function removeBook() {
		$err = deleteBook();
		if (!$err) {
			$GLOBALS['alert-message'] = 'Successfully removed the book!';
		} else {
			$GLOBALS['alert-message'] = 'Failed to remove the book:\n'.str_replace(str_replace("'", "\'", $err));
		}
	}
	}
	if (!function_exists('formatDate')) {
	function formatDate($d) {
		return $d==''?'NULL':$d;
	}
	}
	if (!function_exists('addBook')) {
	function addBook() {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}

		$title = str_replace("'", "\'", $GLOBALS['HoldingVar']['title']);
		$subtitle = $GLOBALS['HoldingVar']['subtitle']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['subtitle']);
		$copyright = formatDate($GLOBALS['HoldingVar']['Copyright']);
		$pubid = addOrGetPublisher(str_replace("'", "\'", $GLOBALS['HoldingVar']['Publisher']), str_replace("'", "\'", $GLOBALS['HoldingVar']['City']), str_replace("'", "\'", $GLOBALS['HoldingVar']['State']), str_replace("'", "\'", $GLOBALS['HoldingVar']['Country']));
		$read = (isset($GLOBALS['HoldingVar']['isread']) && $GLOBALS['HoldingVar']['isread']=='1')?'1':'0';
		$reference = (isset($GLOBALS['HoldingVar']['isreference']) && $GLOBALS['HoldingVar']['isreference']=='1')?'1':'0';
		$owned = (isset($GLOBALS['HoldingVar']['isowned']) && $GLOBALS['HoldingVar']['isowned']=='1')?'1':'0';
		$reading = (isset($GLOBALS['HoldingVar']['isreading']) && $GLOBALS['HoldingVar']['isreading']=='1')?'1':'0';
		$shipping = (isset($GLOBALS['HoldingVar']['isshipipng']) && $GLOBALS['HoldingVar']['isshipping']=='1')?'1':'0';
		$isbn = $GLOBALS['HoldingVar']['isbn']==''?'NULL':$GLOBALS['HoldingVar']['isbn'];
		$loaneefirst = $GLOBALS['HoldingVar']['loaneefirst']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['loaneefirst']);
		$loaneelast = $GLOBALS['HoldingVar']['loaneelast']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['loaneelast']);
		$dewey = $GLOBALS['HoldingVar']['dewey']==''?'':formatDewey($GLOBALS['HoldingVar']['dewey']);
		$pages = $GLOBALS['HoldingVar']['pages'];
		$width = $GLOBALS['HoldingVar']['width'];
		$height = $GLOBALS['HoldingVar']['height'];
		$depth = $GLOBALS['HoldingVar']['depth'];
		$weight = $GLOBALS['HoldingVar']['weight'];
		$primary = str_replace("'", "\'", $GLOBALS['HoldingVar']['primary-language']);
		$secondary = str_replace("'", "\'", $GLOBALS['HoldingVar']['secondary-language']);
		$original = str_replace("'", "\'", $GLOBALS['HoldingVar']['original-language']);
		$series = str_replace("'", "\'", $GLOBALS['HoldingVar']['series']);
		$volume = $GLOBALS['HoldingVar']['volume'];
		$format = str_replace("'", "\'", $GLOBALS['HoldingVar']['format']);
		$edition = $GLOBALS['HoldingVar']['edition'];

		$dewey=str_replace("'", "\'", addDewey($dewey));
		$series=str_replace("'", "\'", addSeries($series));
		$format=str_replace("'", "\'", addFormat($format));
		$primary=str_replace("'", "\'", addLanguage($primary));
		$secondary=str_replace("'", "\'", addLanguage($secondary));
		$original=str_replace("'", "\'", addLanguage($original));

		$sql = "INSERT INTO books (Title, Subtitle, Copyright, PublisherID, IsRead, IsReference, IsOwned, IsShipping, IsReading, ISBN, LoaneeFirst, LoaneeLast, Dewey, Pages, Width, Height, Depth, Weight, PrimaryLanguage, SecondaryLanguage, OriginalLanguage, Series, Volume, Format, Edition, ImageURL)".
				"VALUES ('".$title."', ".($subtitle=='NULL'?$subtitle:"'".$subtitle."'").", ".($copyright=='NULL'?$copyright:"'".$copyright."'").", ".$pubid.", ".$read.", ".$reference.", ".$owned.", ".$shipping.", ".$reading.", ".($isbn=='NULL'?$isbn:"'".$isbn."'").", ".($loaneefirst=='NULL'?$loaneefirst:"'".$loaneefirst."'").", ".($loaneelast=='NULL'?$loaneelast:"'".$loaneelast."'").", ".($dewey=='NULL'?$dewey:"'".$dewey."'").", ".$pages.", ".$width.", ".$height.", ".$depth.", ".$weight.", ".($primary=='NULL'?$primary:"'".$primary."'").", ".($secondary=='NULL'?$secondary:"'".$secondary."'").", ".($original=='NULL'?$original:"'".$original."'").", ".($series=='NULL'?$series:"'".$series."'").", ".$volume.", ".($format=='NULL'?$format:"'".$format."'").", ".$edition.", '".$imageurl."')";
		while (strpos($sql, '\\\\')) {
			$sql = str_replace('\\\\', '\\', $sql);
		}
		if ($conn->query($sql) === TRUE) {
			$bookId = $conn->insert_id;
			if (isset($GLOBALS['HoldingVar']['authors'])) {
				foreach ($GLOBALS['HoldingVar']['authors'] as $author) {
					addWrittenBy($bookId, $author['firstname'], str_replace(' ', ';', $author['middlenames']), $author['lastname'], $author['role']);
				}
			}
			$GLOBALS['HoldingVar']['bookid']=$bookId;
			$sql = "UPDATE books SET ImageURL='res/bookimages/".$bookId.".jpg' WHERE BookID=".$bookId;
			if ($conn->query($sql) !== TRUE) {
				return "Error: " . $sql . "<br>" . $conn->error;
			}
			if ($GLOBALS['HoldingVar']['imageurl'] != '') {
				$out = 'res/bookimages/'.$bookId.'.jpg';
				$contents = file_get_contents($GLOBALS['HoldingVar']['imageurl']);
				if ($contents) {
					$byteCount = file_put_contents($out, $contents);
					if (!$byteCount) {
						return "Error: " . "Unable to get image";
					}
					$color = getImageColor($out);
					$sql = 'UPDATE books SET SpineColor='.($color=='null'?'null':'"'.$color.'"').' WHERE BookID='.$bookId.';';
					$conn->query($sql);					
				} else {
					return "Error: " . "Unable to get image";
				}
			}
		} else {
			return "Error: " . $sql . "<br>" . $conn->error;
		}
		return null;
	}
	}
	if (!function_exists('addAuthor')) {
	function addAuthor($author, $bookid) {
		$author = explode(' - ', $author);
		$person = parsePerson($author[0]);
		$role = $author[1];
		addWrittenBy($bookid, $person[0], $person[1], $person[2], $role);
	}
	}
	if (!function_exists('addOrGetPublisher')) {
	function addOrGetPublisher($publisher, $city, $state, $country) {
		if ($publisher=='' && $city=='' && $state=='' && $country=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addOrGetPerson')) {
	function addOrGetPerson($first, $middle, $last) {
		if ($first=='' && $middle=='' && $last=='') {
			return 'NULL';
		} else {
			$middles = explode(';', $middle);
			for ($i=1; $i<count($middles); $i++) {
				if ($middles[$i]=='') {
					unset($middles[$i]);
				}
			}
			$m = '';
			foreach ($middles as $key => $value) {
				$m = $m.$key.'\t'.$value.'\n';
			}
			$middle = join(';', array_values($middles));
			$conn = getBookConnection();
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
	}
	if (!function_exists('addDewey')) {
	function addDewey($d) {
		if ($d=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addFormat')) {
	function addFormat($f) {
		if ($f=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addLanguage')) {
	function addLanguage($l) {
		if ($l=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addRole')) {
	function addRole($r) {
		if ($r=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addSeries')) {
	function addSeries($s) {
		if ($s=='') {
			return 'NULL';
		} else {
			$conn = getBookConnection();
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
	}
	if (!function_exists('addWrittenBy')) {
	function addWrittenBy($bookid, $first, $middle, $last, $role) {
		$pid = addOrGetPerson($first, $middle, $last);
		addRole($role);
		$conn = getBookConnection();
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
	}
	if (!function_exists('removeAllWrittenBy')) {
	function removeAllWrittenBy($bookid) {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}
		$sql = "DELETE FROM written_by WHERE BookId='".$bookid."'";
		$conn->query($sql);
	}
	}
	if (!function_exists('updateBook')) {
	function updateBook($id) {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			return die("Connection failed: " . $conn->connect_error);
		}

		removeAllWrittenBy($id);

		if (isset($GLOBALS['HoldingVar']['authors'])) {
			foreach ($GLOBALS['HoldingVar']['authors'] as $author) {
				addWrittenBy($id, $author['firstname'], str_replace(' ', ';', $author['middlenames']), $author['lastname'], $author['role']);
			}
		}

		$title = str_replace("'", "\'", $GLOBALS['HoldingVar']['title']);
		$subtitle = $GLOBALS['HoldingVar']['subtitle']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['subtitle']);
		$copyright = formatDate($GLOBALS['HoldingVar']['Copyright']);
		$pubid = addOrGetPublisher(str_replace("'", "\'", $GLOBALS['HoldingVar']['Publisher']), str_replace("'", "\'", $GLOBALS['HoldingVar']['City']), str_replace("'", "\'", $GLOBALS['HoldingVar']['State']), str_replace("'", "\'", $GLOBALS['HoldingVar']['Country']));
		$read = (isset($GLOBALS['HoldingVar']['isread']) && $GLOBALS['HoldingVar']['isread']=='1')?'1':'0';
		$reference = (isset($GLOBALS['HoldingVar']['isreference']) && $GLOBALS['HoldingVar']['isreference']=='1')?'1':'0';
		$owned = (isset($GLOBALS['HoldingVar']['isowned']) && $GLOBALS['HoldingVar']['isowned']=='1')?'1':'0';
		$reading = (isset($GLOBALS['HoldingVar']['isreading']) && $GLOBALS['HoldingVar']['isreading']=='1')?'1':'0';
		$shipping = (isset($GLOBALS['HoldingVar']['isshipping']) && $GLOBALS['HoldingVar']['isshipping']=='1')?'1':'0';
		$isbn = $GLOBALS['HoldingVar']['isbn']==''?'NULL':$GLOBALS['HoldingVar']['isbn'];
		$loaneefirst = $GLOBALS['HoldingVar']['loaneefirst']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['loaneefirst']);
		$loaneelast = $GLOBALS['HoldingVar']['loaneelast']==''?'NULL':str_replace("'", "\'", $GLOBALS['HoldingVar']['loaneelast']);
		$dewey = $GLOBALS['HoldingVar']['dewey']==''?'':formatDewey($GLOBALS['HoldingVar']['dewey']);
		$pages = $GLOBALS['HoldingVar']['pages'];
		$width = $GLOBALS['HoldingVar']['width'];
		$height = $GLOBALS['HoldingVar']['height'];
		$depth = $GLOBALS['HoldingVar']['depth'];
		$weight = $GLOBALS['HoldingVar']['weight'];
		$primary = str_replace("'", "\'", $GLOBALS['HoldingVar']['primary-language']);
		$secondary = str_replace("'", "\'", $GLOBALS['HoldingVar']['secondary-language']);
		$original = str_replace("'", "\'", $GLOBALS['HoldingVar']['original-language']);
		$series = str_replace("'", "\'", $GLOBALS['HoldingVar']['series']);
		$volume = $GLOBALS['HoldingVar']['volume'];
		$format = str_replace("'", "\'", $GLOBALS['HoldingVar']['format']);
		$edition = $GLOBALS['HoldingVar']['edition'];

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
				", LoaneeFirst=".($loaneefirst=='NULL'?$loaneefirst:"'".$loaneefirst."'").
				", LoaneeLast=".($loaneelast=='NULL'?$loaneelast:"'".$loaneelast."'").
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
				", ImageURL='res/bookimages/".$id.".jpg'".
				" WHERE BookId=".$id;
		while (strpos($sql, '\\\\')) {
			$sql = str_replace('\\\\', '\\', $sql);
		}
		if ($conn->query($sql) === TRUE) {
			if ($GLOBALS['HoldingVar']['imageurl'] != '') {
				$out = 'res/bookimages/'.$id.'.jpg';
				$contents = file_get_contents($GLOBALS['HoldingVar']['imageurl']);
				if ($contents) {
					$byteCount = file_put_contents($out, $contents);
					if (!$byteCount) {
						return "Error: " . "Unable to get image";
					}
					$color = getImageColor($out);
					$sql = 'UPDATE books SET SpineColor='.($color=='null'?'null':'"'.$color.'"').' WHERE BookID='.$id.';';
					$conn->query($sql);					
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
	}
	if (!function_exists('deleteBook')) {
	function deleteBook() {
		if ($GLOBALS['HoldingVar']['bookid']) {
			$conn = getBookConnection();
			if ($conn->connect_errno>0) {
				return die("Connection failed: " . $conn->connect_error);
			}
			$sql = 'DELETE FROM books WHERE BookID='.$GLOBALS['HoldingVar']['bookid'];
			if ($conn->query($sql) === TRUE) {
				return null;
			} else {
				return "Error: " . $sql . "<br>" . $conn->error;
			}
			return null;
		}
		return null;
	}
	}
	if (!function_exists('fillShelf')) {
	function fillShelf() {
		if (isset($GLOBALS['HoldingVar']['reloadShelf']) && $GLOBALS['HoldingVar']['reloadShelf']=='true') {
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
				$retval = $retval . 		'subtitle: "'.str_replace('"', ",", $book['Subtitle']).'",';
				$retval = $retval . 		'width: '.(intval($book['Width'])<=0?'25':$book['Width']).',';
				$retval = $retval . 		'height: '.(intval($book['Height'])<=0?'200':$book['Height']).',';
				$retval = $retval . 		'image: "'.str_replace('"', ",", $book['ImageURL']).'",';
				$retval = $retval . 		'color: "'.str_replace('"', ",", $book['SpineColor']).'",';
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
	}
	if (!function_exists('stringShelves')) {
	function stringShelves() {
		$shelves = getShelves();
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
		return $retval;
	}
	}
	if (!function_exists('getShelves')) {
	function getShelves() {
		$shelves = array();
		$conn = getBookConnection();
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
	}
	if (!function_exists('makeEditorForm')) {
	function makeEditorForm() {
		$retval = 			'<form action="editor.php" method="post" id="editorForm">';
		$retval = $retval . makeInputFields();
		$retval = $retval . '</form>';
		return $retval;
	}
	}
	if (!function_exists('makeBookGridView')) {
	function makeBookGridView() {
		$grid = '';
		$bookIds = getBookIds(true);
		foreach ($bookIds as $id) {
			$grid = $grid . makeGridViewEntry($id);
		}
		return $grid;
	}
	}
	if (!function_exists('makeGridViewEntry')) {
	function makeGridViewEntry($id) {
		if ($id=='') {
			return '';
		} else {
			$book = getBook($id);
			$retval = 			'<form action="editor.php" method="get" id="grid-view-'.$id.'">';
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
	}
	if (!function_exists('writeToFile')) {
	function writeToFile($file, $data) {
		$f = fopen($file, "w") or die("Unable to open file ".$file);
		fwrite($f, $data);
		fclose($f);
	}
	}
	if (!function_exists('readFromFile')) {
	function readFromFile($file) {
		return file_get_contents($file);
	}
	}
	if (!function_exists('makeInputFields')) {
	function makeInputFields() {
		$retval = 				'<input type="hidden" name="sort" value="'.$GLOBALS['HoldingVar']['sort'].'">';
		$retval = $retval . 	'<input type="hidden" name="number-to-get" value="'.$GLOBALS['HoldingVar']['number-to-get'].'">';
		$retval = $retval . 	'<input type="hidden" name="filter" value="'.$GLOBALS['HoldingVar']['filter'].'">';
		$retval = $retval . 	'<input type="hidden" name="read" value="'.$GLOBALS['HoldingVar']['read'].'">';
		$retval = $retval . 	'<input type="hidden" name="reference" value="'.$GLOBALS['HoldingVar']['reference'].'">';
		$retval = $retval . 	'<input type="hidden" name="owned" value="'.$GLOBALS['HoldingVar']['owned'].'">';
		$retval = $retval . 	'<input type="hidden" name="shipping" value="'.$GLOBALS['HoldingVar']['shipping'].'">';
		$retval = $retval . 	'<input type="hidden" name="reading" value="'.$GLOBALS['HoldingVar']['reading'].'">';
		$retval = $retval . 	'<input type="hidden" name="page" value="'.$GLOBALS['HoldingVar']['page'].'">';
		$retval = $retval . 	'<input type="hidden" name="view" value="'.$GLOBALS['HoldingVar']['view'].'">';
		$retval = $retval . 	'<input type="hidden" name="loaned" value="'.$GLOBALS['HoldingVar']['loaned'].'">';
		$retval = $retval . 	'<input type="hidden" name="fromdewey" value="'.$GLOBALS['HoldingVar']['fromdewey'].'">';
		$retval = $retval . 	'<input type="hidden" name="todewey" value="'.$GLOBALS['HoldingVar']['todewey'].'">';
		$retval = $retval . 	'<input type="hidden" name="currentid" value="'.$GLOBALS['HoldingVar']['currentid'].'">';
		return $retval;
	}
	}
	if (!function_exists('exportBooks')) {
	function exportBooks() {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT * FROM books";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$num_fields = $result->field_count;
		$headers = array();
		for ($i=0; $i < $num_fields; $i++) {
			$headers[] = $result->fetch_field()->name;
		}
		$fp = fopen('php://output', 'w');
		if ($fp && $result) {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="export.csv"');
			header('Pragma: no-cache');
			header('Expires: 0');
			fputcsv($fp, $headers);
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				fputcsv($fp, array_values($row));
			}
		}
	}
	}
	if (!function_exists('exportAuthors')) {
	function exportAuthors() {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT BookID, FirstName, MiddleNames, LastName, Role from written_by JOIN persons on written_by.AuthorID=persons.PersonID";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		$num_fields = $result->field_count;
		$headers = array();
		for ($i=0; $i < $num_fields; $i++) {
			$headers[] = $result->fetch_field()->name;
		}
		$fp = fopen('php://output', 'w');
		if ($fp && $result) {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="export.csv"');
			header('Pragma: no-cache');
			header('Expires: 0');
			fputcsv($fp, $headers);
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				fputcsv($fp, array_values($row));
			}
		}
	}
	}
	if (!function_exists('makeBookList')) {
	function makeBookList() {
		$list = '<table>';
		$bookIds = getBookIds(true);
		foreach ($bookIds as $id) {
			$list = $list . makeListEntry($id);
		}
		return $list.'</table>';
	}
	}
	if (!function_exists('makeListEntry')) {
	function makeListEntry($id='') {
		if ($id=='') {
			return '';
		} else {
			$book = getBook($id);
			$retval = '<form action="editor.php" method="get" id="list-'.$id.'">';
			$retval = $retval . makeInputFields();
			$retval = $retval . '<input type="hidden" name="bookid" value="'.$id.'" />';
			$retval = $retval . '<tr class="list-row" onclick="openEditor(\'list-'.$id.'\')">';
			$retval = $retval . 	'<td class="book-image">';
			$retval = $retval . 		'<img src="'.($book['ImageURL']==''?'':$book['ImageURL']).'" alt="'.$book['Title'].'"></img>';
			$retval = $retval .		'</td> ';
			$retval = $retval .		'<td class="book-title">'.$book['Title'].'</td>';
			$retval = $retval .		'<td class="book-subtitle">'.$book['Subtitle'].'</td>';
			$retval = $retval . '</tr>';
			$retval = $retval . '</form>';
			return $retval;
		}
	}
	}
	// if (!function_exists('getImageColor')) {
	// function getImageColor($url) {
	// 	if (file_exists($url)) {
	// 		$img = ImageCreateFromJpeg($url);
	// 		$width = imagesx($img);
	// 		$height = imagesy($img);
	// 		$n = $width*$height;
	// 		$histogram = array();
	// 		for ($i=0; $i<$width; $i++) {
	// 			for ($j=0; $j<$height; $j++) {
	// 				$rgb = ImageColorAt($img, $i, $j);
	// 				$r = ($rgb>>16)&0xFF;
	// 				$g = ($rgb>>8)&0xFF;
	// 				$b = $rgb&0xFF;
	// 				$hsl = rgbToHsl(array($r, $g, $b));
	// 				$histogram[$hsl[0]] += $hsl[0]/$n;
	// 			}
	// 		}
	// 		$max = $histogram[0];
	// 		for ($i=0; $i<count($histogram); $i++) {
	// 			if ($histogram[$i] > $max) {
	// 				$max = $histogram[$i];
	// 			}
	// 		}
	// 		return "hsl(".$max.", 100%, 50%)";
	// 		// $rgb = hslToRgb(array($max, 1, 0.5));
	// 		// return rgbToHex(mixColor($rgb, array(255, 255, 255)));
	// 		// return rgbToHex($rgb);
	// 	} else {
	// 		return "null";
	// 	}
	// }
	// }
	// if (!function_exists('rgbToHsl')) {
	// function rgbToHsl($rgb)
	// {
	// 	$r = $rgb[0];
	// 	$g = $rgb[1];
	// 	$b = $rgb[2];
	// 	$r /= 255;
	// 	$g /= 255;
	// 	$b /= 255;
	// 	$max = max( $r, $g, $b );
	// 	$min = min( $r, $g, $b );
	// 	$h;
	// 	$s;
	// 	$l = ( $max + $min ) / 2;
	// 	$d = $max - $min;
	// 	if( $d == 0 ){
	// 		$h = $s = 0; // achromatic
	// 	} else {
	// 		$s = $d / ( 1 - abs( 2 * $l - 1 ) );
	// 		switch( $max ){
	// 		case $r:
	// 			$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
	// 				if ($b > $g) {
	// 				$h += 360;
	// 			}
	// 			break;
	// 		case $g: 
	// 			$h = 60 * ( ( $b - $r ) / $d + 2 ); 
	// 			break;
	// 		case $b: 
	// 			$h = 60 * ( ( $r - $g ) / $d + 4 ); 
	// 			break;
	// 		}								
	// 	}
	// 	return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
	// }
	// }
	// if (!function_exists('hslToRgb')) {
	// function hslToRgb($hsl)
	// {
	// 	$h = $hsl[0];
	// 	$s = $hsl[1];
	// 	$l = $hsl[2];
	// 	$r; 
	// 	$g; 
	// 	$b;
	// 	$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
	// 	$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
	// 	$m = $l - ( $c / 2 );
	// 	if ( $h < 60 ) {
	// 		$r = $c;
	// 		$g = $x;
	// 		$b = 0;
	// 	} else if ( $h < 120 ) {
	// 		$r = $x;
	// 		$g = $c;
	// 		$b = 0;			
	// 	} else if ( $h < 180 ) {
	// 		$r = 0;
	// 		$g = $c;
	// 		$b = $x;					
	// 	} else if ( $h < 240 ) {
	// 		$r = 0;
	// 		$g = $x;
	// 		$b = $c;
	// 	} else if ( $h < 300 ) {
	// 		$r = $x;
	// 		$g = 0;
	// 		$b = $c;
	// 	} else {
	// 		$r = $c;
	// 		$g = 0;
	// 		$b = $x;
	// 	}
	// 	$r = ( $r + $m ) * 255;
	// 	$g = ( $g + $m ) * 255;
	// 	$b = ( $b + $m  ) * 255;
	// 	return array( floor( $r ), floor( $g ), floor( $b ) );
	// }
	// }
	//if (!function_exists('mixColor')) {
	//function mixColor($rgb1, $rgb2) {
	// 	return array(($rgb1[0]+$rgb2[0])/2, ($rgb1[1]+$rgb2[1])/2, ($rgb1[2]+$rgb2[2])/2);
	// }
	// }
	//if (!function_exists('rgbToHex')) {
	//function rgbToHex($rgb)
	// {
	// 	return '#' . sprintf('%02x', $rgb[0]) . sprintf('%02x', $rgb[1]) . sprintf('%02x', $rgb[2]);
	// }
	// }
	//if (!function_exists('setAllImageColors')) {
	//function setAllImageColors() {
	// 	$conn = getBookConnection();
	// 	if ($conn->connect_errno>0) {
	// 		die("Connection failed: " . $conn->connect_error);
	// 	}
	// 	$sql = "SELECT BookID, ImageURL from books";
	// 	$result = $conn->query($sql);
	// 	if (!$result) {
	// 		die("Query failed: " . $conn->error);
	// 	}
	// 	while ($row = $result->fetch_assoc()) {
	// 		$id = $row['BookID'];
	// 		$url = $row['ImageURL'];
	// 		$color = getImageColor($url);
	// 		// echo "<img src='".$url."' width='50' alt='".$row['Title']."' /><div style='display:block; width:50; height:100; background-color=".$color." /></br>";
	// 		echo "<div style='margin: 25px; outline: 1px solid black; background-color: ".$color.";'><img width=50 src='".$url."' alt='".$row['Title']."' /></div></br>";
	// 		$sql = 'UPDATE books SET SpineColor='.($color=='null'?'null':'"'.$color.'"').' WHERE BookID='.$id.';';
	// 		$conn->query($sql);
	// 	}
	// }
	//}
	if (!function_exists('getImageColor')) {
	function getImageColor($url) {
		if (file_exists($url)) {
			$delta = 24;
			$reduce_brightness = true;
			$reduce_gradients = true;
			$num_results = 1;
			$ex=new GetMostCommonColors();
			$colors=$ex->Get_Color($url, $num_results, $reduce_brightness, $reduce_gradients, $delta);
			foreach ($colors as $hex => $index) {
				return "#".$hex;
			}
		} else {
			return "null";
		}
	}
	}
	if (!function_exists('setAllImageColors')) {
	function setAllImageColors() {
		$conn = getBookConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT BookID, ImageURL from books";
		$result = $conn->query($sql);
		if (!$result) {
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			set_time_limit(10);
			$id = $row['BookID'];
			$url = $row['ImageURL'];
			$color = getImageColor($url);
			// echo "<img src='".$url."' width='50' alt='".$row['Title']."' /><div style='display:block; width:50; height:100; background-color=".$color." /></br>";
			echo $color."<div style='margin: 25px; outline: 1px solid black; background-color: ".$color.";'><img width=50 src='".$url."' alt='".$row['Title']."' /></div></br>";
			$sql = 'UPDATE books SET SpineColor='.($color=='null'?'null':'"'.$color.'"').' WHERE BookID='.$id.';';
			$conn->query($sql);
		}
	}
	}
	if (!function_exists('importBooks')) {
	function importBooks($contents) {
		$rows = explode("\r\n", $contents);
		$headers = explode("\t", $rows[0]);
		$data = array_splice($rows, 1);
		for ($i=0; $i < count($data); $i++) { 
			$data[$i] = explode("\t", $data[$i]);
		}
		for ($i=0; $i < count($data); $i++) { 
			for ($j=0; $j < count($headers); $j++) {
				$value = $data[$i][$j];
				switch (strtolower($headers[$j])) {
					case 'title':
						$GLOBALS['HoldingVar']['title'] = $value;
						break;
						case 'subtitle':
						$GLOBALS['HoldingVar']['subtitle'] = $value;
						break;
						case 'copyright':
						$GLOBALS['HoldingVar']['Copyright'] = $value;
						break;
						case 'publisher':
						$GLOBALS['HoldingVar']['Publisher'] = $value;
						break;
						case 'city':
						$GLOBALS['HoldingVar']['City'] = $value;
						break;
						case 'state':
						$GLOBALS['HoldingVar']['State'] = $value;
						break;
						case 'country':
						$GLOBALS['HoldingVar']['Country'] = $value;
						break;
						case 'isread':
						$GLOBALS['HoldingVar']['isread'] = $value;
						break;
						case 'isreference':
						$GLOBALS['HoldingVar']['isreference'] = $value;
						break;
						case 'isowned':
						$GLOBALS['HoldingVar']['isowned'] = $value;
						break;
						case 'isreading':
						$GLOBALS['HoldingVar']['isreading'] = $value;
						break;
						case 'isshipping':
						$GLOBALS['HoldingVar']['isshipping'] = $value;
						break;
						case 'isbn':
						$GLOBALS['HoldingVar']['isbn'] = $value;
						break;
						case 'loaneefirst':
						$GLOBALS['HoldingVar']['loaneefirst'] = $value;
						break;
						case 'loaneelast':
						$GLOBALS['HoldingVar']['loaneelast'] = $value;
						break;
						case 'dewey':
						$GLOBALS['HoldingVar']['dewey'] = $value;
						break;
						case 'pages':
						$GLOBALS['HoldingVar']['pages'] = $value;
						break;
						case 'width':
						$GLOBALS['HoldingVar']['width'] = $value;
						break;
						case 'height':
						$GLOBALS['HoldingVar']['height'] = $value;
						break;
						case 'depth':
						$GLOBALS['HoldingVar']['depth'] = $value;
						break;
						case 'weight':
						$GLOBALS['HoldingVar']['weight'] = $value;
						break;
						case 'primarylanguage':
						$GLOBALS['HoldingVar']['primary-language'] = $value;
						break;
						case 'secondarylanguage':
						$GLOBALS['HoldingVar']['secondarylanguage'] = $value;
						break;
						case 'originallanguage':
						$GLOBALS['HoldingVar']['original-language'] = $value;
						break;
						case 'series':
						$GLOBALS['HoldingVar']['series'] = $value;
						break;
						case 'volume':
						$GLOBALS['HoldingVar']['volume'] = $value;
						break;
						case 'format':
						$GLOBALS['HoldingVar']['format'] = $value;
						break;
						case 'edition':
						$GLOBALS['HoldingVar']['edition'] = $value;
						break;
						case 'imageurl':
						$GLOBALS['HoldingVar']['imageurl'] = $value;
						break;
						case 'authors':
						$GLOBALS['HoldingVar']['authors'] = array();
						foreach (explode(';', $value) as $author) {
							if (strpos($author, ':') !== false) {
								return 'Ill formated author string on book '.$i.'. Stopping. Finished all books before this.';
							}
							$role = substr($author, strpos($author, ':')+1);
							$name = substr($author, 0, strpos($author, ':'));
							$fn = '';
							$mn = '';
							$ln = '';
							if (strpos($name, ',')) {
								$ln = substr($name, 0, strpos($name, ','));
								$name = explode(' ', substr($name, strpos($name, ',')+1));
								if ($name[0] == '') {
									$fn = $name[1];
									if (count($name) > 2) {
										$mn = implode(' ', array_splice($name, 2));
									}
								} else {
									$fn = $name[0];
									if (count($name) > 1) {
										$mn = implode(' ', array_splice($name, 1));
									}
								}
							} else {
								$ln = $name;
							}
							$a = $arrayName = array('firstname' => $fn, 'middlenames' => $mn, 'lastname' => $ln, 'role' => $role);
							array_push($GLOBALS['HoldingVar']['authors'], $a);
						}
						break;
					default:
					foreach ($rows as $d) {
			// foreach ($d as $v) {
			// 	$ds = $ds.$v.'\t';
			// }
			$ds = $d.'\n';
		}
						return 'Unknown header value '.$headers[$j].'. Stopping before adding book '.$i.$ds;
						break;
				}
			}
			if ($err = addBook() !== NULL) {
				return 'Unable to add book '.$i.'. Added all up to this. Error: '.$err;
			}
		}
		$ds = '';
		foreach ($headers as $d) {
			// foreach ($d as $v) {
			// 	$ds = $ds.$v.'\t';
			// }
			$ds = $d.'\n';
		}
		return "Books Successfully added: ".$ds;
	}
	}
	function loadDeweys() {
		return file_get_contents('deweys.txt');
	}
?>