window.attendanceGps = ({ maxAccuracy }) => ({
    locating: false, submitting: false, located: false, gpsMessage: '', watchId: null, timer: null, statusTimer: null, cancelLocation: null, searchToken: 0,
    async locate() {
        if (this.locating || this.submitting) return false;
        this.located = false;
        if (!navigator.geolocation) { this.gpsMessage = 'Perangkat ini tidak mendukung GPS.'; return false; }
        this.locating = true;
        this.gpsMessage = 'Mencari lokasi GPS...';
        const searchToken = ++this.searchToken;
        let best = null;
        return new Promise(resolve => {
            const finish = (position, message) => {
                if (!this.locating || searchToken !== this.searchToken) return;
                if (this.watchId !== null) navigator.geolocation.clearWatch(this.watchId);
                clearTimeout(this.timer);
                clearTimeout(this.statusTimer);
                this.watchId = this.timer = this.statusTimer = this.cancelLocation = null;
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
            this.statusTimer = setTimeout(() => {
                if (this.locating && searchToken === this.searchToken) {
                    this.gpsMessage = 'GPS masih mencari lokasi. Pastikan lokasi perangkat aktif dan coba berada di area terbuka.';
                }
            }, 5000);
            this.timer = setTimeout(() => finish(null, best
                ? `Akurasi lokasi ${Math.ceil(best.coords.accuracy)} meter, belum memenuhi batas ${maxAccuracy} meter.`
                : 'Lokasi belum ditemukan. Periksa GPS dan izin lokasi, lalu tekan Coba Lagi.'), 15000);
            this.watchId = navigator.geolocation.watchPosition(position => {
                if (!this.locating || searchToken !== this.searchToken) return;
                if (!Number.isFinite(position.coords.accuracy) || position.coords.accuracy < 0) return;
                if (!best || position.coords.accuracy < best.coords.accuracy) best = position;
                if (best.coords.accuracy <= maxAccuracy) {
                    finish(best, `GPS ditemukan. Akurasi: ${Math.ceil(best.coords.accuracy)} meter.`);
                } else {
                    this.gpsMessage = `Akurasi lokasi ${Math.ceil(best.coords.accuracy)} meter, belum memenuhi batas ${maxAccuracy} meter.`;
                }
            }, error => {
                if (!this.locating || searchToken !== this.searchToken) return;
                if (error.code === 1) {
                    finish(null, 'Izin lokasi ditolak. Izinkan akses lokasi di browser, lalu coba lagi.');
                } else if (error.code === 2) {
                    finish(null, 'Lokasi belum tersedia. Aktifkan GPS atau coba di area dengan sinyal lebih baik.');
                } else if (error.code === 3) {
                    finish(null, 'GPS terlalu lama merespons. Aktifkan lokasi dengan akurasi tinggi, lalu coba lagi.');
                } else {
                    finish(null, 'Lokasi tidak dapat ditemukan. Periksa izin GPS dan pengaturan lokasi perangkat, lalu coba lagi.');
                }
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
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
        this.gpsMessage = 'Pencarian GPS dibatalkan. Tekan Coba Lagi untuk mencoba kembali.';
    },
    destroy() { this.cancelLocation?.(); this.searchToken++; },
});
