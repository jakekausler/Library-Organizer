<?php
	$method = $_SERVER['REQUEST_METHOD'];
	$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
	$input = json_decode(file_get_contents('php://input'), true);

	$shelfdict = json_decode(file_get_contents('shelfdict.json'), true);

	$searchDistance = 2;
	$percentToFind = 0.66;

	switch ($method) {
		case 'GET':
			$searchMaterial = split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(".", " ", str_replace("/", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", " ", str_replace('"', '', str_replace("'", "", $request[0])))))))))));
			$books = [];
			foreach ($shelfdict as $i => $book) {
				if (searchMatches($book['title'], $book['subtitle'], $searchMaterial, $searchDistance, $percentToFind)) {
					$b = [];
					$b['title'] = $book['title'];
					$b['subtitle'] = $book['subtitle'];
					$b['book'] = $book['booknum'];
					$b['shelf'] = $book['shelfnum'];
					$b['case'] = $book['casenum'];
					array_push($books, $b);
				}
			}
			echo json_encode($books);
	}

	function searchMatches($title, $subtitle, $material, $distance, $percent) {
		$titlearray = split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(".", " ", str_replace("/", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", " ", str_replace('"', '', str_replace("'", "", $title)))))))))));
		$subtitlearray = split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(".", " ", str_replace("/", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", " ", str_replace('"', '', str_replace("'", "", $subtitle)))))))))));
		$found = [];
		foreach ($material as $key => $term) {
			array_push($found, false);
			foreach ($titlearray as $k => $t) {
				if (levenshtein($t, $term) <= $distance) {
					$found[$key] = true;
					break;
				}
			}
			foreach ($subtitlearray as $k => $t) {
				if (levenshtein($t, $term) <= $distance) {
					$found[$key] = true;
					break;
				}
			}
		}
		$count = 0;
		foreach ($found as $key => $value) {
			if ($value) {
				$count++;
			}
		}
		return $count/count($found) > $percent;
	}
?>