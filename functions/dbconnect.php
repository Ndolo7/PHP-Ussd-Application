<?php


// Database connection
try {
  $servername = "localhost";
  $username = "root";
  $password = ""; 
  $dbname = "ussds";
  $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  error_log("Database connection failed: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
  exit;
}



  ?>