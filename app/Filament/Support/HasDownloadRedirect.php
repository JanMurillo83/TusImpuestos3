<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;

trait HasDownloadRedirect
{
    protected ?string $downloadFilename = null;

    protected function setDownloadFilename(?string $filename): void
    {
        $this->downloadFilename = $filename;
    }

    protected function getDownloadRedirectUrl(): ?string
    {
        if (! $this->downloadFilename) {
            return null;
        }

        $tenantSlug = request()->route('tenantSlug') ?? Filament::getTenant()?->id;
        if (! $tenantSlug) {
            return null;
        }

        return route('tiadmin.download.redirect', [
            'tenantSlug' => $tenantSlug,
            'file' => $this->downloadFilename,
            'return' => static::getResource()::getUrl('index', [], false),
        ]);
    }
}
