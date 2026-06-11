<?php
// app/views/notifications/index.php
?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-bell"></i> Kotak Masuk Notifikasi</div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($notifications)): ?>
            <div style="padding:40px; text-align:center; color:var(--text-muted)">
                <i class="fas fa-check-circle" style="font-size:40px;opacity:0.2;display:block;margin-bottom:12px"></i>
                Tidak ada notifikasi baru.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column;">
                <?php foreach ($notifications as $notif): ?>
                    <?php
                        $link = '#';
                        if ($notif['related_type'] === 'leave') {
                            $link = APP_URL . '/requests/leave';
                        } elseif ($notif['related_type'] === 'tidak_finger') {
                            $link = APP_URL . '/requests/tidak-finger';
                        } elseif ($notif['related_type'] === 'overtime') {
                            $link = APP_URL . '/requests/overtime';
                        } elseif ($notif['related_type'] === 'payroll') {
                            $link = APP_URL . '/my-salary';
                        } elseif ($notif['related_type'] === 'warning_letter') {
                            $link = APP_URL . '/dashboard';
                        }
                    ?>
                    <a href="<?= $link ?>" style="text-decoration:none; padding: 15px 20px; border-bottom: 1px solid var(--border-color); display:flex; gap:15px; align-items:flex-start; background: <?= $notif['is_read'] ? 'transparent' : '#f8f9fa' ?>; transition: background 0.2s;">
                        <?php
                            $iconColor = match($notif['type']) {
                                'success' => 'var(--success-color)',
                                'danger'  => 'var(--danger-color)',
                                'warning' => 'var(--warning-color)',
                                default   => 'var(--primary-color)'
                            };
                            $iconClass = match($notif['type']) {
                                'success' => 'fa-check-circle',
                                'danger'  => 'fa-times-circle',
                                'warning' => 'fa-exclamation-circle',
                                default   => 'fa-info-circle'
                            };
                        ?>
                        <div style="color: <?= $iconColor ?>; font-size: 24px; padding-top: 2px;">
                            <i class="fas <?= $iconClass ?>"></i>
                        </div>
                        <div style="flex:1">
                            <div style="font-weight: 600; margin-bottom: 5px; color: var(--text-color);">
                                <?= htmlspecialchars($notif['title']) ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.95em; line-height: 1.5;">
                                <?= htmlspecialchars($notif['message']) ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8em; margin-top: 8px;">
                                <i class="fas fa-clock"></i> <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
