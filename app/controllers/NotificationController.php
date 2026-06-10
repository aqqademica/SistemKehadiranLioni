<?php
class NotificationController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        
        // Tandai semua sebagai sudah dibaca
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
        
        $notifications = $this->db->query(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        )->fetchAll();

        $this->render('notifications.index', [
            'pageTitle'  => 'Notifikasi',
            'activePage' => '/KehadiranApp/public/notifications',
            'notifications' => $notifications
        ]);
    }
}
