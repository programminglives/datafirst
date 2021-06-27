<?php

require_once('Connection.php');

class Helper{

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

    public function checkConfirmationToken($token, $email){
        $sql = "SELECT * FROM usersDF_SYS_102 where email = ? AND confirmation_token = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ss",$email, $token);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        $status = null;
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $status = $row['status'];
            }
        }
        return $status;
    }

    public function checkForgotPasswordToken($token, $email){
        $sql = "SELECT * FROM usersDF_SYS_102 where email = ? AND password_reset_token = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("ss",$email, $token);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        return $result->num_rows > 0;
    }

}