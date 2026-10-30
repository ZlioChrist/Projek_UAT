{{-- Livewire Saved Items Component --}}
<div wire:poll.30s="loadItems" x-data="savedItemsLogic()" x-init="initFromStorage($wire)" class="saved-items-wrapper">

    {{-- Header Page --}}
    <div class="saved-page-header">
        <h2 class="saved-page-title">
            <span class="saved-count-badge"
                  x-show="$wire.items.length > 0"
                  x-text="$wire.items.length"
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 scale-50"
                  x-transition:enter-end="opacity-100 scale-100">0</span>
            Tersimpan
        </h2>
    </div>

    {{-- Skeleton Loading --}}
    <div wire:loading class="space-y-4 px-6 pb-32">
        <template x-for="i in 3" :key="'skel-'+i">
            <x-skeleton.saved-card />
        </template>
    </div>

    {{-- Saved Items List --}}
    <div class="space-y-3 px-6 pb-32" x-show="$wire.items.length > 0" wire:loading.remove>
        <template x-for="item in $wire.items" :key="item.type + '-' + item.id">
            <a :href="item.url" class="saved-card group">
                <div class="saved-card__image-container">
                    <img :src="item.image || 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=300'"
                        :alt="item.name" class="saved-card__image">
                    <div class="absolute top-1 right-1 w-3 h-3 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-sm z-20">
                        <div :class="item.isOpen ? 'bg-emerald-500' : 'bg-rose-500'" class="w-1.5 h-1.5 rounded-full"></div>
                    </div>
                </div>
                <div class="flex-1 min-w-0 flex flex-col justify-center">
                    <h3 class="text-[0.95rem] font-black text-[#2C1810] leading-snug truncate group-hover:text-amber-600 transition-colors" x-text="item.name"></h3>
                    <p class="text-[0.65rem] font-bold text-[#8B7355] flex items-center gap-1 mt-0.5">
                        <i class="ph-fill ph-map-pin text-amber-500"></i>
                        <span class="truncate" x-text="(item.address || '').split(',')[0]"></span>
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="saved-card__tag" x-text="item.type"></span>
                    </div>
                </div>
                <div class="pl-2 border-l border-[#1a0f0a]/5 flex flex-col justify-center">
                    <button @click.prevent.stop="confirmDelete(item)"
                        class="flex items-center justify-center w-9 h-9 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white active:scale-90 transition-all">
                        <i class="ph-bold ph-trash text-base"></i>
                    </button>
                </div>
            </a>
        </template>
    </div>

    {{-- Premium 3D Empty State --}}
    <div x-show="$wire.items.length === 0" x-cloak wire:loading.remove>
        <div class="saved-empty-state">
            <span class="saved-empty__illustration" aria-hidden="true"><i class="ph-fill ph-coffee"></i></span>
            <h3 class="saved-empty__title">Belum Ada Simpanan</h3>
            <p class="saved-empty__subtitle">Jelajahi cafe dan roastery estetik di sekitarmu dan simpan favoritmu di sini.</p>
            <div class="saved-empty__actions">
                <a href="{{ route('explore') }}" class="saved-empty__cta">
                    <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                    Cari Kopi
                </a>
                <a href="{{ route('roastery') }}" class="saved-empty__cta saved-empty__cta--secondary">
                    <i class="ph-bold ph-coffee" aria-hidden="true"></i>
                    Cari Beans
                </a>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak class="modal-overlay" :class="{ 'active': showDeleteModal }">
            <div x-show="showDeleteModal" class="modal-card"
                x-transition:enter="transition ease-out duration-400"
                x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                <div class="modal-icon-circle"><i class="ph-bold ph-warning text-2xl text-rose-500"></i></div>
                <h3 class="text-xl font-black text-center text-[#2C1810] mb-2">Hapus?</h3>
                <p class="text-center text-[0.9rem] text-[#8B7355] mb-8" x-text="'Hapus ' + itemToDelete?.name + '?'"></p>
                <div class="flex flex-col gap-3">
                    <button @click="executeDelete()" class="btn-delete-confirm py-4 w-full text-white font-bold">Ya, Hapus</button>
                    <button @click="showDeleteModal = false" class="btn-cancel py-4 w-full font-bold text-[#8B7355]">Batal</button>
                </div>
            </div>
        </div>
    </template>
</div>

@script
<script>
    Alpine.data('savedItemsLogic', () => ({
        savedCafeIds: [],
        savedRoasteryIds: [],
        showDeleteModal: false,
        itemToDelete: null,
        $wire: null,
        async initFromStorage(wireInstance) {
            this.$wire = wireInstance;
            const storedCafes = localStorage.getItem('wadah-bookmarks');
            const storedRoasteries = localStorage.getItem('wadah-roastery-bookmarks');

            this.savedCafeIds = storedCafes ? JSON.parse(storedCafes) : [];
            this.savedRoasteryIds = storedRoasteries ? JSON.parse(storedRoasteries) : [];

            const isAuth = {{ auth()->check() ? 'true' : 'false' }};

            if (isAuth) {
                // Sync to DB if we have local items
                if (this.savedCafeIds.length > 0 || this.savedRoasteryIds.length > 0) {
                    try {
                        await fetch('/api/bookmarks/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                cafes: this.savedCafeIds,
                                roasteries: this.savedRoasteryIds
                            })
                        });
                        // Clear local storage after successful sync
                        localStorage.removeItem('wadah-bookmarks');
                        localStorage.removeItem('wadah-roastery-bookmarks');
                        this.savedCafeIds = [];
                        this.savedRoasteryIds = [];
                    } catch (e) { console.error('Sync failed', e); }
                }
                // For auth users, Livewire handles the fetching directly from DB.
                // We just trigger a load.
                this.$wire.loadItems();
            } else {
                this.syncToLivewire();
            }
        },
        syncToLivewire() {
            if (this.$wire) this.$wire.updateIds(this.savedCafeIds, this.savedRoasteryIds);
        },
        confirmDelete(item) {
            this.itemToDelete = item;
            this.showDeleteModal = true;
        },
        async executeDelete() {
            if (!this.itemToDelete) return;
            const isAuth = {{ auth()->check() ? 'true' : 'false' }};

            if (isAuth) {
                try {
                    await fetch('/api/bookmarks/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            bookmarkable_id: this.itemToDelete.id,
                            bookmarkable_type: this.itemToDelete.type
                        })
                    });
                    this.$wire.loadItems();
                } catch (e) { console.error('Delete failed', e); }
            } else {
                if (this.itemToDelete.type === 'cafe') {
                    this.savedCafeIds = this.savedCafeIds.filter(i => i != this.itemToDelete.id);
                    localStorage.setItem('wadah-bookmarks', JSON.stringify(this.savedCafeIds));
                } else {
                    this.savedRoasteryIds = this.savedRoasteryIds.filter(i => i != this.itemToDelete.id);
                    localStorage.setItem('wadah-roastery-bookmarks', JSON.stringify(this.savedRoasteryIds));
                }
                this.syncToLivewire();
            }
            this.showDeleteModal = false;
        }
    }));
</script>
@endscript
