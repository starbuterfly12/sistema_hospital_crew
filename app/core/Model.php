<?php

require_once __DIR__ . '/Database.php';

class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    protected function fetchOne(string $sql, array $params = []): array|false
    {
        $statement = $this->query($sql, $params);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->query($sql, $params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollBack(): bool
    {
        if (! $this->db->inTransaction()) {
            return false;
        }

        return $this->db->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }
}
