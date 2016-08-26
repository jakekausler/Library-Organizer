<?php
require_once 'functions.php';
	function getOriginalMap() {
		$idLocationMap = array();
		$conn = getConnection();
		if ($conn->connect_errno>0) {
			die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT BookID,ImageURL FROM books";
		$result = $conn->query($sql);
		if (!$result) {
			return false;
			die("Query failed: " . $conn->error);
		}
		while ($row = $result->fetch_assoc()) {
			$idLocationMap[$row['BookID']] = $row['ImageURL'];
		}
		return $idLocationMap;
	}
	function copyImages($idLocationMap)
	{
		$i = 0;
		$count = count($idLocationMap);
		foreach ($idLocationMap as $id => $location) {
			if ($location != '') {
				$extension = pathinfo($location)['extension'];
				if ($extension=='') {
					$extension = 'jpg';
				} elseif (strpos($extension, '?') > -1) {
					$extension = substr($extension, 0, strpos($extension, '?'));
				}
				$newLocation = 'res/bookimages/'.$id.'.'.$extension;
				copy($location, $newLocation);
				$idLocationMap[$id] = $newLocation;
			}
			$i++;
			echo $i.' of '.$count.'</br>';
		}
		return $idLocationMap;
	}
	function printLocations($idLocationMap) {
		foreach ($idLocationMap as $id => $location) {
			if ($location != '') {
				echo '<img src="'.$location.'" alt="not found!" /></br>';
			}
		}
	}
	function renameImages($idLocationMap) {
		foreach ($idLocationMap as $id => $location) {
			$conn = getConnection();
			if ($conn->connect_errno>0) {
				die("Connection failed: " . $conn->connect_error);
			}
			$sql = "UPDATE books SET ImageURL='".$location."' WHERE BookID=".$id;
			if ($conn->query($sql) !== TRUE) {
		    	echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}
	}
	function getMap($idLocationMap) {
		foreach ($idLocationMap as $id => $location) {
			if (file_exists('res/bookimages/'.$id.'.jpg')) {
				$idLocationMap[$id] = 'res/bookimages/'.$id.'.jpg';
			} else {
				$idLocationMap[$id] = '';
			}
		}
		return $idLocationMap;
	}
	renameImages(getMap(getOriginalMap()));
?>