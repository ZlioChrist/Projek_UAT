<x-filament-panels::page>
    <style>
        .admin-profile-photo-editor[hidden] { display: none; }
        .admin-profile-photo-editor { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 1rem; background: rgba(15, 12, 10, .72); backdrop-filter: blur(4px); }
        .admin-profile-photo-dialog { width: min(32rem, 100%); max-height: min(90vh, 48rem); overflow: auto; padding: 1.25rem; border: 1px solid rgb(120 113 108 / .3); border-radius: 1rem; background: white; color: #292524; box-shadow: 0 24px 60px rgb(0 0 0 / .28); }
        .dark .admin-profile-photo-dialog { border-color: #44403c; background: #1c1917; color: #f5f5f4; }
        .admin-profile-photo-dialog-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .admin-profile-photo-dialog-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; }
        .admin-profile-photo-dialog-header p { margin: .25rem 0 0; color: #78716c; font-size: .8rem; }
        .dark .admin-profile-photo-dialog-header p { color: #a8a29e; }
        .admin-profile-photo-close { display: grid; width: 2rem; height: 2rem; place-items: center; border: 1px solid #e7e5e4; border-radius: 999px; background: transparent; font-size: 1.2rem; }
        .dark .admin-profile-photo-close { border-color: #57534e; }
        .admin-profile-photo-crop { display: block; width: min(100%, 22rem); aspect-ratio: 1; margin: 0 auto; border: 1px solid #d6d3d1; border-radius: .75rem; background: #f5f5f4; cursor: grab; touch-action: none; }
        .admin-profile-photo-crop:active { cursor: grabbing; }
        .dark .admin-profile-photo-crop { border-color: #57534e; background: #292524; }
        .admin-profile-photo-zoom { display: grid; gap: .45rem; margin-top: 1rem; }
        .admin-profile-photo-zoom-label { display: flex; justify-content: space-between; gap: .75rem; font-size: .8rem; font-weight: 600; }
        .admin-profile-photo-zoom input { width: 100%; accent-color: #805b3d; }
        .admin-profile-photo-dialog-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1rem; }
        .admin-profile-photo-dialog-actions button { min-height: 2.5rem; padding: .55rem .9rem; border: 1px solid #d6d3d1; border-radius: .6rem; font-size: .85rem; font-weight: 600; }
        .admin-profile-photo-dialog-actions [data-apply-crop] { border-color: #805b3d; background: #805b3d; color: white; }
        .admin-profile-photo-preview { width: 4rem; height: 4rem; border-radius: 999px; object-fit: cover; }
    </style>

    @if (session('profile_photo_status'))
        <div role="status" class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300">
            {{ session('profile_photo_status') }}
        </div>
    @endif

    @error('profile_photo')
        <div role="alert" class="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300">
            {{ $message }}
        </div>
    @enderror

    <form
        id="admin-profile-photo-form"
        method="POST"
        action="{{ route('admin.profile-photo.update') }}"
        enctype="multipart/form-data"
        class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
    >
        @csrf
        @method('PATCH')
        <div class="flex flex-wrap items-center gap-4">
            @if (auth()->user()?->profile_photo_path)
                <img
                    id="admin-profile-photo-preview"
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}"
                    alt="Foto profil saat ini"
                    class="admin-profile-photo-preview"
                >
            @else
                <img id="admin-profile-photo-preview" class="admin-profile-photo-preview" alt="Pratinjau foto profil" hidden>
                <span id="admin-profile-photo-initial" class="grid h-16 w-16 place-items-center rounded-full bg-primary-100 text-lg font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-200">
                    {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? '', 0, 1)) }}
                </span>
            @endif
            <label class="min-w-[14rem] flex-1 space-y-2 text-sm font-medium text-gray-950 dark:text-white">
                <span>Foto profil</span>
                <input
                    id="admin-profile-photo-input"
                    type="file"
                    name="profile_photo"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-950 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:font-medium dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:file:bg-gray-700"
                >
                <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">JPG, PNG, atau WebP. Maksimal 5 MB. Atur posisi dan zoom sebelum disimpan; hasilnya 256 × 256 piksel.</span>
            </label>
            <x-filament::button type="submit" id="admin-profile-photo-submit">
                Simpan foto
            </x-filament::button>
        </div>
        <p id="admin-profile-photo-status" class="text-xs text-gray-500 dark:text-gray-400" aria-live="polite"></p>
    </form>

    <div id="admin-profile-photo-editor" class="admin-profile-photo-editor" hidden>
        <section class="admin-profile-photo-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-profile-photo-title">
            <header class="admin-profile-photo-dialog-header">
                <div>
                    <h2 id="admin-profile-photo-title">Atur foto profil</h2>
                    <p>Geser foto untuk memilih bagian yang ingin ditampilkan, lalu atur ukurannya.</p>
                </div>
                <button type="button" class="admin-profile-photo-close" data-cancel-crop aria-label="Tutup editor foto">×</button>
            </header>
            <canvas id="admin-profile-photo-crop" class="admin-profile-photo-crop" width="256" height="256" aria-label="Pratinjau potongan foto profil"></canvas>
            <label class="admin-profile-photo-zoom" for="admin-profile-photo-zoom">
                <span class="admin-profile-photo-zoom-label">
                    <span>Ukuran foto</span>
                    <output id="admin-profile-photo-zoom-value" for="admin-profile-photo-zoom">100%</output>
                </span>
                <input id="admin-profile-photo-zoom" type="range" min="1" max="3" step="0.01" value="1">
            </label>
            <footer class="admin-profile-photo-dialog-actions">
                <button type="button" data-cancel-crop>Batal</button>
                <button type="button" data-apply-crop>Gunakan foto ini</button>
            </footer>
        </section>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Simpan perubahan profil</x-filament::button>
    </form>

    <script>
        (() => {
            const form = document.getElementById('admin-profile-photo-form');
            const input = form?.querySelector('input[name="profile_photo"]');
            const submitButton = document.getElementById('admin-profile-photo-submit');
            const status = document.getElementById('admin-profile-photo-status');
            const editor = document.getElementById('admin-profile-photo-editor');
            const canvas = document.getElementById('admin-profile-photo-crop');
            const zoomInput = document.getElementById('admin-profile-photo-zoom');
            const zoomValue = document.getElementById('admin-profile-photo-zoom-value');
            const preview = document.getElementById('admin-profile-photo-preview');
            const initial = document.getElementById('admin-profile-photo-initial');

            if (!form || !input || !submitButton || !status || !editor || !canvas || !zoomInput || !zoomValue || !preview) {
                return;
            }

            const context = canvas.getContext('2d');
            if (!context) {
                status.textContent = 'Editor foto tidak dapat dibuka di browser ini.';
                return;
            }

            const image = new Image();
            const pointer = { active: false, x: 0, y: 0, offsetX: 0, offsetY: 0 };
            const originalPreviewSrc = preview.getAttribute('src') || '';
            const originalPreviewHidden = preview.hidden;
            let currentObjectUrl = null;
            let croppedPreviewUrl = null;
            let photoReady = false;

            const drawCrop = () => {
                if (!image.naturalWidth || !image.naturalHeight) return;

                const zoom = Number(zoomInput.value);
                const cropSize = Math.min(image.naturalWidth, image.naturalHeight) / zoom;
                const maxOffsetX = Math.max(0, (image.naturalWidth - cropSize) / 2);
                const maxOffsetY = Math.max(0, (image.naturalHeight - cropSize) / 2);
                const startX = (image.naturalWidth - cropSize) / 2 + pointer.offsetX * maxOffsetX;
                const startY = (image.naturalHeight - cropSize) / 2 + pointer.offsetY * maxOffsetY;

                context.clearRect(0, 0, canvas.width, canvas.height);
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, startX, startY, cropSize, cropSize, 0, 0, canvas.width, canvas.height);
                zoomValue.value = `${Math.round(zoom * 100)}%`;
                zoomValue.textContent = zoomValue.value;
            };

            const closeEditor = () => {
                editor.hidden = true;
                pointer.active = false;
                image.removeAttribute('src');
                if (currentObjectUrl) {
                    URL.revokeObjectURL(currentObjectUrl);
                    currentObjectUrl = null;
                }
            };

            const cancelCrop = () => {
                input.value = '';
                photoReady = false;
                if (croppedPreviewUrl) {
                    URL.revokeObjectURL(croppedPreviewUrl);
                    croppedPreviewUrl = null;
                }
                preview.src = originalPreviewSrc;
                preview.hidden = originalPreviewHidden;
                if (initial) initial.hidden = !originalPreviewHidden;
                closeEditor();
                status.textContent = 'Pengaturan foto dibatalkan.';
            };

            input.addEventListener('change', () => {
                const [file] = input.files || [];
                if (!file) return;

                photoReady = false;
                status.textContent = 'Atur posisi dan ukuran foto sebelum menyimpan.';
                if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = URL.createObjectURL(file);
                image.onload = () => {
                    pointer.offsetX = 0;
                    pointer.offsetY = 0;
                    zoomInput.value = '1';
                    drawCrop();
                    editor.hidden = false;
                    editor.querySelector('[data-cancel-crop]')?.focus();
                };
                image.onerror = () => {
                    input.value = '';
                    status.textContent = 'Foto tidak dapat dibuka. Pilih file JPG, PNG, atau WebP yang valid.';
                };
                image.src = currentObjectUrl;
            });

            zoomInput.addEventListener('input', drawCrop);

            canvas.addEventListener('pointerdown', (event) => {
                pointer.active = true;
                pointer.x = event.clientX;
                pointer.y = event.clientY;
                canvas.setPointerCapture(event.pointerId);
            });
            canvas.addEventListener('pointermove', (event) => {
                if (!pointer.active) return;
                const cropSize = Math.min(image.naturalWidth, image.naturalHeight) / Number(zoomInput.value);
                const maxOffsetX = Math.max(0, (image.naturalWidth - cropSize) / 2);
                const maxOffsetY = Math.max(0, (image.naturalHeight - cropSize) / 2);
                pointer.offsetX = Math.max(-1, Math.min(1, pointer.offsetX - (event.clientX - pointer.x) / canvas.clientWidth * cropSize / (maxOffsetX || 1)));
                pointer.offsetY = Math.max(-1, Math.min(1, pointer.offsetY - (event.clientY - pointer.y) / canvas.clientHeight * cropSize / (maxOffsetY || 1)));
                pointer.x = event.clientX;
                pointer.y = event.clientY;
                drawCrop();
            });
            canvas.addEventListener('pointerup', () => { pointer.active = false; });
            canvas.addEventListener('pointercancel', () => { pointer.active = false; });

            editor.querySelectorAll('[data-cancel-crop]').forEach((button) => {
                button.addEventListener('click', cancelCrop);
            });
            editor.addEventListener('click', (event) => {
                if (event.target === editor) {
                    cancelCrop();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !editor.hidden) {
                    cancelCrop();
                }
            });

            editor.querySelector('[data-apply-crop]')?.addEventListener('click', () => {
                canvas.toBlob((blob) => {
                    if (!blob) {
                        status.textContent = 'Foto gagal diproses. Silakan coba lagi.';
                        return;
                    }

                    const croppedFile = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });
                    const transfer = new DataTransfer();
                    transfer.items.add(croppedFile);
                    input.files = transfer.files;
                    if (croppedPreviewUrl) URL.revokeObjectURL(croppedPreviewUrl);
                    croppedPreviewUrl = URL.createObjectURL(blob);
                    preview.src = croppedPreviewUrl;
                    preview.hidden = false;
                    if (initial) initial.hidden = true;
                    photoReady = true;
                    status.textContent = 'Foto siap disimpan dalam ukuran 256 × 256 piksel.';
                    closeEditor();
                }, 'image/jpeg', 0.88);
            });

            form.addEventListener('submit', async (event) => {
                if (photoReady) {
                    return;
                }

                const [file] = input.files || [];
                if (!file) {
                    return;
                }

                event.preventDefault();
                editor.hidden = false;
                status.textContent = 'Selesaikan pengaturan foto sebelum menyimpan.';
                submitButton.disabled = false;
            });
        })();
    </script>
</x-filament-panels::page>
