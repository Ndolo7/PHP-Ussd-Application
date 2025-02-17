<?php

include "./functions/dbconnect.php";

    ;$stmt = $db->prepare("SELECT price FROM myorders WHERE phone_number = ? AND session_id = ? ORDER BY session_id DESC LIMIT 1");
    ;$stmt->execute([$phoneNumber, $sessionId]);
    ;$price = $stmt->fetchColumn();
    
    session_start();

    $errors  = array();
    $errmsg  = '';

    $config = array(
        "env"              => "sandbox",
        "BusinessShortCode"=> "174379",
        "key"              => "32kCGgGrKXtdjJeinekBuNrhHrrAmW9le0KXIrPISq4Ag2HO", //Enter your consumer key here
        "secret"           => "QYQR6e71VQSYYPO415RhMR352bap2SGea9ybUfSIT1KkgLckAGXXExvNShHc2A6t", //Enter your consumer secret here
        "username"         => "apitest",
        "TransactionType"  => "CustomerPayBillOnline",
        "passkey"          => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919", //Enter your passkey here
        "CallBackURL"      => "https://kind-terminally-sunfish.ngrok-free.app/trial/",
        "AccountReference" => "CompanyXLTD",
        "TransactionDesc"  => "Payment of X" ,
    );


    $phone = (substr($phoneNumber, 0, 1) == "+") ? str_replace("+", "", $phoneNumber) : $phoneNumber;

    $access_token = ($config['env']  == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
    $credentials = base64_encode($config['key'] . ':' . $config['secret']); 
    
    $ch = curl_init($access_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response); 
    $token = isset($result->{'access_token'}) ? $result->{'access_token'} : "N/A";

    $timestamp = date("YmdHis");
    $password  = base64_encode($config['BusinessShortCode'] . "" . $config['passkey'] ."". $timestamp);
    
    
    $curl_post_data = array( 
        "BusinessShortCode" => $config['BusinessShortCode'],
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => $config['TransactionType'],
        "Amount" => $price,
        "PartyA" => $phone,
        "PartyB" => $config['BusinessShortCode'],
        "PhoneNumber" => $phone,
        "CallBackURL" => $config['CallBackURL'],
        "AccountReference" => $config['AccountReference'],
        "TransactionDesc" => $config['TransactionDesc'],
    ); 

    $data_string = json_encode($curl_post_data);

    $endpoint = ($config['env'] == "live") ? "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest" : "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest"; 

    $ch = curl_init($endpoint );
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response     = curl_exec($ch);
    curl_close($ch);

    $result = json_decode(json_encode(json_decode($response)), true);

    if(!preg_match('/^[0-9]{10}+$/', $phone) && array_key_exists('errorMessage', $result)){
        $errors['phone'] = $result["errorMessage"];
    }