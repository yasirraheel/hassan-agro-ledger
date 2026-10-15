<?php

namespace App\Http\Controllers\Admin;

use Google\Client;
use Google\Service\Drive;
use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $settings = get_option('backup_settings');
        $backups = $this->getBackupHistory();

        return view('admin.backup.index', compact('settings', 'backups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination' => 'required|in:local,cloud',
            'cloud_provider' => 'required_if:destination,cloud',
        ]);

        $settings = [
            'destination' => $request->destination,
            'auto_backup' => $request->has('auto_backup') ? 1 : 0,
            'auto_backup_interval' => $request->auto_backup_interval ?? 2,
            'auto_backup_unit' => $request->auto_backup_unit ?? 'days',
        ];

        if ($request->destination === 'cloud') {
            $settings['cloud_provider'] = $request->cloud_provider;
            $settings['cloud_path'] = $request->cloud_path;

            if ($request->cloud_provider === 'google_drive') {
                $settings['folder_id'] = $request->folder_id;
                $settings['refresh_token'] = $request->refresh_token;
                $settings['client_id'] = $request->client_id;
                $settings['client_secret'] = $request->client_secret;
            } elseif ($request->cloud_provider === 'dropbox') {
                $settings['dropbox_access_token'] = $request->dropbox_access_token;
            } elseif ($request->cloud_provider === 's3') {
                $settings['s3_bucket'] = $request->s3_bucket;
                $settings['s3_region'] = $request->s3_region;
                $settings['s3_access_key'] = $request->s3_access_key;
                $settings['s3_secret_key'] = $request->s3_secret_key;
                $settings['s3_endpoint'] = $request->s3_endpoint;
                $settings['s3_path_style'] = $request->has('s3_path_style') ? 1 : 0;
            }
        }

        Cache::forget('backup_settings');

        Option::updateOrCreate(
            ['key' => 'backup_settings'],
            ['value' => $settings]
        );

        return response()->json(__('Backup settings saved successfully.'));
    }

    public function create(): RedirectResponse
    {
        $settings = get_option('backup_settings');
        $destination = $settings['destination'] ?? 'local';

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->ensureBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!$this->createDatabaseBackup($filepath)) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Failed to create database backup.'));
        }

        $location = 'Local';
        if ($destination === 'cloud') {
            $cloudProvider = $settings['cloud_provider'] ?? 'google_drive';
            $uploaded = $this->uploadToCloud($filepath, $cloudProvider, $settings);

            if ($uploaded) {
                $location = ucfirst($cloudProvider);
            } else {
                return redirect()->route('admin.backup.index')
                        ->with('error', __('Backup created locally but failed to upload to cloud.'));
            }
        }

        return redirect()->route('admin.backup.index')
            ->with('success', __('Backup created successfully. Location: ') . $location);
    }

    private function ensureBackupDirectory(): string
    {
        $path = storage_path('app/backups');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    public function createDatabaseBackup(string $filepath): bool
    {
        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver", 'mysql');

            return match ($driver) {
                'mysql' => $this->dumpMySql($connection, $filepath),
                'pgsql' => $this->dumpPostgreSql($connection, $filepath),
                'sqlite' => $this->dumpSqlite($connection, $filepath),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Backup: Exception during database backup: ' . $e->getMessage());
            return false;
        }
    }

    private function dumpMySql(string $connection, string $filepath): bool
    {
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '3306');

        if (empty($database)) {
            Log::error('Backup: Database name is empty.');
            return false;
        }

        if ($this->runMysqldump($database, $username, $password, $host, $port, $filepath)) {
            return true;
        }

        Log::info('Backup: mysqldump failed, falling back to PHP-based dump.');
        return $this->phpMySqlDump($connection, $filepath);
    }

    private function runMysqldump(
        string $database,
        string $username,
        string $password,
        string $host,
        string $port,
        string $filepath,
    ): bool {
        $command = sprintf(
            'mysqldump --user=%s --host=%s --port=%s --routines --no-tablespaces --single-transaction --skip-lock-tables %s',
            escapeshellarg($username),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database)
        );

        if (!empty($password)) {
            $command .= ' --password=' . escapeshellarg($password);
        }

        $xamppPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (file_exists($xamppPath)) {
            $command = '"' . $xamppPath . '" ' . substr($command, 10);
        }

        $command .= ' > ' . escapeshellarg($filepath) . ' 2>&1';

        $output = [];
        $returnVar = -1;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warning('Backup: mysqldump command failed.', ['return_code' => $returnVar, 'output' => $output]);
            return false;
        }

        if (!file_exists($filepath) || filesize($filepath) === 0) {
            Log::warning('Backup: mysqldump produced an empty file.');
            return false;
        }

        return true;
    }

    private function phpMySqlDump(string $connection, string $filepath): bool
    {
        try {
            $pdo = DB::connection($connection)->getPdo();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            $sql = "-- Database backup generated by Pos Pro\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $tableName) {
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`")->fetch(\PDO::FETCH_NUM);
                $sql .= "\n-- Table structure for `{$tableName}`\n";
                $sql .= ($createStmt[1] ?? '') . ";\n\n";

                $rows = DB::connection($connection)->table($tableName)->get();
                if ($rows->isEmpty()) {
                    continue;
                }

                $columns = array_keys((array) $rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';

                foreach ($rows->chunk(100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $row = (array) $row;
                        $escaped = array_map(fn ($val) => $val === null ? 'NULL' : $pdo->quote($val), $row);
                        $values[] = '(' . implode(', ', $escaped) . ')';
                    }
                    $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            file_put_contents($filepath, $sql);

            return file_exists($filepath) && filesize($filepath) > 0;
        } catch (\Exception $e) {
            Log::error('Backup: PHP MySQL dump failed: ' . $e->getMessage());
            return false;
        }
    }

    private function dumpPostgreSql(string $connection, string $filepath): bool
    {
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '5432');

        if (empty($database)) {
            Log::error('Backup: Database name is empty.');
            return false;
        }

        putenv("PGPASSWORD={$password}");
        $command = sprintf(
            'pg_dump --username=%s --host=%s --port=%s %s',
            escapeshellarg($username),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database)
        );
        $command .= ' > ' . escapeshellarg($filepath) . ' 2>&1';

        $output = [];
        $returnVar = -1;
        exec($command, $output, $returnVar);
        putenv('PGPASSWORD');

        if ($returnVar !== 0) {
            Log::error('Backup: pg_dump failed.', ['return_code' => $returnVar, 'output' => $output]);
            return false;
        }

        return file_exists($filepath) && filesize($filepath) > 0;
    }

    private function dumpSqlite(string $connection, string $filepath): bool
    {
        $database = config("database.connections.{$connection}.database");

        if (!file_exists($database)) {
            Log::error('Backup: SQLite database file not found: ' . $database);
            return false;
        }

        return copy($database, $filepath);
    }

    public function uploadToCloud(string $filepath, string $provider, array $settings): bool
    {
        try {
            // Dynamic runtime config
            if ($provider === 'google_drive') {
                config([
                    'filesystems.disks.google.clientId' => $settings['client_id'] ?? '',
                    'filesystems.disks.google.clientSecret' => $settings['client_secret'] ?? '',
                    'filesystems.disks.google.refreshToken' => $settings['refresh_token'] ?? '',
                    'filesystems.disks.google.folderId' => $settings['folder_id'] ?? '',
                ]);
            }

            if ($provider === 'dropbox') {
                config([
                    'filesystems.disks.dropbox.authorization_token' => $settings['dropbox_access_token'] ?? '',
                ]);
            }

            if ($provider === 's3') {
                config([
                    'filesystems.disks.s3_custom.key' => $settings['s3_access_key'] ?? '',
                    'filesystems.disks.s3_custom.secret' => $settings['s3_secret_key'] ?? '',
                    'filesystems.disks.s3_custom.region' => $settings['s3_region'] ?? 'us-east-1',
                    'filesystems.disks.s3_custom.bucket' => $settings['s3_bucket'] ?? '',
                    'filesystems.disks.s3_custom.endpoint' => $settings['s3_endpoint'] ?? '',
                    'filesystems.disks.s3_custom.use_path_style_endpoint' => (bool) ($settings['s3_path_style'] ?? false),
                ]);
            }

            $filename = basename($filepath);

            $cloudPath = match ($provider) {
                'dropbox'
                    => '/' . trim($settings['cloud_path'] ?? '', '/'),

                default
                    => trim($settings['cloud_path'] ?? '', '/'),
            };

            $targetPath = $cloudPath
                ? $cloudPath . '/' . $filename
                : $filename;

            $disk = match ($provider) {

                'google_drive' => 'google',

                'dropbox' => 'dropbox',

                's3' => 's3_custom',

                default => null,
            };

            if (!$disk) {
                return false;
            }

            Storage::disk($disk)->put(
                $targetPath,
                fopen($filepath, 'r')
            );

            return true;

        } catch (\Exception $e) {

            Log::error('Backup upload failed', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getBackupHistory(): array
    {
        $backups = [];
        $directory = storage_path('app/backups');

        if (!is_dir($directory)) {
            return $backups;
        }

        $files = File::files($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        usort($backups, fn($a, $b) => $b['modified'] - $a['modified']);

        return $backups;
    }

    public function download(string $filename): mixed
    {
        $filename = basename($filename);
        $filepath = storage_path('app/backups/' . $filename);

        if (!file_exists($filepath)) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Backup file not found.'));
        }

        return response()->download($filepath);
    }

    public function destroy(string $filename)
    {
        $filename = basename($filename);
        $filepath = storage_path('app/backups/' . $filename);

        if (file_exists($filepath) && unlink($filepath)) {
            return response()->json([
                'message' => __('Backup deleted successfully.'),
                'redirect' => route('admin.backup.index'),
            ]);
        }

        return response()->json([
                'message' => __('Backup file not found.'),
                'redirect' => route('admin.backup.index'),
            ]);
    }

    public function deleteAll(Request $request)
    {
        $filenames = $request->input('ids', []);

        if (empty($filenames)) {
            return response()->json([
                'message' => __('No backup files selected.'),
                'redirect' => route('admin.backup.index'),
            ]);
        }

        $deleted = 0;
        foreach ($filenames as $filename) {
            $filename = basename($filename);
            $filepath = storage_path('app/backups/' . $filename);

            if (file_exists($filepath) && unlink($filepath)) {
                $deleted++;
            }
        }

        return response()->json([
            'message' => __(':count backup(s) deleted successfully.', ['count' => $deleted]),
            'redirect' => route('admin.backup.index'),
        ]);
    }

    public function googleDriveConnect()
    {
        $settings = get_option('backup_settings');

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return redirect()->route('admin.backup.index')
                    ->with('error', __('Please save the Client ID and Client Secret before connecting to Google Drive.'));
        }

        $client = new Client();

        $client->setClientId($settings['client_id']);

        $client->setClientSecret($settings['client_secret']);

        $client->setRedirectUri(route('google-drive.callback'));

        $client->addScope(Drive::DRIVE);

        $client->setAccessType('offline');

        $client->setPrompt('consent');

        return redirect($client->createAuthUrl());
    }

    public function googleDriveCallback(Request $request)
    {
        $client = new Client();

        $settings = get_option('backup_settings');

        $client->setClientId($settings['client_id']);

        $client->setClientSecret($settings['client_secret']);

        $client->setRedirectUri(route('google-drive.callback'));

        $token = $client->fetchAccessTokenWithAuthCode(
            $request->code
        );  

        if ($token['error'] ?? false) {
            return redirect()->route('admin.backup.index')
                    ->with('error', $token['error_description'] ?? __('Failed to retrieve access token from Google Drive.'));
        }

        Option::updateOrCreate(
            ['key' => 'backup_settings'],
            ['value' => array_merge($settings, [
                'refresh_token' => $token['refresh_token'] ?? null,
            ])]
        );

        Cache::forget('backup_settings');

        return redirect()->route('admin.backup.index')
                ->with('message', __('Successfully connected to Google Drive.'));
    }
}
