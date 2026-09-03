<?php

namespace AppleBot;

use PDO;
use PDOException;
use PDOStatement;

/**
 * پوشش سادهٔ PDO با چند تابع کمکی.
 * قانون طلایی: هرگز ورودی کاربر را داخل رشتهٔ SQL نچسبانید؛ همیشه پارامتر.
 */
class Db
{
    private PDO $pdo;

    public function __construct(array $cfg)
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $cfg['host'] ?? 'localhost',
            $cfg['name'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );

        $this->pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function fetchValue(string $sql, array $params = []): mixed
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $ph   = array_map(static fn ($c) => ':' . $c, $cols);
        $sql  = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $ph) . ')';
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $c) {
            $set[] = '`' . $c . '` = :set_' . $c;
        }
        $params = [];
        foreach ($data as $k => $v) {
            $params['set_' . $k] = $v;
        }
        $params = array_merge($params, $whereParams);
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE ' . $where;
        return $this->run($sql, $params)->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
