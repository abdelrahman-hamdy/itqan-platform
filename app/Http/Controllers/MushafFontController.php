<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves Mushaf assets to the in-meeting interactive viewer:
 *
 *   1. `show(page)` — streams KFGQPC QPC v4 per-page WOFF2 fonts from
 *      `storage/app/public/mushaf/fonts/{page}.woff2`. Aggressive cache
 *      headers because per-page font content is immutable within a
 *      release. In production these can be served directly by nginx.
 *
 *   2. `page(page)` — returns per-page JSON containing either the KFGQPC
 *      glyph payload (preferred, pixel-identical to mobile) OR a
 *      verse-text fallback if only `pages/{page}.json` is present. If
 *      neither is bundled, returns 404 and the JS module falls back to
 *      the client-side surah catalog.
 *
 * No assets need to be present for the meeting to work — the JS gracefully
 * downgrades through several fallback layers.
 */
class MushafFontController extends Controller
{
    private const MIN_PAGE = 1;

    private const MAX_PAGE = 604;

    public function show(int $page): Response|BinaryFileResponse
    {
        if ($page < self::MIN_PAGE || $page > self::MAX_PAGE) {
            abort(404);
        }

        $relativePath = "mushaf/fonts/{$page}.woff2";
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        return response()->file($disk->path($relativePath), [
            'Content-Type' => 'font/woff2',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function page(int $page): JsonResponse
    {
        if ($page < self::MIN_PAGE || $page > self::MAX_PAGE) {
            abort(404);
        }

        $disk = Storage::disk('public');
        $relativePath = "mushaf/pages/{$page}.json";

        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $body = $disk->get($relativePath);
        $decoded = json_decode($body ?? '', true);

        if (! is_array($decoded)) {
            abort(404);
        }

        return response()
            ->json($decoded)
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }
}
