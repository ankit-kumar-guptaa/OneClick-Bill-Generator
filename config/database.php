<?php
class Database {
    private $host = "srv839.hstgr.io";
    private $db_name = "u511651506_oneclick_bills";
    private $username = "u511651506_oneclick_bills";
    private $password = "Raja@123321@";
    // private $host = "localhost";
    // private $db_name = "oneclick_bills";
    // private $username = "root";
    // private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
