<?php

require('Onboarding.php');

if(isset($_POST['email']) && isset($_POST['password'])){
    $onboarding = new Onboarding();
    $response = $onboarding->loginDF($_POST['email'],$_POST['password']);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email and a password!!'
    ],JSON_PRETTY_PRINT);
}