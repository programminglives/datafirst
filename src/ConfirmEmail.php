<?php

require('Onboarding.php');

if(isset($_POST['email']) && isset($_POST['confirmation_token'])){
    $onboarding = new Onboarding();
    $response = $onboarding->confirmEmail($_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter the email and confirmation_token!!'
    ],JSON_PRETTY_PRINT);
}