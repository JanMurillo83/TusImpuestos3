<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TiadminDownloadController extends Controller
{
    public function download(Request $request, string $tenantSlug)
    {
        $file = $this->sanitizeFile($request->query('file'));
        if (! $file) {
            abort(404);
        }

        $path = public_path('TMPCFDI/' . $file);
        if (! File::exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function downloadAndRedirect(Request $request, string $tenantSlug)
    {
        $file = $this->sanitizeFile($request->query('file'));
        if (! $file) {
            abort(404);
        }

        $downloadUrl = route('tiadmin.download', [
            'tenantSlug' => $tenantSlug,
            'file' => $file,
        ]);

        $returnUrl = $this->sanitizeReturnUrl($request->query('return'), $tenantSlug);

        return view('tiadmin.download-redirect', [
            'downloadUrl' => $downloadUrl,
            'returnUrl' => $returnUrl,
        ]);
    }

    private function sanitizeFile(?string $file): ?string
    {
        if (! $file) {
            return null;
        }

        $file = basename($file);
        if ($file === '' || str_contains($file, '..')) {
            return null;
        }

        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        return $file;
    }

    private function sanitizeReturnUrl(?string $returnUrl, string $tenantSlug): string
    {
        if (! $returnUrl) {
            return url('/' . $tenantSlug . '/tiadmin');
        }

        if (str_starts_with($returnUrl, 'http')) {
            return url('/' . $tenantSlug . '/tiadmin');
        }

        if (! str_starts_with($returnUrl, '/' . $tenantSlug . '/')) {
            return url('/' . $tenantSlug . '/tiadmin');
        }

        return $returnUrl;
    }
}
