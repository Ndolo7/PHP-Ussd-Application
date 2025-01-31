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
    $response  = "CON Welcome to the Jay's Uji Power!\n";
    $response .= "1. Order Uji Power\n";
    $response .= "2. Exit";

} else if ($inputs[0] == "1" && count($inputs) == 1) {
    // Tea type selection
    $response  = "CON Choose tea type:\n";
    $response .= "1. With Sugar\n";
    $response .= "2. Sugarless";

} else if ($inputs[0] == "1" && count($inputs) == 2) {
    // Flavor selection
    $response  = "CON Choose flavor:\n";
    $response .= "1. Flavored\n";
    $response .= "2. Unflavored";

} else if ($inputs[0] == "1" && count($inputs) == 3 && $inputs[2] == "1") {
    // Specific flavor type selection (if "Flavored" was chosen)
    $response  = "CON Choose flavor type (Sh 100 Each):\n";
    $response .= "1. Honey \n";
    $response .= "2. Moringa\n";
    $response .= "3. Mukombero\n";
    $response .= "4. Thafai\n";
    $response .= "5. Special";

} else if ($inputs[0] == "1" && ((count($inputs) == 3 && $inputs[2] == "2") || count($inputs) == 4)) {
    // Quantity input
    $response  = "CON Enter quantity (How many cups ?)";

} else if ($inputs[0] == "1" && count($inputs) == 5) {
    // Location (building) input
    $response  = "CON Enter your location (building):";

} else if ($inputs[0] == "1" && count($inputs) == 6) {
    // Office name or number input
    $response  = "CON Enter your office name or number:\n";
    $response .= "Enter none if theres no office."; 

} else if ($inputs[0] == "1" && count($inputs) == 7) {
    // Delivery time selection
    $response  = "CON Choose delivery time:\n";
    $response .= "1. 5-7am\n";
    $response .= "2. 9-11am\n";
    $response .= "3. 12-2pm\n";
    $response .= "4. 3-5pm";

} else if ($inputs[0] == "1" && count($inputs) == 8) {
    // Final confirmation
    $teaType = $inputs[1] == "1" ? "Sugared" : "Sugarless";
    $flavor = $inputs[2] == "1" ? "Flavored" : "Unflavored";
    $flavourOptions = ["Honey", "Moringa", "Mukombero", "Thafai", "Special"];
    
    if ($inputs[3] == "2") {
        $flavorType = "None";
    } else
        $flavorType = $flavourOptions[$inputs[2] -1 ];

    $quantity = (int)$inputs[4];
    $building = $inputs[5];
    $office = $inputs[6];
    $timeOptions = ["5-7am", "9-11am", "12-2pm", "3-5pm"];
    $time = $timeOptions[$inputs[7] - 1];
    $price = $quantity * 100;

    $response = "CON Confirm your order:\n";
    $response .= "Uji: $teaType\n";
    $response .= "Flavor: $flavorType\n";
    $response .= "Quantity: $quantity\n";
    $response .= "Building: $building, Office: $office\n";
    $response .= "Delivery Time: $time\n";
    $response .= "Total Price: $price Ksh\n";
    $response .= "1. Pay\n";
    $response .= "2. Cancel/Change Order";

    ;$stmt = $db->prepare("INSERT INTO orders (tea_type, flavor, quantity, building, office, delivery_time, phone_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    ;$stmt->execute([$teaType, $flavorType, $quantity, $building, $office, $time, $phoneNumber]);

    $orderId = $db->lastInsertId();

} else if ($inputs[0] == "1" && count($inputs) == 9 && $inputs[8] == "1") {
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
