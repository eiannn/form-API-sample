<?php
class Database {
    private $primary_conn;
    private $secondary_conn;
    private $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            // Primary database connection
            $this->primary_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME_PRIMARY);
            
            if ($this->primary_conn->connect_error) {
                throw new Exception("Primary DB Connection failed: " . $this->primary_conn->connect_error);
            }

            // Secondary database connection
            $this->secondary_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME_SECONDARY);
            
            if ($this->secondary_conn->connect_error) {
                throw new Exception("Secondary DB Connection failed: " . $this->secondary_conn->connect_error);
            }

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
        }
    }

    public function getPrimaryConnection() {
        return $this->primary_conn;
    }

    public function getSecondaryConnection() {
        return $this->secondary_conn;
    }

    public function getError() {
        return $this->error;
    }

    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }

    public function prepareStatement($conn, $sql, $params = [], $types = "") {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Statement preparation failed: " . $conn->error);
        }

        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat("s", count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        return $stmt;
    }
}
?>