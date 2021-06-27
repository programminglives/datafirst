<?php

require_once 'DotEnv.php';

class Connection {

    private $hostname = 'autoshay-mysql.cilucdyakkvg.us-east-1.rds.amazonaws.com';
    private $username = 'vishal';
    private $password = 'vishal';
    private $database = 'DFA_vishal';

    private $connection;

    public function __construct(){
        (new DotEnv(__DIR__ . '/../.env'))->load();
    }

    public function connectDatabase(){
        $this->connection = new mysqli($this->hostname,$this->username,$this->password,$this->database);
        return $this->connection;
    }
}