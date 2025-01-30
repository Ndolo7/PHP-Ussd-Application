<?php

header("Content-Type: application/x-www-form-urlencoded");
$UssdCallbackResponse = file_get_contents('php://input');
$jsonData = json_encode($UssdCallbackResponse);
$logFile = "Ussdresponse,json";
$log = fopen($logFile, "a");
fwrite($log, $UssdCallbackResponse);
fclose($log);

include 'dbconnect.php';