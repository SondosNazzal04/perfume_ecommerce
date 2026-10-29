<?php

class Database {
    private $db;

    public function __construct() {
        $this->db = new PDO(
            "mysql:host=localhost;dbname=perfume_ecommerce;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }

    public function connect(): PDO {
        return $this->db;
    }

    public function run_query(string $query, array $param = []) {
        $stmt = $this->db->prepare($query);
        $stmt->execute($param);
        return $stmt;
    }

    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    public function commit() {
        return $this->db->commit();
    }

    public function rollBack() {
        if ($this->db->inTransaction()) {
            return $this->db->rollBack();
        }

        return false;
    }
}