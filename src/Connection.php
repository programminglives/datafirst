<?php

class Connection {

    private $hostname = 'localhost';
    private $username = 'username';
    private $password = 'password';
    private $database = 'database';

    private $connection;

    public function connectDatabase(){
        $this->connection = new mysqli($this->hostname,$this->username,$this->password,$this->database);
        return $this->connection;
    }
}