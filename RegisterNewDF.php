<?php

require('Onboarding.php');

if(isset($_POST['email'])){
    if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'error' => 'Please enter a valid email!'
        ],JSON_PRETTY_PRINT);
        die();
    }
    try {
        $onboarding = new Onboarding();
        $response = $onboarding->registerNewDF($_POST['email']);
    }catch (Exception $e){
        echo json_encode($e->getMessage());
        die();
    }
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email!!'
    ],JSON_PRETTY_PRINT);
}
