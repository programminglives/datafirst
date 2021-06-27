<?php

require('Onboarding.php');

if(isset($_POST['email']) && isset($_POST['confirmation_token'])){
    $onboarding = new Onboarding();
    $response = $onboarding->approveAccount($_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email and a confirmation token!!'
    ],JSON_PRETTY_PRINT);
}