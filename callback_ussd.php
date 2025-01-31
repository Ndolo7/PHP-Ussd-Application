<?php

header("Content-Type: application/json");
$Ussdresponse = file_get_contents('php://input');

$logFile = "Ussdresponse.json";

$data = '{}'; //to ovewrite existing json data

file_put_contents($logFile, $data); //put contents in empty json file


$log = fopen($logFile, "a");

fwrite($log, $Ussdresponse);

fclose($log);

include 'functions/dbconnect.php';


