<div class="space-y-4 text-sm">
    <div><strong>Pemohon</strong><p>{{ $application->user?->name }} · {{ $application->user?->email }}</p></div>
    <div><strong>Jenis usaha</strong><p>{{ $application->business_type === 'cafe' ? 'Kafe' : 'Roastery' }}</p></div>
    <div><strong>Nama usaha</strong><p>{{ $application->business_name }}</p></div>
    <div><strong>Kota</strong><p>{{ $application->city?->name }}</p></div>
    <div><strong>Alamat</strong><p>{{ $application->address }}</p></div>
    <div><strong>WhatsApp</strong><p>{{ $application->whatsapp_number }}</p></div>
    <div><strong>Deskripsi</strong><p>{{ $application->description ?: 'Tidak diisi' }}</p></div>
    @if($application->review_notes)
        <div><strong>Catatan pemeriksaan</strong><p>{{ $application->review_notes }}</p></div>
    @endif
</div>
