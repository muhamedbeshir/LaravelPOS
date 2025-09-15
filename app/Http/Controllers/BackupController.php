<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File as ValidationFile;
use Ifsnop\Mysqldump\Mysqldump;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

final class BackupController extends Controller
{
    // Middleware is defined in routes/web.php

    // Helper to get backup destination details
    private function getBackupDestination(): BackupDestination
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $appName = config('app.name', 'LaravelPos');
        return BackupDestination::create($diskName, $appName);
    }

    // Helper to get temporary directory path
    private function getTempDirectory(): string
    {
        // Use Spatie's config or fallback to storage/app/backup-temp
        $tempDir = config('backup.temporary_directory', storage_path('app/backup-temp'));
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        return $tempDir;
    }

    public function index(): View
    {
        $backupDestination = $this->getBackupDestination();
        $backups = $backupDestination->backups()->sortByDesc(function (Backup $backup) {
            return $backup->date()->timestamp;
        });

        return view('backups.index', compact('backups'));
    }

    /**
     * Creates a new backup synchronously using mysqldump-php.
     */
    public function create(): RedirectResponse
    {
        $tempDir = $this->getTempDirectory();
        $timestamp = now()->format('Y-m-d-H-i-s');
        $dbDumpFilename = "db-dump-{$timestamp}.sql";
        $tempDbDumpPath = rtrim($tempDir, '/') . '/' . $dbDumpFilename;
        $finalZipFilename = "backup-{$timestamp}.zip";
        
        // Get DB connection details
        $connectionName = config('database.default');
        $dbConfig = config("database.connections.{$connectionName}");

        if ($dbConfig['driver'] !== 'mysql') {
            return redirect()->route('backups.index')->with('error', __('This backup method currently only supports MySQL.'));
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            $dbConfig['host'] ?? '127.0.0.1',
            (int) ($dbConfig['port'] ?? 3306),
            $dbConfig['database']
        );
        $user = $dbConfig['username'];
        $pass = $dbConfig['password'];

        Log::info("Starting synchronous database backup creation using mysqldump-php to: {$tempDbDumpPath}");

        try {
            // 1. Dump the database using mysqldump-php
            $dumpSettings = [
                'compress' => Mysqldump::NONE, // No compression on the SQL dump itself
                'add-drop-table' => true,
                'single-transaction' => true, // Recommended for InnoDB
                'lock-tables' => false, // Not needed with single-transaction
                'default-character-set' => Mysqldump::UTF8MB4, // Use UTF8MB4
                'hex-blob' => true, // Dump binary blobs as hex
                'skip-triggers' => false,
                'routines' => true, // Include stored procedures/functions
                'events' => true, // Include events
                'skip-comments' => true, // Optional: reduce file size
                'skip-dump-date' => true, // Optional: reduce file size
            ];
            $pdoSettings = []; // Use defaults

            $dumper = new Mysqldump($dsn, $user, $pass, $dumpSettings, $pdoSettings);
            $dumper->start($tempDbDumpPath);
            Log::info("Database dump completed: {$tempDbDumpPath}");

            // 2. Create a Zip archive containing the dump
            $tempZipPath = rtrim($tempDir, '/') . '/' . $finalZipFilename;
            $zip = new ZipArchive();
            if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                 throw new Exception("Cannot open <$tempZipPath> for writing zip.");
            }
            $zip->addFile($tempDbDumpPath, $dbDumpFilename);
            $zip->close();
            Log::info("Created zip archive: {$tempZipPath}");

            // 3. Move the Zip archive to the final destination disk
            $destination = $this->getBackupDestination();
            $destinationDisk = Storage::disk($destination->diskName());
            $finalPath = $destination->backupName() . '/' . $finalZipFilename;

            $stream = fopen($tempZipPath, 'r+');
            $destinationDisk->put($finalPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            Log::info("Moved backup zip to final destination: {$destination->diskName()} / {$finalPath}");

             // 4. Clean up temporary files
             File::delete($tempDbDumpPath);
             File::delete($tempZipPath);
             Log::info("Cleaned up temporary files.");

            return redirect()->route('backups.index')->with('success', __('تم إنشاء النسخة الاحتياطية بنجاح باستخدام PHP.'));

        } catch (\Throwable $e) {
            Log::error("PHP Backup creation failed: {$e->getMessage()}", ['exception' => $e]);
             // Clean up temp files on error too
             if (File::exists($tempDbDumpPath)) File::delete($tempDbDumpPath);
             if (File::exists($tempZipPath)) File::delete($tempZipPath);
            return redirect()->route('backups.index')->with('error', __('فشل إنشاء النسخة الاحتياطية: ') . $e->getMessage());
        }
    }

    /**
     * Handles the upload of a backup zip file.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => [
                'required',
                ValidationFile::types(['zip'])->max(config('backup.backup.source.files.max_upload_size_mb', 1024) * 1024), // Max size in KB (e.g., 1GB)
            ],
        ], [
            'backup_file.required' => __('الرجاء اختيار ملف نسخة احتياطية.'),
            'backup_file.mimes' => __('يجب أن يكون الملف من نوع zip.'),
            'backup_file.max' => __('حجم الملف كبير جدًا.'),
        ]);

        try {
            $file = $request->file('backup_file');
            $filename = $file->getClientOriginalName(); // Use the original filename

            $destination = $this->getBackupDestination();
            $destinationDisk = Storage::disk($destination->diskName());
            $finalPath = $destination->backupName() . '/' . $filename;

            Log::info("Attempting to upload backup file '{$filename}' to: {$destination->diskName()} / {$finalPath}");

            // Store the uploaded file directly
            $path = $file->storeAs(
                $destination->backupName(), // Directory within the disk
                $filename,                  // Original filename
                $destination->diskName()     // Disk name
            );

            if (!$path) {
                throw new Exception('Failed to store the uploaded file.');
            }

            Log::info("Successfully uploaded backup file to: {$path}");
            return redirect()->route('backups.index')->with('success', __('تم رفع ملف النسخة الاحتياطية بنجاح.'));

        } catch (\Throwable $e) {
            Log::error("Backup upload failed: {$e->getMessage()}", ['exception' => $e]);
            return redirect()->route('backups.index')->with('error', __('فشل رفع ملف النسخة الاحتياطية: ') . $e->getMessage());
        }
    }

    /**
     * Downloads a specific backup file.
     */
    public function download(string $filename): StreamedResponse|RedirectResponse
    {
        try {
            $destination = $this->getBackupDestination();
            $backup = $destination->backups()->first(function (Backup $backup) use ($filename) {
                return basename($backup->path()) === $filename;
            });

            if (!$backup) {
                return redirect()->route('backups.index')->with('error', __('لم يتم العثور على ملف النسخة الاحتياطية.'));
            }

            return Storage::disk($destination->diskName())->download($backup->path());

        } catch (\Throwable $e) {
            Log::error("Backup download failed for {$filename}: {$e->getMessage()}", ['exception' => $e]);
            return redirect()->route('backups.index')->with('error', __('فشل تنزيل النسخة الاحتياطية: ') . $e->getMessage());
        }
    }

    /**
     * Deletes a specific backup file.
     */
    public function destroy(string $filename): RedirectResponse
    {
        Log::info("Attempting to delete backup file: {$filename}");
        try {
            $destination = $this->getBackupDestination();
            $backup = $destination->backups()->first(function (Backup $backup) use ($filename) {
                return basename($backup->path()) === $filename;
            });

            if (!$backup) {
                return redirect()->route('backups.index')->with('error', __('لم يتم العثور على ملف النسخة الاحتياطية.'));
            }

            $backup->delete();
            Log::info("Backup file deleted: {$filename}");

            return redirect()->route('backups.index')->with('success', __('تم حذف ملف النسخة الاحتياطية بنجاح.'));

        } catch (\Throwable $e) {
            Log::error("Backup deletion failed for {$filename}: {$e->getMessage()}", ['exception' => $e]);
            return redirect()->route('backups.index')->with('error', __('فشل حذف النسخة الاحتياطية: ') . $e->getMessage());
        }
    }

    /**
     * Restores a specific backup file.
     * WARNING: This is a destructive operation.
     * NOTE: Restore still depends on `mysql` CLI for importing the .sql file.
     * A pure PHP restore is much more complex.
     */
    public function restore(string $filename): RedirectResponse
    {
        Log::warning("Starting backup restore process for: {$filename}");
        $destination = $this->getBackupDestination();
        $tempDir = $this->getTempDirectory();
        $tempZipPath = null;
        $tempSqlPath = null;

        try {
            // Find the backup
            $backup = $destination->backups()->first(function (Backup $backup) use ($filename) {
                return basename($backup->path()) === $filename;
            });

            if (!$backup) {
                throw new Exception('لم يتم العثور على ملف النسخة الاحتياطية.');
            }

            // 1. Download/Copy backup zip to temporary location
            $tempZipPath = rtrim($tempDir, '/') . '/' . $filename;
            $stream = Storage::disk($destination->diskName())->readStream($backup->path());
            file_put_contents($tempZipPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            Log::info("Copied backup zip to temporary location: {$tempZipPath}");

            // 2. Extract the .sql file from the zip
            $zip = new ZipArchive();
            if ($zip->open($tempZipPath) !== TRUE) {
                throw new Exception("Cannot open zip archive: {$tempZipPath}");
            }
            // Assuming the sql file is the first one (or only one)
            $sqlFilename = $zip->getNameIndex(0);
            if (!$sqlFilename || !str_ends_with(strtolower($sqlFilename), '.sql')) {
                 throw new Exception("Could not find .sql file inside the zip archive.");
            }
            $tempSqlPath = rtrim($tempDir, '/') . '/' . $sqlFilename;
            if (!$zip->extractTo($tempDir, $sqlFilename)) {
                throw new Exception("Failed to extract SQL file from zip.");
            }
            $zip->close();
            Log::info("Extracted SQL file to: {$tempSqlPath}");

            // 3. Put the application into maintenance mode
            Log::info('Entering maintenance mode for restore.');
            Artisan::call('down', ['--secret' => 'restore-secret']);

            // 4. Clear existing database (using mysql CLI is simpler here)
            // If mysql CLI is unavailable, this part needs a PHP alternative (more complex)
            Log::warning('Wiping database before restore...');
             Artisan::call('db:wipe', ['--force' => true]);
             Log::info('Database wipe completed.');
             
             // 5. Import the SQL file (using mysql CLI is simpler)
             // A pure PHP import requires parsing and executing SQL statements manually.
             // This is complex and prone to errors/timeouts.
            Log::info("Importing SQL dump using mysql command-line tool: {$tempSqlPath}");
            $connectionName = config('database.default');
            $dbConfig = config("database.connections.{$connectionName}");
            $command = sprintf(
                'mysql --host="%s" --port="%d" --user="%s" %s --database="%s" < "%s"',
                $dbConfig['host'] ?? '127.0.0.1',
                (int) ($dbConfig['port'] ?? 3306),
                $dbConfig['username'],
                !empty($dbConfig['password']) ? sprintf('--password="%s"', $dbConfig['password']) : '',
                $dbConfig['database'],
                $tempSqlPath
            );
            
            // Execute the command (requires `mysql` CLI to be available)
            $returnVar = null;
            $output = [];
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("Database import failed. Exit Code: {$returnVar}. Output: " . implode("\n", $output));
            }
            Log::info("Database import completed.");

            // 6. Optional: Run migrations
            Log::info('Running migrations after restore...');
            Artisan::call('migrate', ['--force' => true]);
            Log::info('Migrations finished.');

            // 7. Bring the application back up
            Log::info('Exiting maintenance mode.');
            Artisan::call('up');

             // 8. Clean up temporary files
             if (File::exists($tempSqlPath)) File::delete($tempSqlPath);
             if (File::exists($tempZipPath)) File::delete($tempZipPath);
             Log::info("Cleaned up temporary files.");

            Log::warning("Backup restore process completed successfully for: {$filename}");
            return redirect()->route('backups.index')->with('success', __('تمت استعادة النسخة الاحتياطية بنجاح.'));

        } catch (\Throwable $e) {
            Log::error("Backup restore failed for {$filename}: {$e->getMessage()}", ['exception' => $e]);
             // Clean up temp files on error
             if ($tempSqlPath && File::exists($tempSqlPath)) File::delete($tempSqlPath);
             if ($tempZipPath && File::exists($tempZipPath)) File::delete($tempZipPath);
            // Ensure application is brought back up even if restore fails
            Log::warning('Attempting to exit maintenance mode after failed restore.');
            Artisan::call('up');
            return redirect()->route('backups.index')->with('error', __('فشل استعادة النسخة الاحتياطية: ') . $e->getMessage());
        }
    }
}
