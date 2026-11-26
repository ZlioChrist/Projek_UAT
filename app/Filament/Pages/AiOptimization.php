<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AiPromptPreview;
use App\Models\GeminiChatConversation;
use App\Services\CloudflareMarketingAssistant;
use App\Support\AiMarketingPrompts;
use App\Support\AuthenticatedUser;
use Filament\Pages\Page;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;
use Livewire\WithFileUploads;
use Throwable;

class AiOptimization extends Page
{
    use WithFileUploads;

    public array $chatMessages = [];

    public ?int $activeConversationId = null;

    public string $chatDraft = '';

    public $uploadedPhoto = null;

    public ?string $chatError = null;

    public ?string $imageError = null;

    public $previewPhoto = null;

    public ?string $previewError = null;

    public ?string $previewErrorForKey = null;

    public bool $isChatThinking = false;

    public bool $isImageThinking = false;

    protected static ?string $title = 'Automatisasi AI';

    protected static ?string $navigationLabel = 'Automatisasi AI';

    protected static ?string $navigationGroup = 'Fitur Tambahan';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.ai-optimization';

    public function mount(): void
    {
        $latestConversation = GeminiChatConversation::query()
            ->where('user_id', AuthenticatedUser::id())
            ->latest('updated_at')
            ->first();

        if ($latestConversation !== null) {
            $this->openConversation($latestConversation->id);
        }
    }

    protected function getViewData(): array
    {
        $previews = AiPromptPreview::query()
            ->where('user_id', AuthenticatedUser::id())
            ->get()
            ->keyBy('prompt_key');
        $prompts = array_map(function (array $prompt) use ($previews): array {
            $promptKey = Str::slug($prompt['title']);
            $preview = $previews->get($promptKey);

            return [
                ...$prompt,
                'key' => $promptKey,
                'image_url' => $preview === null
                    ? $prompt['image_url']
                    : route('admin.ai-prompt-previews.show', $promptKey),
                'has_custom_image' => $preview !== null,
            ];
        }, app(AiMarketingPrompts::class)->all());

        return [
            'prompts' => $prompts,
            'quickTools' => app(AiMarketingPrompts::class)->quickTools(),
            'conversations' => GeminiChatConversation::query()
                ->where('user_id', AuthenticatedUser::id())
                ->latest('updated_at')
                ->get(),
        ];
    }

    public function savePromptPreview(string $promptKey): bool
    {
        abort_unless(static::canAccess(), 403);
        $this->previewError = null;
        $this->previewErrorForKey = $promptKey;

        abort_unless($this->isValidPromptKey($promptKey), 404);

        $this->validate([
            'previewPhoto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $userId = AuthenticatedUser::id();
        $existingPreview = AiPromptPreview::query()
            ->where('user_id', $userId)
            ->where('prompt_key', $promptKey)
            ->first();
        $mimeType = $this->previewPhoto->getMimeType();
        $newPath = $this->previewPhoto->store('ai-prompt-previews/'.$userId, 'local');

        if (! is_string($newPath)) {
            $this->previewError = 'Foto pratinjau tidak dapat disimpan.';

            return false;
        }

        try {
            DB::transaction(function () use ($userId, $promptKey, $newPath, $mimeType): void {
                AiPromptPreview::query()->updateOrCreate(
                    ['user_id' => $userId, 'prompt_key' => $promptKey],
                    ['image_path' => $newPath, 'image_mime_type' => $mimeType],
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            report($exception);
            $this->previewError = 'Foto pratinjau gagal disimpan. Coba lagi.';

            return false;
        }

        if ($existingPreview !== null
            && $existingPreview->image_path !== $newPath
            && Storage::disk('local')->exists($existingPreview->image_path)
            && ! Storage::disk('local')->delete($existingPreview->image_path)) {
            report(new RuntimeException('An old AI prompt preview could not be deleted.'));
            $this->previewError = 'Foto baru sudah disimpan, tetapi foto lama tidak dapat dihapus.';
        }

        $this->previewPhoto = null;

        return true;
    }

    public function resetPromptPreview(string $promptKey): bool
    {
        abort_unless(static::canAccess(), 403);
        $this->previewError = null;
        $this->previewErrorForKey = $promptKey;

        abort_unless($this->isValidPromptKey($promptKey), 404);

        $preview = AiPromptPreview::query()
            ->where('user_id', AuthenticatedUser::id())
            ->where('prompt_key', $promptKey)
            ->first();

        if ($preview === null) {
            return true;
        }

        $preview->delete();
        if (Storage::disk('local')->exists($preview->image_path)
            && ! Storage::disk('local')->delete($preview->image_path)) {
            report(new RuntimeException('An AI prompt preview could not be deleted.'));
            $this->previewError = 'Gambar bawaan dipulihkan, tetapi foto lama tidak dapat dihapus.';

            return false;
        }

        return true;
    }

    private function isValidPromptKey(string $promptKey): bool
    {
        foreach (app(AiMarketingPrompts::class)->all() as $prompt) {
            if (Str::slug($prompt['title']) === $promptKey) {
                return true;
            }
        }

        return false;
    }

    public function sendChatMessage(CloudflareMarketingAssistant $assistant): void
    {
        abort_unless(static::canAccess(), 403);

        $this->chatError = null;
        $message = trim($this->chatDraft);

        if ($message === '' && $this->uploadedPhoto === null) {
            $this->chatError = 'Tulis pesan atau lampirkan foto terlebih dahulu.';

            return;
        }
        if (mb_strlen($message) > 4000) {
            $this->chatError = 'Pesan maksimal 4.000 karakter.';

            return;
        }

        $this->validate([
            'uploadedPhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (! $this->allowAiRequest()) {
            $this->chatError = 'Batas penggunaan tercapai. Coba lagi dalam satu menit.';

            return;
        }

        $this->isChatThinking = true;

        $conversation = $this->currentConversation();
        $messages = $conversation === null
            ? []
            : $conversation->messages()
                ->latest('id')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(fn ($item): array => ['role' => $item->role, 'text' => mb_substr($item->content, 0, 4000)])
                ->values()
                ->all();

        if ($message !== '') {
            $messages[] = ['role' => 'user', 'text' => $message];
        } elseif ($this->uploadedPhoto !== null) {
            $messages[] = ['role' => 'user', 'text' => 'Tolong analisis foto yang saya lampirkan dan berikan saran praktis untuk kedai kopi saya.'];
        }

        $image = null;
        $uploadedImageContents = null;
        $uploadedImageMimeType = null;
        if ($this->uploadedPhoto !== null) {
            $uploadedImageContents = file_get_contents($this->uploadedPhoto->getRealPath());
            if ($uploadedImageContents === false) {
                $this->chatError = 'Foto tidak dapat dibaca. Coba pilih file lain.';

                return;
            }

            $uploadedImageMimeType = $this->uploadedPhoto->getMimeType();
            $image = [
                'mime_type' => $uploadedImageMimeType,
                'data' => base64_encode($uploadedImageContents),
            ];
        } elseif ($conversation !== null) {
            $lastImageMessage = $conversation->messages()
                ->whereNotNull('image_path')
                ->latest('id')
                ->first();
            if ($lastImageMessage !== null && Storage::disk('local')->exists($lastImageMessage->image_path)) {
                $imageContents = Storage::disk('local')->get($lastImageMessage->image_path);
                $image = [
                    'mime_type' => $lastImageMessage->image_mime_type,
                    'data' => base64_encode($imageContents),
                ];
            }
        }

        try {
            $answer = $assistant->chat($messages, $image);
        } catch (RuntimeException|RequestException|ConnectionException|UnexpectedValueException $exception) {
            $this->chatError = $this->aiErrorMessage($exception);

            return;
        } finally {
            $this->isChatThinking = false;
        }

        try {
            $this->persistTurn(
                $message !== '' ? $message : 'Tolong analisis foto yang saya lampirkan dan berikan saran praktis untuk kedai kopi saya.',
                $answer,
                $uploadedImageContents,
                $uploadedImageMimeType,
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->chatError = 'Jawaban AI sudah diterima, tetapi percakapan belum dapat disimpan. Coba kirim pesan lagi.';

            return;
        }
        $this->chatDraft = '';
        $this->uploadedPhoto = null;
        $this->chatError = null;
    }

    public function generateImage(string $prompt, CloudflareMarketingAssistant $assistant): void
    {
        abort_unless(static::canAccess(), 403);

        $this->imageError = null;
        $prompt = trim($prompt);

        if ($prompt === '' || mb_strlen($prompt) > 2048) {
            $this->imageError = 'Prompt kosong atau melebihi batas 2.048 karakter.';

            return;
        }
        if (! $this->allowAiRequest()) {
            $this->imageError = 'Batas penggunaan tercapai. Coba lagi dalam satu menit.';

            return;
        }

        $this->isImageThinking = true;

        try {
            $image = $assistant->generateImage($prompt);
        } catch (RuntimeException|RequestException|ConnectionException|UnexpectedValueException $exception) {
            $this->imageError = $this->aiErrorMessage($exception);

            return;
        } finally {
            $this->isImageThinking = false;
        }

        $imageData = substr($image['data_uri'], strpos($image['data_uri'], ',') + 1);
        $imageContents = base64_decode($imageData, true);
        if ($imageContents === false) {
            $this->imageError = 'Gambar hasil AI tidak dapat dibaca.';

            return;
        }

        try {
            $this->persistTurn($prompt, 'Gambar berhasil dibuat dari prompt ini.', $imageContents, $image['mime_type'], true);
        } catch (Throwable $exception) {
            report($exception);
            $this->imageError = 'Gambar AI sudah dibuat, tetapi belum dapat disimpan ke riwayat percakapan.';

            return;
        }
    }

    public function clearChat(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->activeConversationId = null;
        $this->chatMessages = [];
        $this->chatDraft = '';
        $this->chatError = null;
        $this->imageError = null;
        $this->uploadedPhoto = null;
        $this->isChatThinking = false;
        $this->isImageThinking = false;
    }

    public function openConversation(int $conversationId): void
    {
        abort_unless(static::canAccess(), 403);

        $conversation = GeminiChatConversation::query()
            ->where('user_id', AuthenticatedUser::id())
            ->findOrFail($conversationId);

        $this->activeConversationId = $conversation->id;
        $this->chatMessages = $conversation->messages()
            ->oldest('id')
            ->get()
            ->map(fn ($message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'text' => $message->content,
                'image_url' => $message->image_path === null
                    ? null
                    : route('admin.ai-chat-images.show', [$conversation->id, $message->id]),
                'mime_type' => $message->image_mime_type,
            ])
            ->all();
        $this->chatDraft = '';
        $this->chatError = null;
        $this->imageError = null;
        $this->uploadedPhoto = null;
    }

    public function deleteConversation(int $conversationId): void
    {
        abort_unless(static::canAccess(), 403);

        $conversation = GeminiChatConversation::query()
            ->where('user_id', AuthenticatedUser::id())
            ->with('messages')
            ->findOrFail($conversationId);

        foreach ($conversation->messages as $message) {
            if ($message->image_path !== null
                && Storage::disk('local')->exists($message->image_path)
                && ! Storage::disk('local')->delete($message->image_path)) {
                throw new RuntimeException('An AI chat image could not be deleted.');
            }
        }

        $conversation->delete();

        if ($this->activeConversationId === $conversationId) {
            $this->clearChat();
        }
    }

    private function currentConversation(): ?GeminiChatConversation
    {
        if ($this->activeConversationId === null) {
            return null;
        }

        return GeminiChatConversation::query()
            ->where('user_id', AuthenticatedUser::id())
            ->findOrFail($this->activeConversationId);
    }

    private function persistTurn(
        string $prompt,
        string $answer,
        ?string $imageContents = null,
        ?string $imageMimeType = null,
        bool $imageOnAssistantMessage = false,
    ): void
    {
        $storedImagePath = null;

        try {
            DB::transaction(function () use ($prompt, $answer, $imageContents, $imageMimeType, $imageOnAssistantMessage, &$storedImagePath): void {
                $conversation = $this->currentConversation();
                if ($conversation === null) {
                    $conversation = GeminiChatConversation::query()->create([
                        'user_id' => AuthenticatedUser::id(),
                        'title' => mb_substr($prompt !== '' ? $prompt : 'Percakapan AI', 0, 120),
                    ]);
                }

                if ($imageContents !== null && $imageMimeType !== null) {
                    $extension = match ($imageMimeType) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => throw new UnexpectedValueException('Unsupported image type for chat history.'),
                    };
                    $storedImagePath = 'gemini-chat/'.AuthenticatedUser::id().'/'.Str::uuid().'.'.$extension;
                    if (! Storage::disk('local')->put($storedImagePath, $imageContents)) {
                        throw new RuntimeException('The chat image could not be saved.');
                    }
                }

                $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $prompt,
                    'image_path' => $storedImagePath !== null && ! $imageOnAssistantMessage ? $storedImagePath : null,
                    'image_mime_type' => $storedImagePath !== null && ! $imageOnAssistantMessage ? $imageMimeType : null,
                ]);
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $answer,
                    'image_path' => $storedImagePath !== null && $imageOnAssistantMessage ? $storedImagePath : null,
                    'image_mime_type' => $storedImagePath !== null && $imageOnAssistantMessage ? $imageMimeType : null,
                ]);
                $conversation->touch();
                $this->activeConversationId = $conversation->id;
            });
        } catch (Throwable $exception) {
            if ($storedImagePath !== null) {
                Storage::disk('local')->delete($storedImagePath);
            }

            throw $exception;
        }

        $this->openConversation($this->activeConversationId);
    }

    private function allowAiRequest(): bool
    {
        $rateLimitKey = 'ai-marketing:'.AuthenticatedUser::id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 8)) {
            return false;
        }

        RateLimiter::hit($rateLimitKey, 60);

        return true;
    }

    private function aiErrorMessage(RuntimeException|RequestException|ConnectionException|UnexpectedValueException $exception): string
    {
        if ($exception instanceof RequestException) {
            $providerError = strtolower((string) $exception->response->body());

            return match ($exception->response->status()) {
                401, 403 => 'Cloudflare menolak token atau akses Workers AI. Periksa token dan Account ID.',
                429 => 'Kuota gratis Cloudflare Workers AI hari ini habis. Coba lagi setelah kuota harian direset.',
                default => 'Cloudflare Workers AI gagal memproses permintaan. Coba lagi beberapa saat lagi.',
            };
        }

        if ($exception instanceof ConnectionException) {
            return 'Tidak dapat terhubung ke Cloudflare Workers AI. Periksa koneksi internet server.';
        }

        if ($exception instanceof UnexpectedValueException) {
            return 'Cloudflare Workers AI mengirim jawaban dengan format yang tidak dikenali.';
        }

        return 'Cloudflare Workers AI belum siap. Periksa Account ID dan API token di .env.';
    }

    public static function canAccess(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [
            UserRole::Developer,
            UserRole::Admin,
            UserRole::Roastery,
        ], true);
    }
}
