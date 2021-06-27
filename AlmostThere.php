<?php

require_once('Onboarding.php');
require_once('src/Authentication.php');


if(isset($_POST['access_token']) && isset($_POST['emails'])){
    $authentication = new Authentication();
    if(!$authentication->getUser($_POST['access_token'])) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Your access token is not authenticated!!'
        ], JSON_PRETTY_PRINT);
        die();
    }
    $onboarding = new Onboarding();
    $response = $onboarding->inviteFriends($_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter access_token and email list!!'
    ],JSON_PRETTY_PRINT);
}