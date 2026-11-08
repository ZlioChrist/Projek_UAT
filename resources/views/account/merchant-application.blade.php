@extends('layouts.app')
@section('title', 'Pengajuan Mitra - WadahNgopi')
@section('content')
<main class="mx-auto max-w-3xl px-4 py-10">
    <a class="text-sm font-bold text-amber-800" href="{{ route('account') }}">← Kembali ke akun</a>
    <section class="mt-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700">Pengelola usaha</p>
        <h1 class="mt-2 text-3xl font-black text-[#2C1810]">Ajukan kafe atau roastery</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Tim WadahNgopi akan memeriksa informasi usaha Anda. Setelah disetujui, akun customer ini akan menjadi akun pengelola dan masuk melalui panel admin. Usaha yang dibuat awalnya berupa draft, belum tampil untuk pelanggan sampai dilengkapi dan diterbitkan.</p>

        @if($errors->any())
            <div role="alert" class="mt-5 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        @if($application?->status === 'rejected')
            <div class="mt-5 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                Pengajuan sebelumnya ditolak: {{ $application->review_notes }} Anda dapat mengirim pengajuan baru setelah memperbaiki informasinya.
            </div>
        @endif

        <form method="POST" action="{{ route('merchant-applications.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-bold">Jenis usaha
                <select name="business_type" required class="mt-1 w-full rounded-xl border border-stone-300 p-3">
                    <option value="">Pilih jenis</option>
                    <option value="cafe" @selected(old('business_type') === 'cafe')>Kafe</option>
                    <option value="roastery" @selected(old('business_type') === 'roastery')>Roastery</option>
                </select>
            </label>
            <label class="block text-sm font-bold">Nama usaha
                <input name="business_name" value="{{ old('business_name', $application?->business_name) }}" required minlength="2" maxlength="180" class="mt-1 w-full rounded-xl border border-stone-300 p-3">
            </label>
            <label class="block text-sm font-bold">Kota
                <select name="city_id" required class="mt-1 w-full rounded-xl border border-stone-300 p-3">
                    <option value="">Pilih kota</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) old('city_id', $application?->city_id) === (string) $city->id)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-bold">Alamat lengkap
                <textarea name="address" required minlength="8" maxlength="2000" rows="3" class="mt-1 w-full rounded-xl border border-stone-300 p-3">{{ old('address', $application?->address) }}</textarea>
            </label>
            <label class="block text-sm font-bold">WhatsApp usaha
                <input name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $application?->whatsapp_number) }}" required minlength="8" maxlength="32" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-xl border border-stone-300 p-3">
                <span class="mt-1 block text-xs font-normal text-stone-500">Nomor ini akan digunakan pelanggan untuk menghubungi usaha.</span>
            </label>
            <label class="block text-sm font-bold">Tentang usaha <span class="font-normal text-stone-500">(opsional)</span>
                <textarea name="description" maxlength="2000" rows="4" class="mt-1 w-full rounded-xl border border-stone-300 p-3">{{ old('description', $application?->description) }}</textarea>
            </label>
            <button type="submit" class="rounded-full bg-[#2C1810] px-6 py-3 font-black text-white">Kirim untuk ditinjau</button>
        </form>
    </section>
</main>
@endsection
