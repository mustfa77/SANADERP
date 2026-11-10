<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Zip backups of the database and uploaded files, kept on the private
 * "local" disk under backups/.
 */
class BackupService
{
    public const DIRECTORY = 'backups';

    /**
     * Backup file names: backup-{manual|auto}-YYYY-MM-DD-HHMMSS.zip
     */
    public const NAME_PATTERN = 'backup-(manual|auto)-\d{4}-\d{2}-\d{2}-\d{6}\.zip';

    /**
     * Create a backup and return its file name.
     *
     * @throws RuntimeException
     */
    public function create(string $type = 'manual'): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIRECTORY);

        $name = sprintf('backup-%s-%s.zip', $type, now()->format('Y-m-d-His'));
        $zipPath = $disk->path(self::DIRECTORY.'/'.$name);
        $databaseDump = $this->dumpDatabase();

        try {
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create the backup file.');
            }

            $zip->addFile($databaseDump['path'], 'database/'.$databaseDump['name']);

            $uploads = storage_path('app/public');
            if (File::isDirectory($uploads)) {
                foreach (File::allFiles($uploads) as $file) {
                    $zip->addFile($file->getPathname(), 'uploads/'.str_replace('\\', '/', $file->getRelativePathname()));
                }
            }

            $zip->addFromString('manifest.json', json_encode([
                'app' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'type' => $type,
                'database_driver' => DB::connection()->getDriverName(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $zip->close();
        } finally {
            File::delete($databaseDump['path']);
        }

        return $name;
    }

    /**
     * @return Collection<int, array{name: string, type: string, size: int, created_at: Carbon}>
     */
    public function all(): Collection
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::DIRECTORY))
            ->map(fn (string $path): string => basename($path))
            ->filter(fn (string $name): bool => $this->isValidName($name))
            ->map(fn (string $name): array => [
                'name' => $name,
                'type' => str_contains($name, '-auto-') ? 'auto' : 'manual',
                'size' => $disk->size(self::DIRECTORY.'/'.$name),
                'created_at' => now()->createFromTimestamp($disk->lastModified(self::DIRECTORY.'/'.$name)),
            ])
            ->sortByDesc('name')
            ->values();
    }

    public function isValidName(string $name): bool
    {
        return (bool) preg_match('/^'.self::NAME_PATTERN.'$/', $name);
    }

    public function exists(string $name): bool
    {
        return $this->isValidName($name) && Storage::disk('local')->exists(self::DIRECTORY.'/'.$name);
    }

    public function path(string $name): string
    {
        return Storage::disk('local')->path(self::DIRECTORY.'/'.$name);
    }

    public function delete(string $name): void
    {
        if ($this->exists($name)) {
            Storage::disk('local')->delete(self::DIRECTORY.'/'.$name);
        }
    }

    public function hasAutomaticBackupToday(): bool
    {
        $today = 'backup-auto-'.now()->format('Y-m-d').'-';

        return $this->all()->contains(fn (array $backup): bool => str_starts_with($backup['name'], $today));
    }

    /**
     * Keep only the newest $keep backups. Returns how many were deleted.
     */
    public function prune(int $keep): int
    {
        $old = $this->all()->slice(max($keep, 1));
        $old->each(fn (array $backup) => $this->delete($backup['name']));

        return $old->count();
    }

    /**
     * Dump the database to a temporary file.
     *
     * @return array{path: string, name: string}
     *
     * @throws RuntimeException
     */
    private function dumpDatabase(): array
    {
        $connection = DB::connection();
        $directory = storage_path('app/backup-temp');
        File::ensureDirectoryExists($directory);

        return match ($connection->getDriverName()) {
            'sqlite' => $this->dumpSqlite($directory),
            'mysql', 'mariadb' => $this->dumpMysql($directory, $connection->getConfig()),
            default => throw new RuntimeException('Unsupported database driver for backups.'),
        };
    }

    /**
     * @return array{path: string, name: string}
     */
    private function dumpSqlite(string $directory): array
    {
        $path = $directory.'/database-'.uniqid().'.sqlite';

        // A consistent copy, even while the application is in use.
        DB::statement('VACUUM INTO ?', [$path]);

        return ['path' => $path, 'name' => 'database.sqlite'];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{path: string, name: string}
     */
    private function dumpMysql(string $directory, array $config): array
    {
        $path = $directory.'/database-'.uniqid().'.sql';

        $result = Process::env(['MYSQL_PWD' => (string) ($config['password'] ?? '')])->run([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--routines',
            '--result-file='.$path,
            $config['database'],
        ]);

        if ($result->failed()) {
            File::delete($path);

            throw new RuntimeException(trim($result->errorOutput()) ?: 'mysqldump failed.');
        }

        return ['path' => $path, 'name' => 'database.sql'];
    }
}
