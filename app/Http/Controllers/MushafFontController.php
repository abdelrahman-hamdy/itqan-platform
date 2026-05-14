<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams KFGQPC QPC v4 per-page mushaf fonts to the browser for the
 * interactive Mushaf overlay.
 *
 * Files live under storage/app/public/mushaf/fonts/{page}.woff2 (one-time
 * deploy artifact — 604 files, ~75 MB total). Cache headers are aggressive
 * because page-font content never changes within a release.
 *
 * In nginx environments these files can be served directly without ever
 * hitting PHP; this controller is the fallback / canonical handler.
 */
class MushafFontController extends Controller
{
    public function show(int $page): Response|BinaryFileResponse
    {
        if ($page < 1 || $page > 604) {
            abort(404);
        }

        $relativePath = "mushaf/fonts/{$page}.woff2";
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $absolute = $disk->path($relativePath);

        return response()->file($absolute, [
            'Content-Type' => 'font/woff2',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
