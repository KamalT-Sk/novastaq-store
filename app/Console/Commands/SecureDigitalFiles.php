<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecureDigitalFiles extends Command
{
    protected $signature = 'store:secure-digital-files';

    protected $description = 'Move existing paid downloads to private storage, preserving order paths';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $paths = Product::whereNotNull('digital_file')->pluck('digital_file')
            ->merge(OrderItem::whereNotNull('digital_file')->pluck('digital_file'))
            ->merge($public->allFiles('digital'))->unique()->filter();
        $failed = false;
        foreach ($paths as $path) {
            if (! str_starts_with($path, 'digital/') || str_contains($path, '..')) {
                $this->error('Unexpected download path: '.$path);
                $failed = true;

                continue;
            }
            if (! $public->exists($path)) {
                if (! $private->exists($path)) {
                    $this->error('Missing download: '.$path);
                    $failed = true;
                }

                continue;
            }
            // Verify both copies before removing the public copy. Re-running is safe.
            $content = $public->get($path);
            if (! $private->exists($path) && ! $private->put($path, $content)) {
                $this->error('Could not copy: '.$path);
                $failed = true;

                continue;
            }
            if (! hash_equals(hash('sha256', $content), hash('sha256', $private->get($path)))) {
                $this->error('Copy verification failed: '.$path);
                $failed = true;

                continue;
            }
            if (! $public->delete($path)) {
                $this->error('Could not remove public copy: '.$path);
                $failed = true;

                continue;
            }
            $this->info('Secured: '.$path);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
