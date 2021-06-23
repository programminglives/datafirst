<?php

class Connection {

    private $hostname = 'localhost';
    private $username = 'root';
    private $password = 'reignofchaos';
    private $database = 'dfa';

    private $connection;

    public function connectDatabase(){
        $this->connection = new mysqli($this->hostname,$this->username,$this->password,$this->database);
        return $this->connection;
    }
}