@extends('layouts.app')
@section('title', 'Notifikasi - WadahNgopi')
@section('content')
<main class="wn-account-page">
    <header class="wn-account-hero">
        <div class="wn-account-welcome">
            <p class="wn-account-eyebrow">Pembaruan favoritmu</p>
            <h1 class="wn-account-title">Notifikasi</h1>
            <p class="wn-account-email">Info perubahan kafe atau roastery yang kamu simpan, serta kabar pesananmu.</p>
        </div>
    </header>

    <section aria-label="Daftar notifikasi" class="mt-8 space-y-3">
        @forelse($notifications as $notification)
            <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm {{ $notification->read_at ? 'opacity-75' : 'ring-1 ring-amber-400' }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-stone-900">{{ $notification->data['title'] ?? 'Pembaruan WadahNgopi' }}</h2>
                            @unless($notification->read_at)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900">Baru</span>
                            @endunless
                        </div>
                        <p class="mt-2 text-sm leading-6 text-stone-600">{{ $notification->data['message'] ?? '' }}</p>
                        @if(! empty($notification->data['business_name']))
                            <p class="mt-2 text-xs font-semibold text-stone-500">{{ $notification->data['business_name'] }}</p>
                        @endif
                        <time class="mt-3 block text-xs text-stone-400" datetime="{{ $notification->created_at->toIso8601String() }}">
                            {{ $notification->created_at->format('d M Y, H:i') }}
                        </time>
                    </div>
                    @if(! empty($notification->data['url']))
                        <form method="POST" action="{{ route('notifications.open', $notification->id) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-800">
                                {{ $notification->read_at ? 'Buka' : 'Tandai dibaca & buka' }}
                                <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <i class="ph ph-bell-slash text-3xl text-stone-400" aria-hidden="true"></i>
                <p class="mt-3 font-semibold text-stone-700">Belum ada notifikasi</p>
                <p class="mt-1 text-sm text-stone-500">Pembaruan kedai favorit dan status pesanan akan muncul di sini.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</main>
@endsection
