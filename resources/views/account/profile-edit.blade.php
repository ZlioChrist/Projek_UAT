@extends('layouts.app')
@section('title', 'Atur Profil - WadahNgopi')
@section('content')
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
                @if($profilePhotoUrl)
                    <img class="wn-account-avatar wn-account-avatar-photo" src="{{ $profilePhotoUrl }}" alt="Foto profil saat ini">
                @else
                    <div class="wn-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(\App\Support\AuthenticatedUser::require()->name, 0, 1)) }}</div>
                @endif
                <label class="wn-account-profile-upload">
                    <span>Foto profil</span>
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG, atau WEBP · Maksimal 2 MB</small>
                </label>
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
@endsection
