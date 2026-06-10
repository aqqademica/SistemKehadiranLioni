// ============================================================
// KehadiranApp — Main JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ---- Notification Dropdown ----
  const notifBtn = document.getElementById('notifBtn');
  const notifPanel = document.getElementById('notifPanel');
  if (notifBtn && notifPanel) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      notifPanel.classList.toggle('open');
    });
    document.addEventListener('click', () => notifPanel.classList.remove('open'));
    notifPanel.addEventListener('click', e => e.stopPropagation());
  }

  // ---- Mobile Sidebar Toggle ----
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ---- Flash message auto-dismiss ----
  const flash = document.getElementById('flashMsg');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.5s';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  }

  // ---- Poll unread notification count (every 30s) ----
  const notifBadge = document.getElementById('notifCount');
  if (notifBadge) {
    const pollNotif = () => {
      fetch('/KehadiranApp/public/api/notifications/unread-count')
        .then(r => r.json())
        .then(data => {
          if (data.count > 0) {
            notifBadge.textContent = data.count > 99 ? '99+' : data.count;
            notifBadge.style.display = 'block';
          } else {
            notifBadge.style.display = 'none';
          }
        }).catch(() => {});
    };
    pollNotif();
    setInterval(pollNotif, 30000);
  }

  // ---- Web Push Notification ----
  registerPushNotification();

  // ---- Confirm dialog helper ----
  window.confirmDelete = (form, msg = 'Yakin ingin menghapus data ini?') => {
    if (confirm(msg)) form.submit();
  };

  // ---- Active nav link highlight ----
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(link => {
    if (link.getAttribute('href') && currentPath.startsWith(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });
});

// ---- Web Push Registration ----
async function registerPushNotification() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
  try {
    const reg = await navigator.serviceWorker.register('/KehadiranApp/public/sw.js');
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') return;

    const vapidKey = document.head.querySelector('meta[name="vapid-key"]')?.content;
    if (!vapidKey) return;

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey)
    });

    await fetch('/KehadiranApp/public/api/push/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      body: JSON.stringify(sub)
    });
  } catch (e) {
    console.warn('Push notification tidak tersedia:', e.message);
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

function getCsrf() {
  return document.head.querySelector('meta[name="csrf-token"]')?.content || '';
}
