@extends('layouts.app')
@section('title', 'Masukan Pelanggan - WadahNgopi')
@section('content')
<main class="mx-auto max-w-2xl px-4 py-10">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700">Suara pelanggan</p>
        <h1 class="mt-1 text-3xl font-black text-[#2C1810]">Keluhan, kritik, dan saran</h1>
        <p class="mt-3 text-sm text-slate-600">Ceritakan pengalaman Anda agar kualitas kafe dan roastery terus meningkat. Nama dan kontak wajib diisi agar tim dapat menindaklanjuti. Masukan hanya dikelola oleh pihak kedai dan admin WadahNgopi.</p>
        @if(session('status'))<div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="mt-5 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('feedback.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-bold">Kafe atau roastery
                <select name="shop" required class="mt-1 w-full rounded-xl border border-slate-300 p-3">
                    <option value="">Pilih tempat</option>
                    @foreach($shops as $shop)<option value="{{ $shop['value'] }}" @selected(old('shop') === $shop['value'])>{{ $shop['name'] }}</option>@endforeach
                </select>
            </label>
            <label class="block text-sm font-bold">Jenis masukan
                <select name="category" required class="mt-1 w-full rounded-xl border border-slate-300 p-3">
                    @foreach(['complaint' => 'Keluhan', 'criticism' => 'Kritik', 'suggestion' => 'Saran', 'praise' => 'Apresiasi', 'other' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', 'suggestion') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-bold">Nama<input name="customer_name" required minlength="2" maxlength="120" value="{{ old('customer_name', auth()->user()?->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
            <label class="block text-sm font-bold">Kontak yang bisa dihubungi<input name="customer_contact" required minlength="5" maxlength="120" value="{{ old('customer_contact') }}" placeholder="Nomor WhatsApp atau email" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
            <label class="block text-sm font-bold">Pesan<textarea name="message" required minlength="10" maxlength="3000" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 p-3">{{ old('message') }}</textarea></label>
            <button class="w-full rounded-full bg-[#2C1810] px-6 py-3 font-black text-white">Kirim masukan</button>
        </form>
    </div>
</main>
@endsection
