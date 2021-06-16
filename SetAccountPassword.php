<?php

require('Onboarding.php');

if(isset($_POST['email']) && isset($_POST['oldPassword']) && isset($_POST['newPassword'])){
    $newPassword = $_POST['newPassword'];
    $passwordStrength = 'Please consider using a stronger password. Use at least 8 characters, one number and one letter!';
    if(strlen($newPassword) < 8 && !preg_match("#[0-9]+#", $newPassword)){
        echo json_encode([
            'warning' => $passwordStrength
        ],JSON_PRETTY_PRINT);
        die();
    }
    $onboarding = new Onboarding();
    $response = $onboarding->updatePassword($_POST['email'],$_POST);
    echo json_encode(array_merge($response),JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter an email, oldPassword and newPassword!!'
    ],JSON_PRETTY_PRINT);
}