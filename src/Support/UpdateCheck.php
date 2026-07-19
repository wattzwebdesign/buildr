<?php

namespace Buildr\Support;

use Illuminate\Support\Facades\Http;

/** Daily-cached check of the buildr repo for a newer engine commit. */
final class UpdateCheck
{
    public static function available(?string $installed = null): bool
    {
        $installed ??= self::installedRef();
        if (! $installed) {
            return false;
        }

        $latest = cache()->remember('buildr.latest_ref', now()->addDay(), function () {
            try {
                $response = Http::timeout(4)
                    ->withHeaders(['Accept' => 'application/vnd.github.sha'])
                    ->get('https://api.github.com/repos/wattzwebdesign/buildr/commits/main');

                return $response->successful() ? trim($response->body()) : null;
            } catch (\Throwable) {
                return null;
            }
        });

        return $latest !== null
            && ! str_starts_with($latest, $installed)
            && ! str_starts_with($installed, $latest);
    }

    public static function installedRef(): ?string
    {
        try {
            return \Composer\InstalledVersions::getReference('buildr/buildr');
        } catch (\Throwable) {
            return null;
        }
    }
}
