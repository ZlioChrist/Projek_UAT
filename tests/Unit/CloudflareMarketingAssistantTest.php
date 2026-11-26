<?php

use App\Filament\Pages\AiOptimization;
use App\Models\GeminiChatConversation;
use App\Models\GeminiChatMessage;
use App\Models\User;
use App\Services\CloudflareMarketingAssistant;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(Tests\TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'database.connections.sqlite.foreign_key_constraints' => false,
    ]);
    DB::purge('sqlite');

    Schema::connection('sqlite')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });
    $migration = require base_path('database/migrations/2026_11_24_000000_create_gemini_chat_history_tables.php');
    $migration->up();
    $previewMigration = require base_path('database/migrations/2026_11_25_000001_create_ai_prompt_previews_table.php');
    $previewMigration->up();

    Storage::fake('local');
});

it('sends the prompt to Cloudflare Workers AI and returns its text response', function () {
    config([
        'services.cloudflare.account_id' => 'test-account-id',
        'services.cloudflare.api_token' => 'test-cloudflare-token',
        'services.cloudflare.chat_model' => '@cf/qwen/qwen3.8-27b',
    ]);

    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'success' => true,
            'result' => [
                'choices' => [
                    ['message' => ['content' => "## Ide promosi\n\n**Coba promo:**\n- Beli dua, dapat satu"]],
                ],
            ],
        ]),
    ]);

    $result = app(CloudflareMarketingAssistant::class)->chat([
        ['role' => 'user', 'text' => 'Buat ide promosi kopi.'],
    ]);

    expect($result)->toBe("Ide promosi\n\nCoba promo:\n- Beli dua, dapat satu");

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudflare.com/client/v4/accounts/test-account-id/ai/run/@cf/qwen/qwen3.8-27b'
        && $request->hasHeader('Authorization', 'Bearer test-cloudflare-token')
        && str_contains($request['messages'][0]['content'], 'Ikuti perintah dengan aman')
        && str_contains($request['messages'][0]['content'], 'Jika informasi penting kurang')
        && str_contains($request['messages'][0]['content'], 'bahasa Indonesia sehari-hari')
        && str_contains($request['messages'][0]['content'], 'Jangan menggunakan Markdown')
        && str_contains($request['messages'][0]['content'], 'ramah, cerdas, profesional')
        && str_contains($request['messages'][0]['content'], 'Jangan kaku, membosankan, atau terlalu to the point')
        && str_contains($request['messages'][0]['content'], 'tetap fokus dan tidak bertele-tele')
        && str_contains($request['messages'][0]['content'], 'Jika pengguna menyapa, bertanya kabar')
        && str_contains($request['messages'][0]['content'], 'tanyakan balik kabar')
        && str_contains($request['messages'][0]['content'], 'Jangan langsung memberi saran usaha')
        && str_contains($request['messages'][0]['content'], 'Untuk saran harga')
        && str_contains($request['messages'][0]['content'], 'Untuk naskah video edukasi')
        && $request['messages'][1]['content'] === 'Buat ide promosi kopi.'
        && $request['max_completion_tokens'] === 1200);
});

it('shows quick tools for marketing, captions, pricing, menus, and educational video scripts', function () {
    $this->actingAs((new User)->forceFill([
        'id' => 12,
        'name' => 'Nadia',
        'role' => 'admin',
    ]));

    Livewire::test(AiOptimization::class)
        ->assertSee('NEAHGO')
        ->assertDontSee('BEANLY AI')
        ->assertSee('YOUR SMART COFFEE COMPANION')
        ->assertSee('Halo, Nadia. Apa yang ingin Anda kembangkan?')
        ->assertSee('Discover. Order. Promote. Grow.')
        ->assertSee('Asisten pintar WadahNgopi')
        ->assertSee('Strategi pemasaran')
        ->assertSee('Buat caption')
        ->assertSee('Susun strategi harga')
        ->assertSee('Susun menu')
        ->assertSee('Buat naskah video edukasi')
        ->assertSee('Enter untuk mengirim, Shift+Enter untuk baris baru.')
        ->assertSee('Kirim')
        ->assertSee('VIDEO EDUKASI')
        ->assertSee('Naskah video edukasi proses seduh');
});

it('saves chat answers in history and restores them on a new page visit', function () {
    config([
        'services.cloudflare.account_id' => 'test-account-id',
        'services.cloudflare.api_token' => 'test-cloudflare-token',
    ]);
    $profilePhotoPath = 'profile-photos/13/profile.png';
    Storage::fake('public');
    Storage::disk('public')->put($profilePhotoPath, 'profile photo');

    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'success' => true,
            'result' => [
                'choices' => [
                    ['message' => ['content' => 'Buat promo beli dua kopi.']],
                ],
            ],
        ]),
    ]);

    $this->actingAs((new User)->forceFill([
        'id' => 13,
        'role' => 'admin',
        'profile_photo_path' => $profilePhotoPath,
    ]));

    Livewire::test(AiOptimization::class)
        ->set('chatDraft', 'Tulis ide promosi kopi.')
        ->call('sendChatMessage')
        ->assertSee(Storage::disk('public')->url($profilePhotoPath), false)
        ->assertSee('Buat promo beli dua kopi.');

    expect(GeminiChatConversation::count())->toBe(1)
        ->and(GeminiChatMessage::where('role', 'assistant')->value('content'))->toBe('Buat promo beli dua kopi.');

    Livewire::test(AiOptimization::class)
        ->assertSee('Tulis ide promosi kopi.')
        ->assertSee('Buat promo beli dua kopi.');
});

it('uses the saved profile photo as the Filament user avatar', function () {
    $profilePhotoPath = 'profile-photos/13/profile.png';
    Storage::fake('public');
    Storage::disk('public')->put($profilePhotoPath, 'profile photo');
    $user = (new User)->forceFill(['profile_photo_path' => $profilePhotoPath]);

    expect(Filament::getUserAvatarUrl($user))
        ->toBe(Storage::disk('public')->url($profilePhotoPath));
});

it('keeps chat context, sends an uploaded photo to Cloudflare, and saves the photo privately', function () {
    config([
        'services.cloudflare.account_id' => 'test-account-id',
        'services.cloudflare.api_token' => 'test-cloudflare-token',
    ]);
    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'success' => true,
            'result' => [
                'choices' => [
                    ['message' => ['content' => 'Foto menunjukkan es kopi susu.']],
                ],
            ],
        ]),
    ]);

    $this->actingAs((new User)->forceFill(['id' => 17, 'role' => 'admin']));
    $conversation = GeminiChatConversation::query()->create([
        'user_id' => 17,
        'title' => 'Ide kopi',
    ]);
    $conversation->messages()->create([
        'role' => 'user',
        'content' => 'Saya menjual kopi susu.',
    ]);

    Livewire::test(AiOptimization::class)
        ->set('activeConversationId', $conversation->id)
        ->set('chatDraft', 'Apa saran foto produk dari gambar ini?')
        ->set('uploadedPhoto', UploadedFile::fake()->createWithContent(
            'coffee.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=')
        ))
        ->call('sendChatMessage')
        ->assertSet('chatError', null)
        ->assertSet('chatDraft', '')
        ->assertSee('Foto menunjukkan es kopi susu.');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/@cf/qwen/qwen3.8-27b')
        && count($request['messages']) === 3
        && $request['messages'][1]['content'] === 'Saya menjual kopi susu.'
        && $request['messages'][2]['content'][1]['type'] === 'image_url'
        && str_starts_with($request['messages'][2]['content'][1]['image_url']['url'], 'data:image/png;base64,'));

    $savedImage = GeminiChatMessage::whereNotNull('image_path')->firstOrFail();
    Storage::disk('local')->assertExists($savedImage->image_path);
    $this->get(route('admin.ai-chat-images.show', [$conversation->id, $savedImage->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs((new User)->forceFill(['id' => 18, 'role' => 'admin']))
        ->get(route('admin.ai-chat-images.show', [$conversation->id, $savedImage->id]))
        ->assertNotFound();
});

it('saves generated images in the chat history and removes them when the conversation is deleted', function () {
    config([
        'services.cloudflare.account_id' => 'test-account-id',
        'services.cloudflare.api_token' => 'test-cloudflare-token',
        'services.cloudflare.image_model' => '@cf/black-forest-labs/flux-1-schnell',
    ]);
    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'success' => true,
            'result' => [
                'image' => base64_encode('fake-jpeg-bytes'),
            ],
        ]),
    ]);

    $this->actingAs((new User)->forceFill(['id' => 21, 'role' => 'admin']));

    Livewire::test(AiOptimization::class)
        ->call('generateImage', 'Buat gambar es kopi susu.')
        ->assertSet('imageError', null)
        ->assertSee('Buat gambar es kopi susu.');

    $conversation = GeminiChatConversation::where('user_id', 21)->firstOrFail();
    $generatedMessage = $conversation->messages()->whereNotNull('image_path')->firstOrFail();
    expect($generatedMessage->role)->toBe('assistant');
    Storage::disk('local')->assertExists($generatedMessage->image_path);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/@cf/black-forest-labs/flux-1-schnell')
        && $request['prompt'] === 'Buat gambar es kopi susu.'
        && $request['steps'] === 4);

    Livewire::test(AiOptimization::class)
        ->call('deleteConversation', $conversation->id);

    expect(GeminiChatConversation::count())->toBe(0);
    Storage::disk('local')->assertMissing($generatedMessage->image_path);
});

it('lets each account replace and restore its own prompt preview', function () {
    $promptKey = 'foto-kopi-segar-dengan-cahaya-alami';
    $previewUrl = route('admin.ai-prompt-previews.show', $promptKey);
    $owner = (new User)->forceFill(['id' => 31, 'role' => 'admin']);
    $imageContents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=');

    $this->actingAs($owner);

    Livewire::test(AiOptimization::class)
        ->set('previewPhoto', UploadedFile::fake()->createWithContent('custom.png', $imageContents))
        ->call('savePromptPreview', $promptKey)
        ->assertSet('previewError', null)
        ->assertSee($previewUrl);

    $preview = \App\Models\AiPromptPreview::query()
        ->where('user_id', 31)
        ->where('prompt_key', $promptKey)
        ->firstOrFail();
    Storage::disk('local')->assertExists($preview->image_path);
    $this->get($previewUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs((new User)->forceFill(['id' => 32, 'role' => 'admin']))
        ->get($previewUrl)
        ->assertNotFound();

    $this->actingAs($owner);
    Livewire::test(AiOptimization::class)
        ->assertSee($previewUrl)
        ->call('resetPromptPreview', $promptKey)
        ->assertSet('previewError', null)
        ->assertDontSee($previewUrl);

    expect(\App\Models\AiPromptPreview::query()->where('user_id', 31)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($preview->image_path);
});

it('explains when the Cloudflare daily free quota is exhausted', function () {
    config([
        'services.cloudflare.account_id' => 'test-account-id',
        'services.cloudflare.api_token' => 'test-cloudflare-token',
    ]);
    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'success' => false,
            'errors' => [['message' => 'Daily free allocation exceeded.']],
        ], 429),
    ]);

    $this->actingAs((new User)->forceFill(['id' => 22, 'role' => 'admin']));

    Livewire::test(AiOptimization::class)
        ->call('generateImage', 'Buat gambar es kopi susu.')
        ->assertSet('imageError', 'Kuota gratis Cloudflare Workers AI hari ini habis. Coba lagi setelah kuota harian direset.');
});

it('rejects image prompts longer than the Cloudflare model limit', function () {
    $this->actingAs((new User)->forceFill(['id' => 23, 'role' => 'admin']));

    Livewire::test(AiOptimization::class)
        ->call('generateImage', str_repeat('a', 2049))
        ->assertSet('imageError', 'Prompt kosong atau melebihi batas 2.048 karakter.');

    Http::assertNothingSent();
});
