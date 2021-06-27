<?php

require('Onboarding.php');

if(isset($_POST['email']) && isset($_POST['forgot_password_token'])
    && isset($_POST['new_password']) && isset($_POST['confirm_new_password'])){
    $newPassword = $_POST['new_password'];
    $passwordStrength = 'Please consider using a stronger password. Use at least 8 characters, one number and one letter!';
    if(strlen($newPassword) < 8 && !preg_match("#[0-9]+#", $newPassword)){
        echo json_encode([
            'warning' => $passwordStrength
        ],JSON_PRETTY_PRINT);
        die();
    }
    if($_POST['new_password'] != $_POST['confirm_new_password']){
        echo json_encode([
            'error' => 'Your new passwords do not match',
        ]);
        die();
    }
    $onboarding = new Onboarding();
    $response = $onboarding->changeForgotPassword($_POST);
    echo json_encode($response,JSON_PRETTY_PRINT);
}else{
    echo json_encode([
        'error' => 'Please enter email, forgot_password_token, new_password and confirm_new_password!!'
    ],JSON_PRETTY_PRINT);
}