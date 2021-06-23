<?php

require_once 'src/Exception.php';
require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';
require_once 'src/Connection.php';
require_once 'src/Authentication.php';
require_once 'src/Helper.php';

class Onboarding{

    private $connection;
    public $error;

    public function __construct(){
        $connection = new Connection();
        $this->connection = $connection->connectDatabase();
        if ($this->connection->connect_error)
            return [
                'error' => $this->connection->connect_error
            ];
    }

    public function registerNewDF($email){
        $sql = "SELECT * FROM users where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$email);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        if($result->fetch_assoc() > 0) {
            http_response_code(101);
            return [
                'error' => 'User is already registered!'
            ];
        }else{
            $sql = "INSERT INTO users (email,password,status,confirmation_token) VALUE (?,?,?,?);";
            $preparedStatement = $this->connection->prepare($sql);
            $preparedStatement->bind_param('ssis',$email,$hashedPassword,$status,$confirmationToken);
            $status = 0;
            $confirmationToken = bin2hex(random_bytes(12));
            $hashedPassword = password_hash('password',PASSWORD_DEFAULT);
            $preparedStatement->execute();
            $this->sendEmail($email,'confirmEmail', $confirmationToken);
            http_response_code(200);
            return [
                'success' => 'User Successfully Registered:'. $email
            ];
        }
    }

    public function loginDF($email, $password){
        $sql = "SELECT * FROM users where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$email);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        if(!$result->num_rows > 0) {
            http_response_code(401);
            return [
                'error' => 'Email does not exist!'
            ];
        }else{
            $hashedPassword = '';
            while($row = $result->fetch_assoc()){
                $user['id'] = $row['id'];
                $user['email'] = $row['email'];
                $hashedPassword = $row['password'];
            }
//            $hashedPassword = password_hash($password,PASSWORD_DEFAULT);
//            $sql = "SELECT * FROM users where email = ? AND password = ?";
//            $preparedStatement = $this->connection->prepare($sql);
//            $preparedStatement->bind_param("ss",$email, $hashedPassword);
//            $preparedStatement->execute();
//            $result = $preparedStatement->get_result();
            if(!password_verify($password,$hashedPassword)) {
                http_response_code(401);
                return [
                    'error' => 'Invalid Password!'
                ];
            }else{
                $authentication = new Authentication();
                $accessToken = $authentication->generateAccessToken();

                $sql = "UPDATE users SET access_token = ? where email = ?";
                $preparedStatement = $this->connection->prepare($sql);
                $preparedStatement->bind_param("ss",$accessToken,$email);
                $update = $preparedStatement->execute();

                http_response_code(200);
                return [
                    'success' => 'Login Successful! You are an authenticated user now!!',
                    'user' => $user,
                    'access_token' => $accessToken
                ];
            }
        }
    }

    public function forgotPassword($email){
        $sql = "SELECT * FROM users where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$email);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        if($result->fetch_assoc() > 0) {
            $sql = "UPDATE users SET password_reset_token = ? where email = ?";
            $preparedStatement = $this->connection->prepare($sql);
            $preparedStatement->bind_param("ss",$resetToken,$email);
            $resetToken = bin2hex(random_bytes(12));
            $update = $preparedStatement->execute();
            if(!$this->sendEmail($email, 'forgotPassword', $resetToken)){
                http_response_code(500);
                return [
                    'error' => 'unable to send message'
                ];
            }
            http_response_code(200);
            return [
                'success' => 'An email has been sent to the email:'. $email
            ];
        }else{
            http_response_code(404);
            return [
                'error' => 'The email, '.$email.' is not registered!'
            ];
        }
    }

    public function changeForgotPassword($request){
        if(!$this->emailExists($request['email'])){
            http_response_code(404);
            return [
                'error' => 'The email does not exist'
            ];
        }
        $helper = new Helper();
        if(!$helper->checkForgotPasswordToken($request['forgot_password_token'],$request['email'])) {
            http_response_code(404);
            return [
                'error' => 'The email and forgot_password_token combination does not exist!!'
            ];
        }
        $sql = "UPDATE users SET password = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ss",$hashedPassword,$request['email']);
        $hashedPassword = password_hash($request['new_password'],PASSWORD_DEFAULT);
        $update = $preparedStatement->execute();
        if($update === false){
            $this->error = $preparedStatement->error;
            http_response_code(500);
            return [
                'error' => $this->error
            ];
        }else{
            $sql = "UPDATE users SET password_reset_token = ? where email = ?";
            $preparedStatement = $this->connection->prepare($sql);
            $preparedStatement->bind_param("ss",$nullToken,$request['email']);
            $nullToken = null;
            $preparedStatement->execute();
            http_response_code(200);
            return [
                'success' => 'You have successfully changed your password!'
            ];
        }
    }

    public function approveAccount($request){
        if(!$this->emailExists($request['email'])) {
            http_response_code(404);
            return [
                'error' => 'The email does not exist'
            ];
        }
        $helper = new Helper();
        $DBStatus = $helper->checkConfirmationToken($request['confirmation_token'], $request['email']);
        if($DBStatus) {
            http_response_code(200);
            return [
                'message' => 'Your email has already been approved!!'
            ];
        }else if($DBStatus === null){
            http_response_code(200);
            return [
                'error' => 'Your email and confirmation token do not match!!'
            ];
        }

        $sql = "UPDATE users SET status = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $status = 1;
        $preparedStatement->bind_param("is",$status,$request['email']);
        $update = $preparedStatement->execute();
        if($update === false){
            $this->error = $preparedStatement->error;
            http_response_code(500);
            return [
                'error' => $this->error
            ];
        }else{
            http_response_code(200);
            return [
                'success' => 'Your account has been successfully activated!'
            ];
        }
    }

    public function updateProfile($email, $request){
        if(!$this->emailExists($email)){
            http_response_code(404);
            return [
                'error' => 'The email does not exist'
            ];
        }

        $sql = "UPDATE users SET name = ?, account = ?, telephone = ?, password = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("sssss",$request['name'],$request['account'],$request['telephone'],$hashedPassword,$email);
        $hashedPassword = password_hash($request['password'],PASSWORD_DEFAULT);
        $update = $preparedStatement->execute();
        if($update === false){
            $this->error = $preparedStatement->error;
            http_response_code(500);
            return [
                'error' => $this->error
            ];
        }else{
            http_response_code(200);
            return [
                'success' => 'Your profile has been successfully updated!'
            ];
        }
    }

    public function updatePassword($email, $request){
        if(!$this->emailExists($email)){
            http_response_code(404);
            return [
                'error' => 'The email does not exist'
            ];
        }
        if(!$this->passwordCorrect($email,$request['old_password'])) {
            http_response_code(401);
            return [
                'error' => 'You have entered wrong old password'
            ];
        }
        $sql = "UPDATE users SET password = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ss",$hashedPassword,$email);
        $hashedPassword = password_hash($request['new_password'],PASSWORD_DEFAULT);
        $update = $preparedStatement->execute();
        if($update === false){
            $this->error = $preparedStatement->error;
            http_response_code(500);
            return [
                'error' => $this->error
            ];
        }else{
            http_response_code(200);
            return [
                'success' => 'You have successfully changed your password!'
            ];
        }
    }

    public function inviteFriends($request){
        foreach ($request['emails'] as $email)
            $this->sendEmail($email,'Invitation');
        return 'email sent';
    }


    /**
     *
     * send email to the given email
     * @param $email
     * @param $subject
     */
    private function sendEmail($email, $subject, $token = null){
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->SMTPSecure = "ssl";
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 465;
        $mail->SMTPAuth = true;
        $mail->Username = 'test@gmail.com'; // gmail
        $mail->Password = 'password'; // gmail password

        $mail->From = "test@gmail.com";// gmail
        $mail->FromName = "Test Name";
        $mail->IsSMTP();
        $host = $_SERVER['HTTP_HOST'];
        switch ($subject){
            case 'forgotPassword':
                $mail->addAddress($email);
                $mail->Subject = 'Reset Your Password';
                $mail->isHTML(true);
                $mail->Body = "<i>Hey your password reset link is: <a href='http://".$host."/dfa_sun/ForgotPassword.php?reset_password_token=".$token."'>Reset Here</a></i>";
                break;
            case 'confirmEmail':
                $mail->addAddress($email);
                $mail->Subject = 'Welcome! Confirm your email!';
                $mail->isHTML(true);
                $mail->Body = "<i>Confirm Your Email : <a href='http://".$host."/dfa_sun/ConfirmEmail.php?email=".$email."&confirmation_token=".$token."'>Confirm Now</a></i>";
                break;
        }
        return $mail->send();
    }

    /**
     * check if the given email exists in the given database
     * @param $email
     * @return bool
     */
    private function emailExists($email){
        $sql = "SELECT * FROM users where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$email);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        return $result->num_rows > 0;
    }

    /**
     * checks if the password is correct for the given email
     * @param $email
     * @param $password
     * @return bool
     */
    private function passwordCorrect($email, $password){
        $sql = "SELECT * FROM users where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$email);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        $hashedPassword = '';
        while($row = $result->fetch_assoc()){
            $hashedPassword = $row['password'];
        }
        return password_verify($password,$hashedPassword);
    }

    private function closeConnection(){
        $this->connection->close();
    }

}