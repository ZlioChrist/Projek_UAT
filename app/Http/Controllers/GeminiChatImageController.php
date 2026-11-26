<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AiOptimization;
use App\Models\GeminiChatConversation;
use App\Models\GeminiChatMessage;
use App\Support\AuthenticatedUser;
use Illuminate\Support\Facades\Storage;

class GeminiChatImageController extends Controller
{
    public function __invoke(GeminiChatConversation $conversation, GeminiChatMessage $message)
    {
        abort_unless(AiOptimization::canAccess(), 403);
        abort_unless((string) $conversation->user_id === (string) AuthenticatedUser::id(), 404);
        abort_unless((string) $message->conversation_id === (string) $conversation->id, 404);
        abort_if($message->image_path === null || ! Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path, null, [
            'Content-Type' => $message->image_mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
