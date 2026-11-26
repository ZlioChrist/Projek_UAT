<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AiOptimization;
use App\Models\AiPromptPreview;
use App\Support\AuthenticatedUser;
use Illuminate\Support\Facades\Storage;

class AiPromptPreviewController extends Controller
{
    public function __invoke(string $promptKey)
    {
        abort_unless(AiOptimization::canAccess(), 403);

        $preview = AiPromptPreview::query()
            ->where('user_id', AuthenticatedUser::id())
            ->where('prompt_key', $promptKey)
            ->firstOrFail();

        abort_unless(in_array($preview->image_mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_if(! Storage::disk('local')->exists($preview->image_path), 404);

        return Storage::disk('local')->response($preview->image_path, null, [
            'Content-Type' => $preview->image_mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
