<?php
// ============================================================
// Base Model
// ============================================================

class Model
{
    protected Database $db;
    protected string $table  = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Ambil semua record
    public function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        return $this->db->query(
            "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$dir}"
        )->fetchAll();
    }

    // Cari berdasarkan primary key
    public function find(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        )->fetch();
        return $result ?: null;
    }

    // Cari dengan kondisi
    public function where(array $conditions, string $operator = 'AND'): array
    {
        $clauses = [];
        $values  = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "`{$col}` = ?";
            $values[]  = $val;
        }
        $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(" {$operator} ", $clauses);
        return $this->db->query($sql, $values)->fetchAll();
    }

    // Cari satu record dengan kondisi
    public function findWhere(array $conditions, string $operator = 'AND'): ?array
    {
        $clauses = [];
        $values  = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "`{$col}` = ?";
            $values[]  = $val;
        }
        $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(" {$operator} ", $clauses) . " LIMIT 1";
        $result = $this->db->query($sql, $values)->fetch();
        return $result ?: null;
    }

    // Insert record
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->db->query(
            "INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$placeholders})",
            array_values($data)
        );
        return (int) $this->db->lastInsertId();
    }

    // Update record
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $this->db->query(
            "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = ?",
            $values
        );
        return true;
    }

    // Delete record
    public function delete(int $id): bool
    {
        $this->db->query(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
        return true;
    }

    // Count records
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return (int) $this->db->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
        }
        $clauses = [];
        $values  = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "`{$col}` = ?";
            $values[]  = $val;
        }
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE " . implode(' AND ', $clauses);
        return (int) $this->db->query($sql, $values)->fetchColumn();
    }

    // Raw query helper
    public function raw(string $sql, array $params = []): PDOStatement
    {
        return $this->db->query($sql, $params);
    }

    // Paginate
    public function paginate(int $page = 1, int $perPage = 20, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = $this->count($conditions);

        if (empty($conditions)) {
            $rows = $this->db->query(
                "SELECT * FROM `{$this->table}` LIMIT ? OFFSET ?",
                [$perPage, $offset]
            )->fetchAll();
        } else {
            $clauses = [];
            $values  = [];
            foreach ($conditions as $col => $val) {
                $clauses[] = "`{$col}` = ?";
                $values[]  = $val;
            }
            $values[] = $perPage;
            $values[] = $offset;
            $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $clauses) . " LIMIT ? OFFSET ?";
            $rows = $this->db->query($sql, $values)->fetchAll();
        }

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }
}
