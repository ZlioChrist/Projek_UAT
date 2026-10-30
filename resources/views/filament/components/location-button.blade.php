<div class="w-full">
    <button type="button" x-data="{ loading: false }" x-on:click.prevent="
                loading = true;
                if (!navigator.geolocation) {
                    alert('Browser kamu tidak mendukung fitur lokasi.');
                    loading = false;
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        $wire.set('data.latitude', position.coords.latitude);
                        $wire.set('data.longitude', position.coords.longitude);
                        
                        // Visual feedback
                        new Notification('Lokasi Ditemukan!', { body: 'Koordinat berhasil diisi.' });
                        loading = false;
                    },
                    (error) => {
                        let msg = 'Gagal mengambil lokasi.';
                        if (error.code === 1) msg = 'Izin lokasi ditolak via Browser. Cek icon gembok/lokasi di address bar!';
                        else if (error.code === 2) msg = 'Posisi tidak tersedia (GPS mati/sinyal lemah).';
                        else if (error.code === 3) msg = 'Timeout (kelamaan nunggu sinyal).';
                        
                        alert(msg);
                        console.error(error);
                        loading = false;
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            "
        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 w-full"
        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">

        <span x-show="!loading" class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Ambil Lokasi Saya (Otomatis)
        </span>

        <span x-show="loading" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            Sedang mencari koordinat...
        </span>
    </button>
</div>