@extends('layouts.app')
@section('title', 'Atur Profil - WadahNgopi')
@section('content')
<style>
    .wn-account-profile-photo-row {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 14px 16px;
        border: 1px solid rgba(135, 101, 67, 0.12);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(255, 249, 242, 0.95), rgba(248, 239, 230, 0.9));
    }

    .wn-account-profile-photo-shell {
        position: relative;
        display: grid;
        place-items: center;
        flex: 0 0 84px;
        width: 84px;
        height: 84px;
    }

    .wn-account-avatar {
        display: grid;
        place-items: center;
        width: 84px;
        height: 84px;
        border-radius: 28px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        background: linear-gradient(145deg, #f8e6c6, #dcb779);
        box-shadow: 0 12px 28px rgba(36, 23, 12, 0.14);
        font-size: 2rem;
    }

    .wn-account-avatar-clickable {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .wn-account-avatar-clickable:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(36, 23, 12, 0.18);
    }

    .wn-account-profile-actions {
        display: grid;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }

    .wn-account-profile-upload {
        display: grid;
        gap: 8px;
    }

    .wn-account-profile-upload input {
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid #e9e1d5;
        background: #fff;
        color: #292620;
        font: inherit;
    }

    .wn-account-edit-photo-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        min-height: 36px;
        padding: 8px 14px;
        border: 1px solid #e9e1d5;
        border-radius: 999px;
        background: #fff;
        color: #292620;
        font-size: 0.72rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .wn-account-edit-photo-btn:hover {
        border-color: #dfc990;
        background: #fffaf0;
    }

    .wn-profile-photo-editor {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(41, 38, 32, 0.55);
        backdrop-filter: blur(5px);
    }

    .wn-profile-photo-editor.is-open {
        display: flex;
    }

    body.profile-photo-editor-open {
        overflow: hidden;
    }

    body.profile-photo-editor-open .bottom-nav.dock-nav-home {
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        transform: translateX(-50%) translateY(12px) !important;
    }

    .wn-profile-photo-editor__panel {
        width: min(560px, 100%);
        max-height: calc(100vh - 48px);
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 10px 10px 8px;
        border: 1px solid #e9e1d5;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(41, 38, 32, 0.16);
    }

    .wn-profile-photo-editor__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 2px 4px 10px;
    }

    .wn-profile-photo-editor__header h2 {
        margin: 0;
        color: #292620;
        font-size: 1.1rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .wn-profile-photo-editor__close {
        display: grid;
        place-items: center;
        width: 28px;
        height: 28px;
        border: 1px solid #e9e1d5;
        border-radius: 999px;
        background: #fff;
        color: #292620;
        font-size: 1.3rem;
        line-height: 1;
        cursor: pointer;
    }

    .wn-profile-photo-editor__frame {
        position: relative;
        width: 100%;
        max-width: 100%;
        aspect-ratio: 1.05;
        overflow: hidden;
        border: 1px solid #e9e1d5;
        border-radius: 18px;
        background: #fbf7ef;
        cursor: grab;
        touch-action: none;
        user-select: none;
    }

    .wn-profile-photo-editor__frame img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform-origin: center center;
        transition: transform 0.15s ease;
        will-change: transform;
    }

    .wn-profile-photo-editor__controls {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
        padding: 0 2px;
    }

    .wn-profile-photo-editor__label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: #746d63;
        font-size: 0.7rem;
        font-weight: 900;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .wn-profile-photo-editor__range {
        width: 100%;
        height: 3px;
        border-radius: 999px;
        accent-color: #e0a846;
    }

    .wn-profile-photo-editor__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 6px;
        padding-top: 2px;
    }

    .wn-profile-photo-editor__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 10px 16px;
        border: 1px solid #292620;
        border-radius: 999px;
        font: inherit;
        font-size: 0.78rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.15s ease;
    }

    .wn-profile-photo-editor__button:hover {
        transform: translateY(-1px);
    }

    .wn-profile-photo-editor__button--secondary {
        border-color: #e9e1d5;
        background: #fff;
        color: #292620;
    }

    .wn-profile-photo-editor__button--primary {
        background: #292620;
        color: #fff;
    }
</style>

<main class="wn-account-page wn-account-profile-edit-page">
    <a href="{{ route('account') }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke akun</a>
    <header class="wn-customer-page-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-user-circle" aria-hidden="true"></i> Pengaturan akun</span>
        <h1>Atur profil</h1>
        <p>Perbarui nama, email, dan foto profil yang tampil di akunmu.</p>
    </header>

    @if(session('status'))
        <p role="status" class="wn-account-form-message is-success">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <p role="alert" class="wn-account-form-message">{{ $errors->first() }}</p>
    @endif

    <section aria-label="Formulir profil">
        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="wn-account-profile-form">
            @csrf
            @method('PATCH')
            <div class="wn-account-profile-photo-row">
                <div class="wn-account-profile-photo-shell">
                    @if($profilePhotoUrl)
                        <img class="wn-account-avatar wn-account-avatar-photo wn-account-avatar-clickable" src="{{ $profilePhotoUrl }}" alt="Foto profil saat ini" title="Klik untuk mengatur ulang ukuran dan crop foto">
                    @else
                        <div class="wn-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(\App\Support\AuthenticatedUser::require()->name, 0, 1)) }}</div>
                    @endif
                </div>

                <div class="wn-account-profile-actions">
                    <label class="wn-account-profile-upload">
                        <span>Foto profil</span>
                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                        <small>JPG, PNG, atau WEBP · Maksimal 5 MB</small>
                    </label>

                    @if($profilePhotoUrl)
                        <button type="button" class="wn-account-edit-photo-btn" data-edit-photo>Atur ulang foto</button>
                    @endif
                </div>
            </div>
            @if($profilePhotoUrl)
                <label class="wn-account-remove-photo">
                    <input type="checkbox" name="remove_profile_photo" value="1">
                    <span>Hapus foto profil saat ini</span>
                </label>
            @endif
            <div class="wn-account-profile-fields">
                <label>
                    <span>Nama</span>
                    <input type="text" name="name" value="{{ old('name', \App\Support\AuthenticatedUser::require()->name) }}" required minlength="2" maxlength="120" autocomplete="name">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', \App\Support\AuthenticatedUser::require()->email) }}" required maxlength="255" autocomplete="email">
                </label>
            </div>
            <button type="submit" class="wn-account-profile-save"><i class="ph ph-floppy-disk" aria-hidden="true"></i> Simpan profil</button>
        </form>
    </section>
</main>

<div id="profile-photo-editor" class="wn-profile-photo-editor" aria-hidden="true">
    <div class="wn-profile-photo-editor__panel" role="dialog" aria-modal="true" aria-labelledby="profile-photo-editor-title">
        <div class="wn-profile-photo-editor__header">
            <h2 id="profile-photo-editor-title">Atur foto profil</h2>
            <button type="button" class="wn-profile-photo-editor__close" id="profile-photo-close" aria-label="Tutup editor foto">×</button>
        </div>

        <div class="wn-profile-photo-editor__frame" id="profile-photo-frame">
            <img id="profile-photo-source" alt="Preview foto profil">
        </div>

        <div class="wn-profile-photo-editor__controls">
            <div class="wn-profile-photo-editor__label">
                <span>Ukuran</span>
                <strong id="profile-photo-zoom-value">100%</strong>
            </div>
            <input id="profile-photo-zoom" class="wn-profile-photo-editor__range" type="range" min="0.8" max="2.5" step="0.01" value="1" aria-label="Ukuran foto profil">

            <div class="wn-profile-photo-editor__actions">
                <button type="button" class="wn-profile-photo-editor__button wn-profile-photo-editor__button--secondary" id="profile-photo-cancel">Batal</button>
                <button type="button" class="wn-profile-photo-editor__button wn-profile-photo-editor__button--primary" id="profile-photo-apply">Gunakan foto</button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const fileInput = document.querySelector('input[name="profile_photo"]');
        const editor = document.getElementById('profile-photo-editor');
        const closeButton = document.getElementById('profile-photo-close');
        const cancelButton = document.getElementById('profile-photo-cancel');
        const applyButton = document.getElementById('profile-photo-apply');
        const zoomInput = document.getElementById('profile-photo-zoom');
        const zoomValue = document.getElementById('profile-photo-zoom-value');
        const imageSource = document.getElementById('profile-photo-source');
        const avatarPhoto = document.querySelector('.wn-account-avatar-photo');
        const editPhotoButton = document.querySelector('[data-edit-photo]');
        const frame = document.getElementById('profile-photo-frame');

        let activeImageUrl = '';
        const dragState = {
            active: false,
            startX: 0,
            startY: 0,
            panX: 0,
            panY: 0,
        };

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        const updateImageTransform = () => {
            const value = Number(zoomInput.value);
            const translateX = dragState.panX * 48 * value;
            const translateY = dragState.panY * 48 * value;

            zoomValue.textContent = `${Math.round(value * 100)}%`;
            imageSource.style.transform = `translate(${translateX}px, ${translateY}px) scale(${value})`;
        };

        const resetEditorState = () => {
            dragState.active = false;
            dragState.startX = 0;
            dragState.startY = 0;
            dragState.panX = 0;
            dragState.panY = 0;
            zoomInput.value = '1';
            updateImageTransform();
        };

        const openEditor = (src) => {
            if (!src) {
                return;
            }

            activeImageUrl = src;
            imageSource.src = src;
            imageSource.onload = () => {
                resetEditorState();
                editor.classList.add('is-open');
                editor.setAttribute('aria-hidden', 'false');
                document.body.classList.add('profile-photo-editor-open');
            };
        };

        const closeEditor = () => {
            editor.classList.remove('is-open');
            editor.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('profile-photo-editor-open');
            imageSource.removeAttribute('src');
            activeImageUrl = '';
            resetEditorState();
        };

        const replaceFileInput = (blob) => {
            const fileName = 'profile-photo.jpg';
            const nextFile = new File([blob], fileName, { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(nextFile);
            fileInput.files = dataTransfer.files;
        };

        const cropImageToSquare = (src) => {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const size = 1200;
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const zoomValueNumber = Number(zoomInput.value);
                    const cropSize = Math.min(img.naturalWidth, img.naturalHeight) / zoomValueNumber;
                    const maxOffset = Math.max(cropSize * 0.38, 20);
                    const panX = clamp(dragState.panX, -1, 1) * maxOffset;
                    const panY = clamp(dragState.panY, -1, 1) * maxOffset;
                    const startX = (img.naturalWidth - cropSize) / 2 + panX;
                    const startY = (img.naturalHeight - cropSize) / 2 + panY;

                    canvas.width = size;
                    canvas.height = size;

                    ctx.clearRect(0, 0, size, size);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, size, size);
                    ctx.drawImage(img, startX, startY, cropSize, cropSize, 0, 0, size, size);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Tidak dapat memproses foto profil.'));
                            return;
                        }

                        resolve(blob);
                    }, 'image/jpeg', 0.92);
                };

                img.onerror = () => reject(new Error('Foto profil tidak dapat dibaca.'));
                img.src = src;
            });
        };

        fileInput.addEventListener('change', (event) => {
            const [file] = event.target.files || [];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = () => openEditor(reader.result);
            reader.readAsDataURL(file);
        });

        const openExistingPhotoEditor = () => {
            if (avatarPhoto) {
                openEditor(avatarPhoto.getAttribute('src'));
            }
        };

        if (avatarPhoto) {
            avatarPhoto.addEventListener('click', openExistingPhotoEditor);
        }

        if (editPhotoButton) {
            editPhotoButton.addEventListener('click', openExistingPhotoEditor);
        }

        closeButton.addEventListener('click', closeEditor);
        cancelButton.addEventListener('click', closeEditor);
        zoomInput.addEventListener('input', () => {
            updateImageTransform();
        });

        frame.addEventListener('pointerdown', (event) => {
            if (!imageSource.src) {
                return;
            }

            dragState.active = true;
            dragState.startX = event.clientX;
            dragState.startY = event.clientY;
            frame.setPointerCapture(event.pointerId);
        });

        frame.addEventListener('pointermove', (event) => {
            if (!dragState.active) {
                return;
            }

            const deltaX = (event.clientX - dragState.startX) / frame.clientWidth;
            const deltaY = (event.clientY - dragState.startY) / frame.clientHeight;

            dragState.panX = clamp(dragState.panX + deltaX * 1.5, -1, 1);
            dragState.panY = clamp(dragState.panY + deltaY * 1.5, -1, 1);
            dragState.startX = event.clientX;
            dragState.startY = event.clientY;

            updateImageTransform();
        });

        frame.addEventListener('pointerup', () => {
            dragState.active = false;
        });

        frame.addEventListener('pointerleave', () => {
            dragState.active = false;
        });

        applyButton.addEventListener('click', async () => {
            try {
                const blob = await cropImageToSquare(activeImageUrl);
                replaceFileInput(blob);
                closeEditor();
            } catch (error) {
                window.alert(error.message || 'Terjadi kesalahan saat menyiapkan foto profil.');
            }
        });

        editor.addEventListener('click', (event) => {
            if (event.target === editor) {
                closeEditor();
            }
        });
    })();
</script>
@endsection
