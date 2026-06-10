<?php
class User extends Model
{
    protected string $table = 'users';

    public function findByUsername(string $username): ?array
    {
        return $this->db->query(
            "SELECT u.*, e.first_name, e.last_name, e.employee_code, e.department_id, e.position_id,
                    r.name AS role_name, r.display_name AS role_display
             FROM users u
             JOIN employees e ON e.id = u.employee_id
             JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? LIMIT 1",
            [$username]
        )->fetch() ?: null;
    }

    public function findWithEmployee(int $userId): ?array
    {
        return $this->db->query(
            "SELECT u.*, e.first_name, e.last_name, e.employee_code, e.base_salary,
                    e.department_id, e.position_id, e.join_date, e.phone, e.email,
                    r.name AS role_name, r.display_name AS role_display,
                    d.name AS department_name, p.name AS position_name
             FROM users u
             JOIN employees e ON e.id = u.employee_id
             JOIN roles r ON r.id = u.role_id
             JOIN departments d ON d.id = e.department_id
             JOIN positions p ON p.id = e.position_id
             WHERE u.id = ? LIMIT 1",
            [$userId]
        )->fetch() ?: null;
    }

    public function getAllWithEmployee(): array
    {
        return $this->db->query(
            "SELECT u.id, u.username, u.is_active, u.last_login, u.created_at,
                    e.first_name, e.last_name, e.employee_code, e.phone, e.email,
                    r.name AS role_name, r.display_name AS role_display,
                    d.name AS department_name, p.name AS position_name
             FROM users u
             JOIN employees e ON e.id = u.employee_id
             JOIN roles r ON r.id = u.role_id
             JOIN departments d ON d.id = e.department_id
             JOIN positions p ON p.id = e.position_id
             ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    public function createAccount(array $data): int
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);
        return $this->create($data);
    }

    public function resetPassword(int $userId, string $newPassword): void
    {
        $this->db->query(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($newPassword, PASSWORD_BCRYPT), $userId]
        );
    }

    public function generateUsername(string $employeeCode, string $firstName, string $lastName): string
    {
        $clean = fn($s) => preg_replace('/[^A-Za-z0-9]/', '', $s);
        return $employeeCode . '_' . $clean($firstName) . '_' . $clean($lastName);
    }
}
