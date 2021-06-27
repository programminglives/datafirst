<?php

require('Onboarding.php');


if(isset($_POST['email']) && isset($_POST['name']) && isset($_POST['password'])
    && isset($_POST['account']) && isset($_POST['agree_terms'])){
    if(json_decode($_POST['agree_terms']) != true) {
        echo json_encode([
            'error' => 'You must agree to the terms and conditions!!'
        ], JSON_PRETTY_PRINT);
        die();
    }
    $onboarding = new Onboarding();
    $response = $onboarding->updateProfile($_POST['email'],$_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email, name, account, password and telephone!!'
    ],JSON_PRETTY_PRINT);
}