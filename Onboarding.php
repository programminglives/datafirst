<?php

require_once "vendor/autoload.php";

use \PHPMailer\PHPMailer\PHPMailer;

class Onboarding{

    private $hostname = 'localhost';
    private $username = 'root';
    private $password = 'reignofchaos';
    private $database = 'dfa';
    private $connection;
    public $error;

    public function __construct(){
        $this->connection = new mysqli($this->hostname,$this->username,$this->password,$this->database);
        if ($this->connection->connect_error)
            die("Connection failed: " . $this->connection->connect_error);
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
            $sql = "INSERT INTO users (email,password,status) VALUE (?,?,?);";
            $preparedStatement = $this->connection->prepare($sql);
            $preparedStatement->bind_param('ssi',$email,$hashedPassword,$status);
            $status = 0;
            $hashedPassword = password_hash('password',PASSWORD_DEFAULT);
            $preparedStatement->execute();
            $this->sendEmail($email,'registration');
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
                http_response_code(200);
                return [
                    'success' => 'Login Successful! You are an authenticated user now!!',
                    'user' => $user
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
            if(!$this->sendEmail($email, 'forgotPassword')){
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

    public function approveAccount($email){
        if(!$this->emailExists($email)) {
            http_response_code(404);
            return [
                'error' => 'The email does not exist'
            ];
        }
        $sql = "UPDATE users SET status = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("is",$status,$email);
        $status = 1;
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
                'success' => 'Your profile has been successfully activated!'
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

        $sql = "UPDATE users SET name = ?, account = ?, telephone = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ssss",$request['name'],$request['account'],$request['telephone'],$email);
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
        if(!$this->passwordCorrect($email,$request['oldPassword'])) {
            http_response_code(401);
            return [
                'error' => 'You have entered wrong old password'
            ];
        }
        $sql = "UPDATE users SET password = ? where email = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ss",$hashedPassword,$email);
        $hashedPassword = password_hash($request['newPassword'],PASSWORD_DEFAULT);
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


    /**
     *
     * send email to the given email
     * @param $email
     * @param $subject
     */
    private function sendEmail($email, $subject){
        $mail = new PHPMailer(true);
        switch ($subject){
            case 'forgotPassword':
                $mail->IsSMTP();
                $mail->SMTPSecure = "ssl";
                $mail->Host = "smtp.gmail.com";
                $mail->Port = 465;
                $mail->SMTPAuth = true;
                $mail->Username = 'test@gmail.com'; // gmail
                $mail->Password = 'password'; // gmail password

                $mail->From = "bomzansanjaya@gmail.com";
                $mail->FromName = "Test Name";
                $mail->addAddress($email);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = "<i>Hey your password reset link is: <a href='#'>Link</a></i>";
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