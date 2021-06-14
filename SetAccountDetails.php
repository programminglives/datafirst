<?php

require('Onboarding.php');


if(isset($_POST['email']) && isset($_POST['name'])
    && isset($_POST['account']) && isset($_POST['telephone'])){
    $onboarding = new Onboarding();
    $response = $onboarding->updateProfile($_POST['email'],$_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email, name, account and telephone!!'
    ],JSON_PRETTY_PRINT);
}