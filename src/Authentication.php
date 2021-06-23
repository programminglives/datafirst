<?php

require_once 'Connection.php';

class Authentication{
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

    public function generateAccessToken(){
        $fp = @fopen('/dev/random','rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, 10);
            @fclose($fp);
        }
        else
        {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        // Remove = from the end
        $result = str_replace('=', ' ', $result);
        return $result;
    }

    public function getUser($accessToken){
        $sql = "SELECT * FROM users where access_token = ?";
        $preparedStatement = $this->connection->prepare($sql);
        $preparedStatement->bind_param("s",$accessToken);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        if($result->num_rows > 0) {
            $user = [];
            while ($row = $result->fetch_assoc()) {
                $user['email'] = $row['email'];
            }
            return $user;
        }
        return false;
    }

}