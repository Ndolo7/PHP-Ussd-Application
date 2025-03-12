<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming requests
$logMessage = date('Y-m-d H:i:s') . " - Received USSD callback\n";
$logMessage .= "POST data: " . print_r($_POST, true) . "\n";
error_log($logMessage, 3, "ussd_callback_log.txt");

include "./functions/dbconnect.php";

// Read POST variables
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];

// Split text input by "*"
$inputs = explode("*", $text);

// Menu logic
if ($text == "") {
    // Main menu
    $response  = "CON Welcome to Our Service!\n";
    $response .= "1. Food\n";
    $response .= "2. Drink\n";
    $response .= "3. Snack\n";
    $response .= "4. Exit";

} elseif ($inputs[0] == "1" && count($inputs) == 1) {
    // Food selection
    $response  = "CON Select your meal (100 Ksh each):\n";
    $response .= "1. F1\n";
    $response .= "2. F2\n";
    $response .= "3. F3\n";
    $response .= "4. F4\n";
    $response .= "0. Back";

} elseif ($inputs[0] == "2" && count($inputs) == 1) {
    // Drink selection
    $response  = "CON Select your drink:\n";
    $response .= "1. Juice (100 Ksh)\n";
    $response .= "2. Uji Power (100 Ksh)\n";
    $response .= "3. Tea (50 Ksh)\n";
    $response .= "4. Coffee (50 Ksh)\n";
    $response .= "0. Back";

} elseif ($inputs[0] == "2" && $inputs[1] == "1" && count($inputs) == 2) {
    // Juice flavor selection
    $response  = "CON Select juice flavor (100 Ksh):\n";
    $response .= "1. J1\n";
    $response .= "2. J2\n";
    $response .= "3. J3\n";
    $response .= "4. J4\n";
    $response .= "5. J5\n";
    $response .= "0. Back";

} elseif ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 2) {
    // Uji Power sugar selection
    $response  = "CON Select Uji Power with:\n";
    $response .= "1. No Sugar\n";
    $response .= "2. Sugar\n";
    $response .= "3. Extra Sugar (sh 10)\n";
    $response .= "4. Honey (sh 10)\n";
    $response .= "0. Back";

} elseif ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 3) {
    // Uji Power flavor selection
    $response  = "CON Select flavor (sh 10 extra):\n";
    $response .= "1. Mukombero\n";
    $response .= "2. Moringa\n";
    $response .= "3. Thabai\n";
    $response .= "4. Above mixture\n";
    $response .= "5. No Flavor\n";
    $response .= "0. Back";

} elseif ($inputs[0] == "3" && count($inputs) == 1) {
    // Snack selection
    $response  = "CON Select your snack (50 Ksh each):\n";
    $response .= "1. S1\n";
    $response .= "2. S2\n";
    $response .= "3. S3\n";
    $response .= "4. S4\n";
    $response .= "5. S5\n";
    $response .= "0. Back";

} elseif (
    ($inputs[0] == "1" && count($inputs) == 2) ||  // Food selected
    ($inputs[0] == "2" && $inputs[1] == "1" && count($inputs) == 3) ||  // Juice selected
    ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 4) ||  // Uji Power selected
    ($inputs[0] == "2" && $inputs[1] == "3" && count($inputs) == 2) ||  // Tea selected
    ($inputs[0] == "2" && $inputs[1] == "4" && count($inputs) == 2) ||  // Coffee selected
    ($inputs[0] == "3" && count($inputs) == 2)     // Snack selected
) {
    // Location input
    $response = "CON Please provide your location (e.g., Building, Office, Shop):\n";
    $response .= "0. Back";

} elseif (
    ($inputs[0] == "1" && count($inputs) == 3) ||  // Food
    ($inputs[0] == "2" && $inputs[1] == "1" && count($inputs) == 4) ||  // Juice
    ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 5) ||  // Uji Power
    ($inputs[0] == "2" && $inputs[1] == "3" && count($inputs) == 3) ||  // Tea
    ($inputs[0] == "2" && $inputs[1] == "4" && count($inputs) == 3) ||  // Coffee
    ($inputs[0] == "3" && count($inputs) == 3)     // Snack
) {
    // Delivery time selection
    $response  = "CON Choose delivery time:\n";
    $response .= "1. 8:30am - 9:30am\n";
    $response .= "2. 9:30am - 11:00am\n";
    $response .= "3. 11:00am - 12:30pm\n";
    $response .= "4. 3:00pm - 4:30pm\n";
    $response .= "5. 6:00pm - 9:00pm\n";
    $response .= "0. Back";

} elseif (
    ($inputs[0] == "1" && count($inputs) == 4) ||  // Food
    ($inputs[0] == "2" && $inputs[1] == "1" && count($inputs) == 5) ||  // Juice
    ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 6) ||  // Uji Power
    ($inputs[0] == "2" && $inputs[1] == "3" && count($inputs) == 4) ||  // Tea
    ($inputs[0] == "2" && $inputs[1] == "4" && count($inputs) == 4) ||  // Coffee
    ($inputs[0] == "3" && count($inputs) == 4)     // Snack
) {
    // Confirmation step
    $item = "";
    $price = 0; // Initialize price
    $extraDetails = "";

    // Determine selected item and set price
    if ($inputs[0] == "1") {
        $foodOptions = ["F1", "F2", "F3", "F4"];
        $item = $foodOptions[$inputs[1] - 1];
        $price = 100; // Food price
    } elseif ($inputs[0] == "2" && $inputs[1] == "1") {
        $juiceOptions = ["J1", "J2", "J3", "J4", "J5"];
        $item = "Juice (" . $juiceOptions[$inputs[2] - 1] . ")";
        $price = 100; // Juice price
    } elseif ($inputs[0] == "2" && $inputs[1] == "2") {
        $sugarOptions = ["No Sugar", "Sugar", "Extra Sugar", "Honey"];
        $flavorOptions = ["Mukombero", "Moringa", "Thabai", "Above mixture", "No Flavor"];
        $item = "Uji Power";
        $extraDetails = $sugarOptions[$inputs[2] - 1] . "\n , " . $flavorOptions[$inputs[3] - 1];
        $price = 100; // Uji Power base price
        if ($inputs[2] == "3" || $inputs[2] == "4") $price += 10; // Extra sugar or honey
        if ($inputs[3] == "1" || $inputs[3] == "2" || $inputs[3] == "3") $price += 10; // Single flavor
        if ($inputs[3] == "4") $price += 20; // Mixed flavor
    } elseif ($inputs[0] == "2" && $inputs[1] == "3") {
        $item = "Tea";
        $price = 50; // Tea price
    } elseif ($inputs[0] == "2" && $inputs[1] == "4") {
        $item = "Coffee";
        $price = 50; // Coffee price
    } elseif ($inputs[0] == "3") {
        $snackOptions = ["S1", "S2", "S3", "S4", "S5"];
        $item = $snackOptions[$inputs[1] - 1];
        $price = 50; // Snack price
    }

    $location = $inputs[count($inputs) - 2];
    $timeOptions = ["8:30am - 9:30am", "9:30am - 11:00am", "11:00am - 12:30pm", "3:00pm - 4:30pm", "6:00pm - 9:00pm"];
    $time = $timeOptions[$inputs[count($inputs) - 1] - 1];

    $response = "CON Confirm your order:\n";
    $response .= "Item: $item\n";
    if ($extraDetails) $response .= "$extraDetails\n";
    $response .= "Location: $location\n";
    $response .= "Time: $time\n";
    $response .= "Amount: $price Ksh\n";
    $response .= "1. Pay\n";
    $response .= "2. Cancel/Change Order\n";
    $response .= "0. Back";

    $stmt = $db->prepare("INSERT INTO orders (item, extra_details, location, delivery_time, phone_number, status, price, session_id) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
    $stmt->execute([$item, $extraDetails, $location, $time, $phoneNumber, $price, $sessionId]);

} elseif (
    ($inputs[0] == "1" && count($inputs) == 5 && $inputs[4] == "1") ||  // Food payment
    ($inputs[0] == "2" && $inputs[1] == "1" && count($inputs) == 6 && $inputs[5] == "1") ||  // Juice payment
    ($inputs[0] == "2" && $inputs[1] == "2" && count($inputs) == 7 && $inputs[6] == "1") ||  // Uji Power payment
    ($inputs[0] == "2" && $inputs[1] == "3" && count($inputs) == 5 && $inputs[4] == "1") ||  // Tea payment
    ($inputs[0] == "2" && $inputs[1] == "4" && count($inputs) == 5 && $inputs[4] == "1") ||  // Coffee payment
    ($inputs[0] == "3" && count($inputs) == 5 && $inputs[4] == "1")     // Snack payment
) {
    // Payment step
    include './functions/stkpush.php';
    if ($result['ResponseCode'] === "0") {
        $MerchantRequestID = $result['MerchantRequestID'];
        $CheckoutRequestID = $result['CheckoutRequestID'];
        $response = "CON Enter M-Pesa PIN when prompted.\n";
        $response .= "0. Back";
    }

} else {
    $response = "CON Invalid selection. Try again.\n";
    $response .= "0. Back";
}

// Return response
header('Content-type: text/plain');
echo $response;
?>