<?php
	$method = $_SERVER['REQUEST_METHOD'];
	$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
	$input = json_decode(file_get_contents('php://input'), true);

	$shelfdict = json_decode(file_get_contents('shelfdict.json'), true);

	$searchDistance = 10;
	$percentToFind = 0.8;
	$rep = 20;
	$ins = 15;
	$del = 10;

	$stopwords = array("a","about","above","above","across","after","afterwards","again","against","all","almost","alone","along","already","also","although","always","am","among","amongst","amoungst","amount","an","and","another","any","anyhow","anyone","anything","anyway","anywhere","are","around","as","at","back","be","became","because","become","becomes","becoming","been","before","beforehand","behind","being","below","beside","besides","between","beyond","both","bottom","but","by","call","can","cannot","cant","co","con","could","couldnt","cry","de","describe","detail","do","done","down","due","during","each","eg","either","else","elsewhere","empty","enough","etc","even","ever","every","everyone","everything","everywhere","except","few","for","from","front","full","further","get","had","has","hasnt","have","he","hence","her","here","hereafter","hereby","herein","hereupon","hers","herself","him","himself","his","how","however","ie","if","in","inc","indeed","interest","into","is","it","its","itself","keep","last","latter","latterly","least","less","ltd","made","many","may","me","meanwhile","might","mill","mine","more","moreover","most","mostly","move","much","must","my","myself","name","namely","neither","never","nevertheless","next","no","nobody","none","noone","nor","not","nothing","now","nowhere","of","off","often","on","once","one","only","onto","or","other","others","otherwise","our","ours","ourselves","out","over","own","part","per","perhaps","please","put","rather","re","same","see","seem","seemed","seeming","seems","serious","several","she","should","show","side","since","sincere","so","some","somehow","someone","something","sometime","sometimes","somewhere","still","such","than","that","the","their","them","themselves","then","thence","there","thereafter","thereby","therefore","therein","thereupon","these","they","thin","this","those","though","through","throughout","thru","thus","to","together","too","top","toward","towards","un","under","until","up","upon","us","very","via","was","we","well","were","what","whatever","when","whence","whenever","where","whereafter","whereas","whereby","wherein","whereupon","wherever","whether","which","while","whither","who","whoever","whole","whom","whose","why","will","with","within","without","would","yet","you","your","yours","yourself","yourselves","the");
	$smallNumbers = array("zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen");
	$tens = array("", "", "twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty", "ninety");
	$scaleNumbers = array("", "thousand", "million", "billion");
	$ordinalEndings = array(
		'one' => 'first',
		'two' => 'second',
		'three' => 'third',
		'four' => 'fourth',
		'five' => 'fifth',
		'six' => 'sixth',
		'en' => 'enth',
		'eight' => 'eighth',
		'nine' => 'ninth',
		'twelve' => 'twelfth',
		'ty' => 'tieth',
		'hundred' => 'hundredth',
		'thousand' => 'thousandth',
		'ion' => 'ionth'
	);

	switch ($method) {
		case 'GET':
			$searchMaterial = split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(";", " ", str_replace(".", " ", str_replace("\\", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", "", str_replace(", ", " ", str_replace('"', '', str_replace("'", "", $request[0])))))))))))));
			if (isset($request[1]) && is_numeric($request[1])) {
				$searchDistance = floatval($request[1]);
			}
			if (isset($request[2]) && is_numeric($request[2])) {
				$percentToFind = floatval($request[2]);
			}
			if (isset($request[3]) && is_numeric($request[3])) {
				$rep = floatval($request[3]);
			}
			if (isset($request[4]) && is_numeric($request[4])) {
				$ins = floatval($request[4]);
			}
			if (isset($request[5]) && is_numeric($request[5])) {
				$del = floatval($request[5]);
			}
			$books = [];
			$matches = [];
			$notmatches = [];
			foreach ($shelfdict as $i => $book) {
				if (searchMatches($book['title'], $book['subtitle'], $searchMaterial, $searchDistance, $percentToFind, $stopwords, $rep, $ins, $del, $matches, $notmatches)) {
					$b = [];
					$b['title'] = $book['title'];
					$b['subtitle'] = $book['subtitle'];
					$b['book'] = $book['booknum'];
					$b['shelf'] = $book['shelfnum'];
					$b['case'] = $book['casenum'];
					array_push($books, $b);
				}
			}
			// asort($matches);
			// asort($notmatches);
			// echo 'Matches:</br>';
			// foreach ($matches as $key => $value) {
			// 	echo $key.': '.$value.'</br>';
			// }
			// echo 'Not Matches:</br>';
			// foreach ($notmatches as $key => $value) {
			// 	echo $key.': '.$value.'</br>';
			// }
			echo json_encode($books);
	}

	function searchMatches($title, $subtitle, $material, $distance, $percent, $stopwords, $rep, $ins, $del, &$matches, &$notmatches) {
		$titlearray = convert_numbers(split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(";", " ", str_replace(":", " ", str_replace(".", " ", str_replace("/", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", "", str_replace(", ", " ", str_replace('"', '', str_replace("'", "", $title)))))))))))))));
		$subtitlearray = convert_numbers(split(" ", strtolower(str_replace("  ", " ", str_replace("  ", " ", str_replace(";", " ", str_replace(":", " ", str_replace(".", " ", str_replace("/", " ", str_replace("&", "and", str_replace("-", " ", str_replace(",", "", str_replace(", ", " ", str_replace('"', '', str_replace("'", "", $subtitle)))))))))))))));
		$found = [];
		foreach ($material as $key => $term) {
			if (!in_array($term, $stopwords)) {
				array_push($found, false);
				foreach ($titlearray as $k => $t) {
					if (!in_array($t, $stopwords)) {
						if (levenshtein($t, $term, $rep, $ins, $del) <= $distance) {
							$found[$key] = true;
							// $matches[$term.'-'.$t] = levenshtein($term, $t, $rep, $ins, $del);
							break;
						} else {
							// $notmatches[$term.'-'.$t] = levenshtein($term, $t, $rep, $ins, $del);
						}
					}
				}
				foreach ($subtitlearray as $k => $t) {
					if (!in_array($t, $stopwords)) {
						if (levenshtein($t, $term, $rep, $ins, $del) <= $distance) {
							$found[$key] = true;
							// $matches[$term.'-'.$t] = levenshtein($term, $t, $rep, $ins, $del);
							break;
						} else {
							// $notmatches[$term.'-'.$t] = levenshtein($term, $t, $rep, $ins, $del);
						}
					}
				}
			}
		}
		$count = 0;
		foreach ($found as $key => $value) {
			if ($value) {
				$count++;
			}
		}
		return count($found) > 0 ? $count/count($found) > $percent : false;
	}

	function convert_numbers($array) {
		$retval = [];
		foreach ($array as $key => $value) {
			if (is_numeric($value)) {
				$retval = array_merge($retval, convert_cardinal_to_words(intval($value)));
			} else if (strlen($value) > 2 && (endsWith($value, 'st') || endsWith($value, 'nd') || endsWith($value, 'rd') || endsWith($value, 'th')) && is_numeric(substr($value, 0, strlen($value)-2))) {
				$retval = array_merge($retval, convert_ordinal_to_words(intval(substr($value, 0, strlen($value)-2))));
			} else {
				array_push($retval, $value);
			}
		}
		return $retval;
	}

	function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}

	function convert_ordinal_to_words($num) {
		global $ordinalEndings;
		$card = convert_cardinal_to_words($num);
		foreach ($ordinalEndings as $ending => $value) {
			if (endsWith($card[count($card)-1], $ending)) {
				$card[count($card)-1] = preg_replace('/'.$ending.'$/', $value, $card[count($card)-1]);
				break;
			}
		}
		return $card;
	}

	function convert_cardinal_to_words($num) {
		global $smallNumbers, $tens, $scaleNumbers;
		if ($num == 0) {
			return array($smallNumbers[0]);
		}
		if ($num > 1200 && $num < 2000) {
			$firstPart = convert_cardinal_to_words(intval($num/100));
			$secondPart = convert_cardinal_to_words($num%100);
			$retval = array();
			if ($num%100 == 0) {
				$retval = $firstPart;
				array_push($retval, 'hundred');
				return $retval;
			} else if ($num%100 < 10) {
				$retval = $firstPart;
				array_push($retval, 'oh');
				$retval = array_merge($retval, $secondPart);
				return $retval;
			} else {
				$retval = $firstPart;
				$retval = array_merge($retval, $secondPart);
				return $retval;
			}
		}
		$digitGroups = array();
		$positive = abs($num);
		for ($i=0; $i < 4; $i++) { 
			$digitGroups[$i] = $positive%1000;
			$positive /= 1000;
		}
		$groupText = array();
		for ($i=0; $i < 4; $i++) { 
			$groupText[$i] = three_digit_group_to_words($digitGroups[$i]);
		}
		$combined = $groupText[0];
		$appendAnd = ($digitGroups[0] > 0) && ($digitGroups[0] < 100);
		for ($i=1; $i < 4; $i++) { 
			if ($digitGroups[$i] != 0) {
				$prefix = $groupText[$i]." ".$scaleNumbers[$i];
				if (strlen($combined) != 0) {
					$prefix .= $appendAnd ? " and " : " ";
				}
				$appendAnd = false;
				$combined = $prefix . $combined;
			}
		}
		if ($number < 0) {
			$combined = "negative " . $combined;
		}
		return explode(" ", $combined);
	}

	function three_digit_group_to_words($num) {
		global $smallNumbers, $tens, $scaleNumbers;
		$groupText = '';
		$hundredsPlace = intval($num/100);
		$tensUnit = $num%100;
		if ($hundredsPlace != 0) {
			$groupText .= $smallNumbers[$hundredsPlace] . ' hundred';
			if ($tensUnit > 0) {
				$groupText .= ' and ';
			}
		}
		$tensPlace = ($tensUnit/10);
		$onesPlace = $tensUnit%10;
		if ($tensPlace >= 2) {
			$groupText .= $tens[$tensPlace];
			if ($onesPlace != 0) {
				$groupText .= ' '.$smallNumbers[$onesPlace];
			}
		} else if ($tensUnit != 0) {
			$groupText .= $smallNumbers[$tensUnit];
		}
		return $groupText;
	}
?>