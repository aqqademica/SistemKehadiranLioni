<?php
// app/views/attendance/camera.php
?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-camera"></i> Absensi Kamera / Lapangan</div>
    </div>
    <div class="card-body">
        <form action="<?= APP_URL ?>/attendance/camera" method="POST" enctype="multipart/form-data" id="cameraForm">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div id="locationStatus" class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-map-marker-alt"></i> Mendapatkan lokasi...
            </div>

            <div class="form-group mb-3">
                <label>Foto Selfie (Wajib)</label>
                <input type="file" name="photo_selfie" class="form-control" accept="image/*" capture="user" required>
            </div>

            <div class="form-group mb-3">
                <label>Foto Bersama Rekan (Opsional)</label>
                <input type="file" name="photo_colleague" class="form-control" accept="image/*" capture="environment">
            </div>

            <div class="form-group mb-3">
                <label>Foto Bersama Client/Pelanggan (Opsional)</label>
                <input type="file" name="photo_client" class="form-control" accept="image/*" capture="environment">
            </div>

            <div class="form-group mb-4">
                <label>Catatan Lokasi / Kegiatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Misal: Kunjungan ke Toko Berkah"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                <i class="fas fa-paper-plane"></i> Kirim Absensi
            </button>
        </form>
    </div>
</div>

<script>
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
                document.getElementById('locationStatus').className = 'alert alert-success';
                document.getElementById('locationStatus').innerHTML = '<i class="fas fa-check-circle"></i> Lokasi terdeteksi: ' + position.coords.latitude.toFixed(4) + ', ' + position.coords.longitude.toFixed(4);
                document.getElementById('submitBtn').disabled = false;
            },
            function(error) {
                document.getElementById('locationStatus').className = 'alert alert-danger';
                document.getElementById('locationStatus').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Gagal mendapatkan lokasi. Harap izinkan akses lokasi.';
            }
        );
    } else {
        document.getElementById('locationStatus').className = 'alert alert-danger';
        document.getElementById('locationStatus').innerHTML = 'Browser Anda tidak mendukung geolokasi.';
    }
</script>
