<?php

require('Onboarding.php');

if(isset($_POST['email'])){
    $onboarding = new Onboarding();
    $response = $onboarding->approveAccount($_POST['email']);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email!!'
    ],JSON_PRETTY_PRINT);
}