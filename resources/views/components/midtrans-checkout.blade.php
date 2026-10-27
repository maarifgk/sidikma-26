<div
    x-data="{
        checkoutError: '',
        orderId: null,
        async openSnap(data) {
            this.checkoutError = '';
            this.orderId = data.orderId;
            try {
                const url = data.production ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
                let script = document.getElementById('sidikma-midtrans-snap');
                if (script &amp;&amp; (script.src !== url || script.dataset.clientKey !== data.clientKey)) {
                    throw new Error('Konfigurasi Midtrans berubah. Muat ulang halaman.');
                }
                if (!window.snap) {
                    if (!window.sidikmaSnapLoading) {
                        window.sidikmaSnapLoading = new Promise((resolve, reject) => {
                            script = document.createElement('script');
                            script.id = 'sidikma-midtrans-snap';
                            script.src = url;
                            script.dataset.clientKey = data.clientKey;
                            script.onload = resolve;
                            script.onerror = () => {
                                script.remove();
                                window.sidikmaSnapLoading = null;
                                reject(new Error('Midtrans tidak dapat dimuat. Periksa koneksi dan coba kembali.'));
                            };
                            document.head.appendChild(script);
                        });
                    }
                    await window.sidikmaSnapLoading;
                }
                window.snap.pay(data.token, {
                    onSuccess: () => this.$wire.refreshOnlinePayment(data.orderId),
                    onPending: () => this.$wire.refreshOnlinePayment(data.orderId),
                    onError: () => { this.checkoutError = 'Pembayaran belum berhasil. Periksa status sebelum mencoba kembali.'; },
                    onClose: () => this.$wire.refreshOnlinePayment(data.orderId),
                });
            } catch (error) {
                this.checkoutError = error.message || 'Checkout belum dapat dibuka. Silakan coba kembali.';
            }
        },
    }"
    x-on:midtrans-checkout.window="openSnap($event.detail)"
>
    <p x-cloak x-show="checkoutError" x-text="checkoutError" role="alert" class="ui-error"></p>
    <div x-cloak x-show="orderId">
        <button type="button" class="ui-btn ui-btn-secondary" x-on:click="$wire.refreshOnlinePayment(orderId)" wire:loading.attr="disabled">Periksa Status Pembayaran</button>
    </div>
</div>
