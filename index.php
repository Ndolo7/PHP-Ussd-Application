<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming requests
$logMessage = date('Y-m-d H:i:s') . " - Received USSD callback\n";
$logMessage .= "POST data: " . print_r($_POST, true) . "\n";
error_log($logMessage, 3, "ussd_callback_log.txt");

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



// Read the variables sent via POST from our API
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];


// Split the text input by "*"
$inputs = explode("*", $text);

// Determine the flow based on the number of inputs
if ($text == "") {
    // Initial menu
    $response  = "CON Welcome to orders By Precious!\n";
    $response .= "1. Uji Power\n";
    $response .= "2. Exit";

} else if ($inputs[0] == "1" && count($inputs) == 1) {
    // Tea type selection
    $response  = "CON Please select Uji Power with:\n";
    $response .= "1. No Sugar\n";
    $response .= "2. Sugar\n ";
    $response .= "3. Extra Sugar\n ";
    $response .= "4. Honey ";

} else if ($inputs[0] == "1" && count($inputs) == 2) {
    // Flavor selection
    $response  = "CON Please select your flavor at an additional cost of sh 10:\n";
    $response .= "1. Mukombero\n";
    $response .= "2. Moringa\n";
    $response .= "3. Thabai\n";
    $response .= "4. Above mixture (Confimation after order)\n";
    $response .= "5. No Flavor\n";

} else if ($inputs[0] == "1" && count($inputs) == 3) {
    $response  = "CON Please provide your location (eg. Building, Office name, Shop)";
    

} else if ($inputs[0] == "1" && count($inputs) == 4) {
    // Delivery time selection
    $response  = "CON Choose delivery time:\n";
    $response .= "1. 8:30am - 9:30am\n";
    $response .= "2. 9:30am - 11:00am\n";
    $response .= "3. 11:00am - 12:30pm\n";
    $response .= "4. 3:00pm - 4:30pm\n";
    $response .= "4. 6:00pm - 9:00pm";


} else if ($inputs[0] == "1" && count($inputs) == 5) {
    // Final confirmation
    $sugarOptions = ["No Sugar", "Sugar", "Extra Sugar", "Honey"];
    $sugarType = $sugarOptions[$inputs[1] -1 ];

    $flavourOptions = [ "Mukombero", "Moringa",  "Thabai", "Above mixture (Confimation after order)", "No Flavor"];
    $flavorType = $flavourOptions[$inputs[2] -1 ];

    $location = $inputs[3];
    
    $timeOptions = ["8:30am - 9:30am", "9:30am - 11:00am", "11:00am - 12:30pm", "3:00pm - 4:30pm", "6:00pm - 9:00pm"];
    $time = $timeOptions[$inputs[4] - 1];

    $price = 100;
    // Sugar type pricing
    if ($inputs[1] == "3") { // Extra Sugar
        $price += 10;
    } else if ($inputs[1] == "4") { // Honey
        $price += 10;
    }

    // Flavor pricing
    if ($inputs[2] == "1" || $inputs[2] == "2" || $inputs[2] == "3") { // Single flavors
        $price += 10;
    } else if ($inputs[2] == "4") { // Mixed flavors
        $price += 20;
    }


    $response = "CON Confirm your order:\n";
    $response .= "Uji: $flavorType\n";
    $response .= "Sugar: $sugarType\n";
    $response .= "Location: $location\n";
    $response .= "Delivery Time: $time\n";
    $response .= "Amount: $price Ksh\n";
    $response .= "1. Pay\n";
    $response .= "2. Cancel/Change Order";

    ;$stmt = $db->prepare("INSERT INTO myorders (flavor_type, sugar_type, location_type, delivery_time, phone_number, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    ;$stmt->execute([$flavorType, $sugarType, $location, $time, $phoneNumber]);

    $orderId = $db->lastInsertId();    
    

} else if ($inputs[0] == "1" && count($inputs) == 6 && $inputs[5] == "1") {
    // Payment step
    
    $quantity = (int)$inputs[4];
    $price = $quantity * 100;
    $amount = $price;
    include 'functions/configs.php'; 
    
    
    $url = "https://api.paystack.co/transaction/initialize";

    $fields = [
        'email' => "imanindolo77@gmail.com",
        'amount' => $amount,
        'phoneNumber' => $phoneNumber,
        'currency' => "KES"
        
    ];

    $fields_string = http_build_query($fields);

    //open connection
    $ch = curl_init();
    
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $SecretKey",
        "Cache-Control: no-cache",
    ));
    
    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
    
    //execute post
    $result = curl_exec($ch);
    echo $result;
    




} else {
    // Invalid input
    $response = "CON Invalid selection. Try again.";
}

// Return the response to the API
header('Content-type: text/plain');
echo $response
?>
