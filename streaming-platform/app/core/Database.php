<?php
// ============================================
// DATABASE CORE CLASS
// Streaming Platform Backend System
// ============================================

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST, 
                DB_USER, 
                DB_PASS, 
                DB_NAME, 
                DB_PORT
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die(json_encode([
                'error' => 'Database Connection Error',
                'message' => $e->getMessage(),
                'solution' => 'Check if MySQL is running in XAMPP'
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Execute query with parameters
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $this->connection->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bind_params = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } elseif (is_null($param)) {
                    $types .= 's';
                    $param = null;
                } else {
                    $types .= 'b';
                }
                $bind_params[] = $param;
            }
            
            array_unshift($bind_params, $types);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bind_params));
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        return $stmt;
    }
    
    // Fetch single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    // Insert data
    public function insert($table, $data) {
        $keys = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $sql = "INSERT INTO `$table` (" . implode(',', $keys) . ") VALUES ($placeholders)";
        $stmt = $this->query($sql, $values);
        
        return $this->connection->insert_id;
    }
    
    // Update data
    public function update($table, $data, $where, $where_params = []) {
        $set_parts = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $set_parts[] = "`$key` = ?";
            $values[] = $value;
        }
        
        $values = array_merge($values, $where_params);
        $sql = "UPDATE `$table` SET " . implode(', ', $set_parts) . " WHERE $where";
        
        $stmt = $this->query($sql, $values);
        return $stmt->affected_rows;
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->affected_rows;
    }
    
    // Helper function for binding parameters
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    
    // Count rows
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM `$table` WHERE $where";
        $result = $this->fetchOne($sql, $params);
        return $result['count'];
    }
    
    // Begin transaction
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        $this->connection->rollback();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
}