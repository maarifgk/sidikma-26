<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(17rem, 24rem)); gap: 1rem; margin-bottom: 1.5rem;">
    @foreach ($products as $product)
        <article class="fi-section" style="overflow: hidden; text-align: center;">
            @if ($product['imageUrl'])
                <img
                    src="{{ $product['imageUrl'] }}"
                    alt="{{ $product['name'] }}"
                    style="display: block; width: 100%; height: 12.5rem; object-fit: cover;"
                >
            @else
                <div style="display: grid; place-items: center; width: 100%; height: 12.5rem; background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 12px, #e5e7eb 12px, #e5e7eb 24px); color: #4b5563;">
                    <div>
                        <strong style="display: block; font-size: 1.1rem;">Template Gambar Batik</strong>
                        <span>Klik Edit untuk mengunggah gambar</span>
                    </div>
                </div>
            @endif

            <div style="display: grid; gap: 0.45rem; padding: 1rem;">
                <strong style="font-size: 1.05rem;">{{ $product['name'] }}</strong>
                <span>Stok: {{ number_format($product['stock'], 0, ',', '.') }}</span>
                <span style="font-size: 1.15rem; font-weight: 700;">
                    Rp {{ number_format($product['price'], 0, ',', '.') }}
                    <small style="font-weight: 500;">/ {{ $product['sizeLabel'] }}</small>
                </span>
                <div style="display: flex; justify-content: center; gap: 0.6rem; margin-top: 0.5rem;">
                    @if ($product['editUrl'])
                        <a href="{{ $product['editUrl'] }}" class="fi-btn fi-size-sm fi-color-gray">Edit</a>
                    @endif
                    <a href="{{ $product['orderUrl'] }}" class="fi-btn fi-size-md fi-color-primary">Pesan Sekarang</a>
                </div>
            </div>
        </article>
    @endforeach
</div>
