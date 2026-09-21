window.attendanceGps = ({ maxAccuracy }) => ({
    locating: false, submitting: false, located: false, gpsMessage: '', watchId: null, timer: null, cancelLocation: null,
    async locate() {
        if (this.locating || this.submitting) return false;
        this.located = false;
        if (!navigator.geolocation) { this.gpsMessage = 'Perangkat ini tidak mendukung GPS.'; return false; }
        this.locating = true;
        this.gpsMessage = 'Mencari lokasi GPS yang akurat...';
        let best = null;
        return new Promise(resolve => {
            const finish = (position, message) => {
                if (!this.locating) return;
                if (this.watchId !== null) navigator.geolocation.clearWatch(this.watchId);
                clearTimeout(this.timer);
                this.watchId = this.timer = this.cancelLocation = null;
                this.locating = false;
                this.located = !!position;
                this.gpsMessage = message;
                if (position) {
                    this.$wire.$set('latitude', position.coords.latitude, false);
                    this.$wire.$set('longitude', position.coords.longitude, false);
                    this.$wire.$set('accuracy', position.coords.accuracy, false);
                }
                resolve(!!position);
            };
            this.cancelLocation = () => finish(null, '');
            this.timer = setTimeout(() => finish(null, best
                ? `Akurasi GPS ±${Math.ceil(best.coords.accuracy)} m; batas sekolah ${maxAccuracy} m. Coba di area terbuka atau gunakan ponsel dengan GPS aktif, lalu tekan presensi lagi.`
                : 'Lokasi belum ditemukan. Aktifkan GPS dan izin lokasi, lalu coba lagi.'), 25000);
            this.watchId = navigator.geolocation.watchPosition(position => {
                if (!Number.isFinite(position.coords.accuracy) || position.coords.accuracy < 0) return;
                if (!best || position.coords.accuracy < best.coords.accuracy) best = position;
                if (best.coords.accuracy <= maxAccuracy) {
                    finish(best, `Lokasi siap. Akurasi GPS ±${Math.ceil(best.coords.accuracy)} m.`);
                } else {
                    this.gpsMessage = `Memperbaiki akurasi GPS: ±${Math.ceil(best.coords.accuracy)} m (batas ${maxAccuracy} m). Mohon tunggu...`;
                }
            }, error => {
                if (error.code === 1) finish(null, 'Izin lokasi ditolak. Izinkan akses lokasi di browser, lalu coba lagi.');
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 });
        });
    },
    async submit(action, needsLocation) {
        if (this.locating || this.submitting) return;
        if (needsLocation && !await this.locate()) return;
        this.submitting = true;
        try {
            await this.$wire[action]();
        } catch (error) {
            this.gpsMessage = 'Presensi belum berhasil dikirim. Periksa koneksi, muat ulang halaman jika sesi berakhir, lalu coba lagi.';
        } finally {
            this.submitting = false;
        }
    },
    cancel() {
        if (!this.locating) return;
        this.cancelLocation?.();
        this.gpsMessage = 'Pencarian GPS dibatalkan. Tekan presensi untuk mencoba kembali.';
    },
    destroy() { this.cancelLocation?.(); },
});
