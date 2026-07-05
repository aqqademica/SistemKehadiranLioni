<?php
/**
 * KehadiranApp — Database Seeder
 * Jalankan sekali dari browser: http://localhost/KehadiranApp/database/seed.php
 * atau via CLI: php seed.php
 */

require_once dirname(__DIR__) . '/config/app.php';

$host   = $_ENV['DB_HOST'] ?? 'localhost';
$user   = $_ENV['DB_USER'] ?? 'root';
$pass   = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'kehadiran_app';

echo "<pre style='font-family:monospace;padding:20px;'>\n";
echo "=== KehadiranApp Database Seeder ===\n\n";

try {
    // Koneksi database dengan fallback untuk hosting
    $connectedWithDb = false;
    try {
        // Coba koneksi tanpa database dulu (untuk local CREATE DATABASE)
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        // Fallback koneksi langsung ke database (untuk hosting yang membatasi hak akses root)
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $connectedWithDb = true;
    }

    // Jalankan schema part 1
    echo ">> Membuat database & tabel (Part 1)...\n";
    $sql1 = file_get_contents(__DIR__ . '/schema.sql');
    $sql1 = str_replace('`kehadiran_app`', '`' . $dbname . '`', $sql1);
    
    foreach (array_filter(array_map('trim', explode(';', $sql1))) as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $ex) {
                // Abaikan error CREATE DATABASE atau USE jika sudah terkoneksi langsung ke database hosting
                if (stripos($stmt, 'CREATE DATABASE') !== false || stripos($stmt, 'USE ') !== false) {
                    continue;
                }
                throw $ex;
            }
        }
    }
    echo "   [OK] Schema Part 1 selesai.\n";

    // Re-koneksi dengan database apabila belum dilakukan
    if (!$connectedWithDb) {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    // Jalankan schema part 2
    echo ">> Membuat tabel lanjutan (Part 2)...\n";
    $sql2 = file_get_contents(__DIR__ . '/schema_part2.sql');
    $sql2 = str_replace('`kehadiran_app`', '`' . $dbname . '`', $sql2);
    foreach (array_filter(array_map('trim', explode(';', $sql2))) as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $ex) {
                if (stripos($stmt, 'USE ') !== false) {
                    continue;
                }
                throw $ex;
            }
        }
    }
    echo "   [OK] Schema Part 2 selesai.\n\n";

    // ========================
    // SEED DATA
    // ========================
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Roles
    echo ">> Seeding roles...\n";
    $roles = [
        ['employee',        'Karyawan'],
        ['supervisor',      'Supervisor'],
        ['hrd_admin',       'Admin HRD'],
        ['hrd_manager',     'Manager HRD'],
        ['payroll_officer', 'Payroll Officer'],
    ];
    $pdo->exec("DELETE FROM roles");
    $stmt = $pdo->prepare("INSERT INTO roles (name, display_name) VALUES (?, ?)");
    foreach ($roles as $r) $stmt->execute($r);
    echo "   [OK] " . count($roles) . " roles.\n";

    // Departments
    echo ">> Seeding departments...\n";
    $pdo->exec("DELETE FROM departments");
    $deps = [
        ['Human Resources', 'HRD', 'Divisi Sumber Daya Manusia'],
        ['Finance',         'FIN', 'Divisi Keuangan'],
        ['Operations',      'OPS', 'Divisi Operasional'],
        ['IT',              'IT',  'Divisi Teknologi Informasi'],
        ['Marketing',       'MKT', 'Divisi Pemasaran'],
    ];
    $stmt = $pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?)");
    foreach ($deps as $d) $stmt->execute($d);
    echo "   [OK] " . count($deps) . " departments.\n";

    // Positions
    echo ">> Seeding positions...\n";
    $pdo->exec("DELETE FROM positions");
    $positions = [
        [1, 'Manager HRD',       'MGR-HRD', 'manager'],
        [1, 'Admin HRD',         'ADM-HRD', 'staff'],
        [2, 'Payroll Officer',   'PAY-OFF', 'staff'],
        [3, 'Supervisor Ops',    'SPV-OPS', 'supervisor'],
        [3, 'Staff Operasional', 'STF-OPS', 'staff'],
        [4, 'IT Support',        'IT-SUP',  'staff'],
        [5, 'Staff Marketing',   'STF-MKT', 'staff'],
    ];
    $stmt = $pdo->prepare("INSERT INTO positions (department_id, name, code, level) VALUES (?, ?, ?, ?)");
    foreach ($positions as $p) $stmt->execute($p);
    echo "   [OK] " . count($positions) . " positions.\n";

    // Shifts
    echo ">> Seeding shifts...\n";
    $pdo->exec("DELETE FROM shifts");
    $shifts = [
        ['Shift Pagi',   '08:00:00', '17:00:00', 0, 8.00],
        ['Shift Siang',  '13:00:00', '22:00:00', 0, 8.00],
        ['Shift Malam',  '22:00:00', '06:00:00', 1, 8.00],
        ['Shift Normal', '08:00:00', '17:00:00', 0, 8.00],
    ];
    $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, is_overnight, work_hours) VALUES (?, ?, ?, ?, ?)");
    foreach ($shifts as $s) $stmt->execute($s);
    echo "   [OK] " . count($shifts) . " shifts.\n";

    // System Settings
    echo ">> Seeding system_settings...\n";
    $pdo->exec("DELETE FROM system_settings");
    $settings = [
        ['late_tolerance_minutes',       '10',   'integer', 'Menit toleransi keterlambatan sebelum dihitung terlambat'],
        ['max_late_minutes',             '60',   'integer', 'Batas menit terlambat; lebih dari ini wajib Hourly Unpaid Leave'],
        ['hourly_rate_divisor',          '173',  'integer', 'Pembagi jam bulanan untuk menghitung hourly rate'],
        ['tidak_finger_window_hours',    '2',    'integer', 'Jam window pengajuan tidak finger setelah notifikasi'],
        ['supervisor_approval_window_h', '24',   'integer', 'Jam batas approval supervisor untuk tidak finger'],
        ['hrd_approval_window_days',     '26',   'integer', 'Hari batas verifikasi HRD untuk tidak finger'],
        ['sick_doc_deadline_days',       '2',    'integer', 'Hari batas upload surat sakit setelah tidak hadir'],
        ['max_paid_sick_days',           '0',    'integer', 'Maks hari sakit dibayar per tahun (0 = sesuai UU/tidak terbatas)'],
        ['late_rounding_mode',           'bracket', 'string', 'Mode rounding terlambat: bracket atau per_minute'],
        ['overtime_multiplier_regular',  '1.5',  'decimal', 'Multiplier lembur hari biasa'],
        ['overtime_multiplier_holiday',  '2.0',  'decimal', 'Multiplier lembur hari libur'],
        ['annual_leave_days',            '12',   'integer', 'Hak cuti tahunan dalam hari'],
        ['annual_leave_min_months',      '12',   'integer', 'Minimal masa kerja (bulan) untuk dapat cuti tahunan'],
    ];
    $stmt = $pdo->prepare("INSERT INTO system_settings (`key`, `value`, `type`, `description`) VALUES (?, ?, ?, ?)");
    foreach ($settings as $s) $stmt->execute($s);
    echo "   [OK] " . count($settings) . " settings.\n";

    // Late Deduction Rules (default bracket)
    echo ">> Seeding late_deduction_rules...\n";
    $pdo->exec("DELETE FROM late_deduction_rules");
    $lateRules = [
        [1,  15, 10.00, 'Terlambat 1-15 menit: potong 10% hourly rate'],
        [16, 30, 20.00, 'Terlambat 16-30 menit: potong 20% hourly rate'],
        [31, 60, 50.00, 'Terlambat 31-60 menit: potong 50% hourly rate'],
    ];
    $stmt = $pdo->prepare("INSERT INTO late_deduction_rules (min_minutes, max_minutes, deduction_percent, description) VALUES (?, ?, ?, ?)");
    foreach ($lateRules as $r) $stmt->execute($r);
    echo "   [OK] " . count($lateRules) . " late deduction rules.\n";

    // Health Partners
    echo ">> Seeding health_partners...\n";
    $pdo->exec("DELETE FROM health_partners");
    $partners = [
        ['RS Umum Daerah Setempat',   'rumah_sakit', 1],
        ['RS Swasta Mitra Utama',      'rumah_sakit', 1],
        ['Klinik Pratama BPJS Utama',  'klinik',      1],
        ['Klinik Keluarga Sehat',      'klinik',      1],
        ['Puskesmas Kecamatan',        'klinik',      1],
    ];
    $stmt = $pdo->prepare("INSERT INTO health_partners (name, type, is_bpjs_affiliated) VALUES (?, ?, ?)");
    foreach ($partners as $p) $stmt->execute($p);
    echo "   [OK] " . count($partners) . " health partners.\n";

    // Payroll Components
    echo ">> Seeding payroll_components...\n";
    $pdo->exec("DELETE FROM payroll_components");
    $components = [
        ['GAPOK',      'Gaji Pokok',              'earning',   1, 0],
        ['TUNJ_TRANS', 'Tunjangan Transportasi',  'earning',   1, 0],
        ['TUNJ_MAKAN', 'Tunjangan Makan',          'earning',   1, 0],
        ['TUNJ_JAB',   'Tunjangan Jabatan',        'earning',   1, 0],
        ['TUNJ_VAR',   'Tunjangan Variabel',       'earning',   0, 0],
        ['LEMBUR',     'Upah Lembur',              'earning',   0, 1],
        ['BONUS_JAB',  'Bonus Jabatan',            'bonus',     0, 0],
        ['POT_TELAT',  'Potongan Keterlambatan',   'deduction', 0, 0],
        ['POT_UNPAID', 'Potongan Unpaid Leave',    'deduction', 0, 0],
        ['POT_HOURLY', 'Potongan Hourly Leave',    'deduction', 0, 0],
        ['POT_ALPHA',  'Potongan Alpha',            'deduction', 0, 0],
        ['POT_PINJAM', 'Potongan Pinjaman',        'deduction', 0, 0],
        ['PPH21',      'PPh 21',                   'deduction', 0, 0],
    ];
    $stmt = $pdo->prepare("INSERT INTO payroll_components (code, name, type, is_fixed, is_taxable) VALUES (?, ?, ?, ?, ?)");
    foreach ($components as $c) $stmt->execute($c);
    echo "   [OK] " . count($components) . " payroll components.\n";

    // National Holidays 2026
    echo ">> Seeding national_holidays (2026)...\n";
    $pdo->exec("DELETE FROM national_holidays WHERE YEAR(date) = 2026");
    $holidays = [
        ['2026-01-01', 'Tahun Baru 2026'],
        ['2026-01-29', 'Tahun Baru Imlek 2577'],
        ['2026-03-20', 'Hari Raya Nyepi'],
        ['2026-03-31', 'Wafat Isa Al Masih'],
        ['2026-04-02', 'Isra Miraj Nabi Muhammad SAW'],
        ['2026-05-01', 'Hari Buruh Internasional'],
        ['2026-05-14', 'Kenaikan Isa Al Masih'],
        ['2026-06-01', 'Hari Lahir Pancasila'],
        ['2026-08-17', 'Hari Kemerdekaan RI'],
        ['2026-12-25', 'Hari Raya Natal'],
    ];
    $stmt = $pdo->prepare("INSERT INTO national_holidays (date, name) VALUES (?, ?)");
    foreach ($holidays as $h) $stmt->execute($h);
    echo "   [OK] " . count($holidays) . " holidays.\n";

    // Employees
    echo ">> Seeding employees...\n";
    $pdo->exec("DELETE FROM employee_salary_components");
    $pdo->exec("DELETE FROM employee_shifts");
    $pdo->exec("DELETE FROM employee_leave_balances");
    $pdo->exec("DELETE FROM users");
    $pdo->exec("DELETE FROM employees");

    $employees = [
        // code,      fname,    lname,      dept, pos, join_date,    salary
        ['MGR001', 'Budi',    'Santoso',    1, 1, '2019-03-01', 12000000],
        ['HRD001', 'Siti',    'Rahayu',     1, 2, '2020-06-15', 7000000],
        ['PAY001', 'Agus',    'Wijaya',     2, 3, '2021-01-10', 7500000],
        ['SPV001', 'Dian',    'Pratama',    3, 4, '2020-08-20', 9000000],
        ['SPV002', 'Rina',    'Kurniawan',  3, 4, '2021-03-05', 8500000],
        ['EMP001', 'Ahmad',   'Fauzi',      3, 5, '2022-07-01', 5500000],
        ['EMP002', 'Dewi',    'Lestari',    5, 7, '2022-09-15', 5000000],
        ['EMP003', 'Hendra',  'Gunawan',    4, 6, '2023-01-03', 6000000],
        ['EMP004', 'Maya',    'Indah',      1, 2, '2023-04-01', 5500000],
        ['EMP005', 'Rizky',   'Firmansyah', 3, 5, '2023-06-01', 5500000],
    ];
    $stmtE = $pdo->prepare("INSERT INTO employees (employee_code, first_name, last_name, department_id, position_id, join_date, base_salary) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($employees as $e) $stmtE->execute($e);
    echo "   [OK] " . count($employees) . " employees.\n";

    // Users (dengan default password bcrypt)
    echo ">> Seeding users & default credentials...\n";
    $adminPass    = password_hash('123',     PASSWORD_BCRYPT);
    $employeePass = password_hash('123',  PASSWORD_BCRYPT);

    // role_id: hrd_manager=4, hrd_admin=3, payroll_officer=5, supervisor=2, employee=1
    $users = [
        // emp_id, username,                       pass,          role_id
        [1, 'MGR001_Budi_Santoso',     $adminPass,    4],
        [2, 'HRD001_Siti_Rahayu',      $adminPass,    3],
        [3, 'PAY001_Agus_Wijaya',       $adminPass,    5],
        [4, 'SPV001_Dian_Pratama',      $adminPass,    2],
        [5, 'SPV002_Rina_Kurniawan',    $adminPass,    2],
        [6, 'EMP001_Ahmad_Fauzi',       $employeePass, 1],
        [7, 'EMP002_Dewi_Lestari',      $employeePass, 1],
        [8, 'EMP003_Hendra_Gunawan',    $employeePass, 1],
        [9, 'EMP004_Maya_Indah',        $employeePass, 1],
        [10,'EMP005_Rizky_Firmansyah',  $employeePass, 1],
    ];
    $stmtU = $pdo->prepare("INSERT INTO users (employee_id, username, password_hash, role_id) VALUES (?, ?, ?, ?)");
    foreach ($users as $u) $stmtU->execute($u);
    echo "   [OK] " . count($users) . " users.\n";

    // Employee Shifts (semua shift pagi dulu)
    echo ">> Seeding employee_shifts...\n";
    $stmtS = $pdo->prepare("INSERT INTO employee_shifts (employee_id, shift_id, effective_date) VALUES (?, 1, '2024-01-01')");
    for ($i = 1; $i <= 10; $i++) $stmtS->execute([$i]);
    echo "   [OK] 10 employee_shifts.\n";

    // Leave Balances 2026
    echo ">> Seeding employee_leave_balances (2026)...\n";
    $stmtL = $pdo->prepare("INSERT INTO employee_leave_balances (employee_id, year, total_days, used_days, remaining_days) VALUES (?, 2026, 12, 0, 12)");
    for ($i = 1; $i <= 10; $i++) $stmtL->execute([$i]);
    echo "   [OK] 10 leave balances.\n";

    // Salary Components per Employee (Gaji Pokok + Tunjangan)
    echo ">> Seeding employee_salary_components...\n";
    $today = date('Y-m-d');
    $stmtC = $pdo->prepare("INSERT INTO employee_salary_components (employee_id, component_id, amount, effective_date) VALUES (?, ?, ?, ?)");
    $allowances = [
        1 => [500000, 400000, 2000000], // transport, makan, jabatan
        2 => [300000, 300000, 500000],
        3 => [300000, 300000, 500000],
        4 => [400000, 350000, 1000000],
        5 => [400000, 350000, 1000000],
        6 => [250000, 300000, 0],
        7 => [250000, 300000, 0],
        8 => [250000, 300000, 0],
        9 => [250000, 300000, 0],
        10 => [250000, 300000, 0],
    ];
    foreach ($employees as $idx => $emp) {
        $empId = $idx + 1;
        // Gaji Pokok (component_id=1)
        $stmtC->execute([$empId, 1, $emp[6], $today]);
        // Tunjangan Transport (component_id=2)
        $stmtC->execute([$empId, 2, $allowances[$empId][0], $today]);
        // Tunjangan Makan (component_id=3)
        $stmtC->execute([$empId, 3, $allowances[$empId][1], $today]);
        // Tunjangan Jabatan jika ada (component_id=4)
        if ($allowances[$empId][2] > 0) {
            $stmtC->execute([$empId, 4, $allowances[$empId][2], $today]);
        }
    }
    echo "   [OK] Salary components seeded.\n";

    // Dummy Finger Logs (bulan ini)
    echo ">> Seeding finger_logs (dummy — bulan ini)...\n";
    $year  = date('Y');
    $month = date('m');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $count = 0;
    $stmtF = $pdo->prepare("INSERT INTO finger_logs (employee_id, log_date, timestamp_in, timestamp_out, device_id, location, raw_status) VALUES (?, ?, ?, ?, 'FP-DEVICE-001', 'Kantor Utama', ?)");

    for ($day = 1; $day <= min($daysInMonth, (int)date('d')); $day++) {
        $date    = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dayOfWeek = date('N', strtotime($date)); // 1=Mon, 7=Sun
        if ($dayOfWeek >= 6) continue; // Skip weekend

        foreach ($employees as $idx => $_) {
            $empId = $idx + 1;
            // Variasi dummy: 90% hadir, 5% terlambat, 5% missing
            $rand = rand(1, 100);
            if ($rand <= 5) {
                // Missing both
                $stmtF->execute([$empId, $date, null, null, 'missing_both']);
            } elseif ($rand <= 10) {
                // Terlambat (08:15 - 08:45)
                $lateMin   = rand(15, 45);
                $timeIn    = sprintf('08:%02d:00', $lateMin);
                $timeOut   = '17:' . sprintf('%02d', rand(0,30)) . ':00';
                $stmtF->execute([$empId, $date, "$date $timeIn", "$date $timeOut", 'valid']);
            } else {
                // Hadir normal (08:00 - 08:09)
                $minIn  = rand(0, 9);
                $minOut = rand(0, 30);
                $timeIn  = sprintf('08:%02d:00', $minIn);
                $timeOut = sprintf('17:%02d:00', $minOut);
                $stmtF->execute([$empId, $date, "$date $timeIn", "$date $timeOut", 'valid']);
            }
            $count++;
        }
    }
    echo "   [OK] {$count} finger log records.\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n=== Seeding selesai! ===\n\n";
    echo "Default Credentials:\n";
    echo "  Manager HRD  : MGR001_Budi_Santoso    / 123\n";
    echo "  Admin HRD    : HRD001_Siti_Rahayu      / 123\n";
    echo "  Payroll      : PAY001_Agus_Wijaya       / 123\n";
    echo "  Supervisor   : SPV001_Dian_Pratama      / 123\n";
    echo "  Karyawan     : EMP001_Ahmad_Fauzi       / 123\n";
    echo "\nAkses aplikasi: <a href='http://localhost/KehadiranApp/public/'>http://localhost/KehadiranApp/public/</a>\n";

} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}

echo "</pre>\n";
