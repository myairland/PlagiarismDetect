<?php
$array = array(); 
$array['age'] = $_POST["str1"] . "1111";
$array['name'] = $_POST['str2']."2222";
die(json_encode($array));

?>