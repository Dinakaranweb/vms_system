<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Proves where the "public" disk actually writes, and whether what it writes can
 * be read back over HTTP the way a browser would read it.
 *
 * Config alone is not proof. The disk can be pointed at S3 and still be broken:
 * bad credentials, a bucket policy that denies anonymous GET, or ACLs disabled so
 * 'visibility' => 'public' is silently ignored. Because the public disk runs with
 * 'throw' => false, every one of those fails without an exception, so the only
 * honest test is to write an object, fetch its URL, and read the status code.
 */
class StorageCheck extends Command
{
    protected $signature = 'storage:check {--keep : Leave the probe file in place instead of deleting it}';

    protected $description = 'Verify the public disk really points where you think, and that its URLs are readable';

    public function handle(): int
    {
        $config = config('filesystems.disks.public');
        $driver = $config['driver'] ?? '(none)';

        $this->newLine();
        $this->line('<fg=cyan;options=bold>Disk configuration</>');
        $this->line('  default disk         : ' . config('filesystems.default'));
        $this->line('  public disk driver   : <options=bold>' . $driver . '</>');
        $this->line('  PUBLIC_DISK_DRIVER   : ' . (env('PUBLIC_DISK_DRIVER') ?: '(unset -> local)'));

        if ($driver === 's3') {
            $this->line('  bucket               : ' . ($config['bucket'] ?: '<fg=red>(empty!)</>'));
            $this->line('  region               : ' . ($config['region'] ?: '<fg=red>(empty!)</>'));
            $this->line('  url                  : ' . ($config['url'] ?: '(null -> derived from bucket)'));
            $this->line('  endpoint             : ' . ($config['endpoint'] ?: '(null -> AWS)'));
            $this->line('  root (key prefix)    : ' . (($config['root'] ?? '') !== '' ? $config['root'] : '(none)'));
            $this->line('  visibility           : ' . ($config['visibility'] ?? '(unset -> private)'));
            $this->line('  key                  : ' . $this->maskKey((string) ($config['key'] ?? '')));
            $this->line('  secret               : ' . (($config['secret'] ?? '')
                ? '[set, ' . strlen($config['secret']) . ' chars]'
                : '<fg=red>(empty!)</>'));
        } else {
            $this->line('  root                 : ' . ($config['root'] ?? '?'));
            $this->line('  url                  : ' . ($config['url'] ?? '?'));
            $this->newLine();
            $this->warn('  Public disk is LOCAL, not S3. Set PUBLIC_DISK_DRIVER=s3 then run: php artisan config:clear');
        }

        // Rebuild the same disk with throw enabled so failures surface instead of
        // returning false. This is the whole point: the app's own disk hides them.
        try {
            $disk = Storage::build(array_merge($config, ['throw' => true]));
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Could not build the disk: ' . $e->getMessage());

            return self::FAILURE;
        }

        $path = 'storage-check/probe-' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.txt';
        $body = 'storage:check probe written ' . now()->toIso8601String();

        $this->newLine();
        $this->line('<fg=cyan;options=bold>Write test</>');

        try {
            $disk->put($path, $body);
            $this->line('  write                : <fg=green>OK</> ' . $path);
        } catch (Throwable $e) {
            $this->line('  write                : <fg=red>FAILED</>');
            $this->newLine();
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->newLine();
            $this->line('  Most likely: wrong AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY, a bucket name');
            $this->line('  that does not exist, the wrong region, or an IAM policy without s3:PutObject.');

            return self::FAILURE;
        }

        try {
            $readBack = $disk->get($path);
            $this->line('  read back (API)      : ' . ($readBack === $body
                ? '<fg=green>OK</> content matches'
                : '<fg=red>MISMATCH</>'));
        } catch (Throwable $e) {
            $this->line('  read back (API)      : <fg=red>FAILED</> ' . $e->getMessage());
        }

        $url = $disk->url($path);
        $this->newLine();
        $this->line('<fg=cyan;options=bold>URL test</>');
        $this->line('  Storage::url()       : ' . $url);

        $exit = self::SUCCESS;

        if (! str_starts_with($url, 'http')) {
            $this->line('  http fetch           : <fg=yellow>SKIPPED</> (url is relative, not absolute)');
            $this->newLine();
            $this->warn('  A relative URL means the disk has no "url" set. On S3 set AWS_URL; note an');
            $this->warn('  empty "AWS_URL=" line counts as set-but-blank unless coerced with ?: null.');
            $exit = self::FAILURE;
        } else {
            try {
                $res = Http::timeout(20)->withoutVerifying()->get($url);
                $code = $res->status();

                if ($res->successful()) {
                    $this->line('  http fetch           : <fg=green>' . $code . ' OK</> (' . strlen($res->body()) . ' bytes)');
                    $this->line('  content matches      : ' . (trim($res->body()) === $body ? '<fg=green>yes</>' : '<fg=yellow>no</>'));
                } else {
                    $this->line('  http fetch           : <fg=red>' . $code . '</>');
                    $exit = self::FAILURE;
                    $this->newLine();
                    $this->explain($code, $driver);
                    $snippet = trim(substr($res->body(), 0, 400));
                    if ($snippet !== '') {
                        $this->newLine();
                        $this->line('  <fg=gray>response body:</>');
                        $this->line('  ' . str_replace("\n", "\n  ", $snippet));
                    }
                }
            } catch (Throwable $e) {
                $this->line('  http fetch           : <fg=red>ERROR</> ' . $e->getMessage());
                $exit = self::FAILURE;
            }
        }

        if ($this->option('keep')) {
            $this->newLine();
            $this->line('  probe left in place (--keep): ' . $path);
        } else {
            try {
                $disk->delete($path);
                $this->newLine();
                $this->line('  probe cleaned up     : <fg=green>OK</>');
            } catch (Throwable $e) {
                $this->newLine();
                $this->warn('  could not delete probe: ' . $e->getMessage() . ' - remove ' . $path . ' by hand');
            }
        }

        $this->newLine();
        $this->line($exit === self::SUCCESS
            ? '<fg=green;options=bold>PASS</> - the public disk writes to ' . $driver . ' and its URLs are publicly readable.'
            : '<fg=red;options=bold>FAIL</> - see above.');
        $this->newLine();

        return $exit;
    }

    private function explain(int $code, string $driver): void
    {
        if ($code === 403 && $driver === 's3') {
            $this->line('  403 from S3 means the request reached AWS and was denied. Either:');
            $this->line('   a) Block Public Access is still on for the bucket or the account; or');
            $this->line('   b) Object Ownership is "Bucket owner enforced", which disables ACLs, so');
            $this->line('      visibility=public is ignored and every object stays private.');
            $this->line('      Fix with a bucket policy granting s3:GetObject, not with ACLs.');
            $this->line('  Note S3 also answers 403 (not 404) for a missing key when the caller has');
            $this->line('  no s3:ListBucket - but the write above succeeded, so the object does exist.');
        } elseif ($code === 403) {
            $this->line('  403 on a local disk URL is usually the framework own storage.local route:');
            $this->line('  the local disk has serve=true and no visibility key, so ServeFile aborts');
            $this->line('  403 when APP_ENV is not production (404 when it is). It means the file was');
            $this->line('  not found at public/storage - check that `php artisan storage:link` ran.');
        } elseif ($code === 404) {
            $this->line('  404 means the URL resolved to a host that has no such file. Check AWS_URL');
            $this->line('  matches the bucket and region, and that AWS_ROOT matches the key prefix.');
        } elseif ($code === 301 || $code === 307) {
            $this->line('  A redirect usually means AWS_DEFAULT_REGION does not match the bucket real');
            $this->line('  region. Confirm the region in the S3 console and correct it.');
        }
    }

    private function maskKey(string $key): string
    {
        if ($key === '') {
            return '<fg=red>(empty!)</>';
        }

        return strlen($key) <= 8
            ? '<fg=red>' . $key . ' - too short to be a real AWS key</>'
            : substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4);
    }
}
