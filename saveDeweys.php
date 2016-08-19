<?php 
require 'functions.php';
$deweys = getDeweys();
$deweyString = stringDeweySelection($deweys, 8000);
file_put_contents('deweys.txt', $deweyString, FILE_APPEND);
echo 'Done saving';
?>