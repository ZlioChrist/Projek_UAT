<div class="space-y-6">
    @foreach($cafes as $cafe)
        <x-filament::section>
            <x-slot name="heading">{{ $cafe->name }}</x-slot>
            <x-slot name="description">Menu, stok, fasilitas, dan kontak kafe yang terhubung ke akun Anda.</x-slot>

            <div class="mb-4 flex flex-wrap gap-2">
                <x-filament::button tag="a" :href="\App\Filament\Resources\CafeResource::getUrl('edit', ['record' => $cafe])" color="gray">
                    Kelola kafe
                </x-filament::button>
                @if($cafe->whatsapp_number)
                    @php
                        $cafeWhatsapp = preg_replace('/\D+/', '', $cafe->whatsapp_number);
                        if (str_starts_with($cafeWhatsapp, '0')) {
                            $cafeWhatsapp = '62'.substr($cafeWhatsapp, 1);
                        }
                    @endphp
                    <x-filament::button tag="a" :href="'https://wa.me/'.$cafeWhatsapp" target="_blank" color="success" icon="heroicon-m-chat-bubble-left-right">
                        WhatsApp kafe
                    </x-filament::button>
                @endif
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 font-medium">Menu</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Stok fisik</th>
                            <th class="px-4 py-3 font-medium">Tersedia</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($cafe->menuItems as $menuItem)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $menuItem->name }}</td>
                                <td class="px-4 py-3">{{ $menuItem->category ?: 'Tanpa kategori' }}</td>
                                <td class="px-4 py-3">{{ $menuItem->stock }}</td>
                                <td class="px-4 py-3"><x-filament::badge :color="$menuItem->available_stock > 0 ? 'success' : 'danger'">{{ $menuItem->available_stock }}</x-filament::badge></td>
                                <td class="px-4 py-3 text-right"><x-filament::link :href="\App\Filament\Resources\MenuItemResource::getUrl('edit', ['record' => $menuItem])">Edit</x-filament::link></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-5 text-center text-gray-500">Belum ada menu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-semibold">Fasilitas</h3>
                    <x-filament::link :href="\App\Filament\Resources\FacilityResource::getUrl('index')">Kelola fasilitas</x-filament::link>
                </div>
                <div class="flex flex-wrap gap-2">
                    @forelse($cafe->facilities as $facility)
                        <x-filament::badge color="gray">{{ $facility->name }}</x-filament::badge>
                    @empty
                        <span class="text-sm text-gray-500">Belum ada fasilitas.</span>
                    @endforelse
                </div>
            </div>
        </x-filament::section>
    @endforeach

    @foreach($roasteries as $roastery)
        <x-filament::section>
            <x-slot name="heading">{{ $roastery->name }}</x-slot>
            <x-slot name="description">Menu, stok, dan kontak roastery yang terhubung ke akun Anda.</x-slot>

            <div class="mb-4 flex flex-wrap gap-2">
                <x-filament::button tag="a" :href="\App\Filament\Resources\RoasteryResource::getUrl('edit', ['record' => $roastery])" color="gray">
                    Kelola roastery
                </x-filament::button>
                @if($roastery->whatsapp_number)
                    @php
                        $roasteryWhatsapp = preg_replace('/\D+/', '', $roastery->whatsapp_number);
                        if (str_starts_with($roasteryWhatsapp, '0')) {
                            $roasteryWhatsapp = '62'.substr($roasteryWhatsapp, 1);
                        }
                    @endphp
                    <x-filament::button tag="a" :href="'https://wa.me/'.$roasteryWhatsapp" target="_blank" color="success" icon="heroicon-m-chat-bubble-left-right">
                        WhatsApp roastery
                    </x-filament::button>
                @endif
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 font-medium">Menu</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Stok fisik</th>
                            <th class="px-4 py-3 font-medium">Tersedia</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($roastery->menuItems as $menuItem)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $menuItem->name }}</td>
                                <td class="px-4 py-3">{{ $menuItem->category ?: 'Tanpa kategori' }}</td>
                                <td class="px-4 py-3">{{ $menuItem->stock }}</td>
                                <td class="px-4 py-3"><x-filament::badge :color="$menuItem->available_stock > 0 ? 'success' : 'danger'">{{ $menuItem->available_stock }}</x-filament::badge></td>
                                <td class="px-4 py-3 text-right"><x-filament::link :href="\App\Filament\Resources\MenuItemResource::getUrl('edit', ['record' => $menuItem])">Edit</x-filament::link></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-5 text-center text-gray-500">Belum ada menu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach

    <x-filament::section>
        <x-slot name="heading">Daftar pesanan dan notifikasi</x-slot>
        <x-slot name="description">Pantau pelanggan dan status pesanan terbaru dari kedai Anda.</x-slot>

        <div class="mb-4 flex justify-end">
            <x-filament::button tag="a" :href="\App\Filament\Resources\OrderResource::getUrl('index')" icon="heroicon-m-arrow-top-right-on-square">
                Buka pengelolaan pesanan
            </x-filament::button>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode / kedai</th>
                        <th class="px-4 py-3 font-medium">Pelanggan</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($orders as $order)
                        <tr>
                            <td class="px-4 py-3"><span class="font-mono font-semibold">{{ $order->public_code }}</span><span class="block text-xs text-gray-500">{{ $order->shop_name }}</span></td>
                            <td class="px-4 py-3">{{ $order->customer_name }}<span class="block text-xs text-gray-500">{{ $order->customer_whatsapp ?: 'Tanpa nomor WhatsApp' }}</span></td>
                            <td class="px-4 py-3"><x-filament::badge color="warning">{{ $order->status_label }}</x-filament::badge></td>
                            <td class="px-4 py-3">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right"><x-filament::link :href="route('owner.orders.show', $order->public_code)">Pantau</x-filament::link></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-5 text-center text-gray-500">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Program Optimalisasi AI</x-slot>
    </x-filament::section>
</div>
