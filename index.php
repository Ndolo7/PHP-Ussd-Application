<?php
include 'initialize.php';

$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];


// Handle different menu levels based on user input
if ($text == "") {
    // First level - Sugar preference
    $response = "CON Welcome to Tea Order Service\n";
    $response .= "Select Sugar Option:\n";
    $response .= "1. Sugared Tea\n";
    $response .= "2. Sugarless Tea";
}

// First level responses
else if ($text == "1" || $text == "2") {
    // Show flavor menu
    $response = "CON Select Tea Flavor (100 Ksh each):\n";
    $response .= "1. Flavor A\n";
    $response .= "2. Flavor B\n";
    $response .= "3. Flavor C\n";
    $response .= "4. Flavor D\n";
    $response .= "5. Flavor E";
}

// Second level responses (Sugar*Flavor)
else if (preg_match('/^[1-2]\*[1-5]$/', $text)) {
    // Ask for quantity
    $response = "CON Enter number of cups:";
}

// Third level responses (Sugar*Flavor*Quantity)
else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+$/', $text)) {
    // Ask for building location
    $response = "CON Enter building name/location:";
}

// Fourth level responses (Sugar*Flavor*Quantity*Building)
else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+\*[a-zA-Z0-9 ]+$/', $text)) {
    // Ask for office
    $response = "CON Enter office name/number:";
}

// Fifth level responses (Sugar*Flavor*Quantity*Building*Office)
else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+\*[a-zA-Z0-9 ]+\*[a-zA-Z0-9 ]+$/', $text)) {
    // Show delivery time options
    $response = "CON Select delivery time:\n";
    $response .= "1. 5-7am\n";
    $response .= "2. 9-11am\n";
    $response .= "3. 1-3pm\n";
    $response .= "4. 5-7pm";
}

// Sixth level responses (Sugar*Flavor*Quantity*Building*Office*Time)
else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+\*[a-zA-Z0-9 ]+\*[a-zA-Z0-9 ]+\*[1-4]$/', $text)) {
    // Parse all input
    $parts = explode('*', $text);
    $sugar = ($parts[0] == '1') ? 'Sugared' : 'Sugarless';
    $flavor = chr(64 + intval($parts[1])); // Convert 1,2,3,4,5 to A,B,C,D,E
    $quantity = $parts[2];
    $building = $parts[3];
    $office = $parts[4];
    $time = [
        '1' => '5-7am',
        '2' => '9-11am',
        '3' => '1-3pm',
        '4' => '5-7pm'
    ][$parts[5]];
    
    $total = $quantity * 100; // 100 Ksh per cup
    
    // Show order summary and payment options
    $response = "CON Order Summary:\n";
    $response .= "$sugar Tea Flavor $flavor\n";
    $response .= "Quantity: $quantity cups\n";
    $response .= "Location: $building, Office: $office\n";
    $response .= "Delivery: $time\n";
    $response .= "Total: $total Ksh\n\n";
    $response .= "1. Confirm & Pay\n";
    $response .= "2. Cancel Order";
}

// Seventh level responses (Sugar*Flavor*Quantity*Building*Office*Time*Choice)
else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+\*[a-zA-Z0-9 ]+\*[a-zA-Z0-9 ]+\*[1-4]\*1$/', $text)) {
    echo "<script>payWithPaystack()</script>";
}

else if (preg_match('/^[1-2]\*[1-5]\*[0-9]+\*[a-zA-Z0-9 ]+\*[a-zA-Z0-9 ]+\*[1-4]\*2$/', $text)) {
    // If user cancelled (chose 2)
    $response = "END Order cancelled. Thank you for using our service!";
}

    



   

?>