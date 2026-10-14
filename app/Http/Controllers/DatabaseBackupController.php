<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackupController extends Controller
{
    private function getToolPath($tool)
    {
        // Try common locations
        $locations = [
            $tool,
            '/usr/bin/' . $tool,
            '/usr/local/bin/' . $tool,
            '/opt/lampp/bin/' . $tool,
        ];

        foreach ($locations as $location) {
            $output = shell_exec("which $location 2>/dev/null");
            if ($output) {
                return trim($output);
            }
            if (file_exists($location) && is_executable($location)) {
                return $location;
            }
        }

        return $tool; // Fallback to just the tool name
    }

    public function download()
    {
        if (config('database.default') !== 'mysql') {
            return back()->with('error', 'Only MySQL/MariaDB database backups are supported currently.');
        }

        $filename = "backup-" . now()->format('Y-m-d-H-i-s') . ".sql.gz";
        $path = storage_path('app/backups/' . $filename);

        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        $connection = config('database.connections.mysql');
        $host = $connection['host'];
        $port = $connection['port'];
        $database = $connection['database'];
        $username = $connection['username'];
        $password = $connection['password'];

        $mysqldump = $this->getToolPath('mysqldump');
        $gzip = $this->getToolPath('gzip');

        $errorPath = storage_path('app/backups/dump_err_' . time() . '.err');

        if ($gzip && is_executable($gzip)) {
            $command = sprintf(
                'MYSQL_PWD=%s %s --user=%s --host=%s --port=%s %s 2>%s | %s -c > %s',
                escapeshellarg($password),
                $mysqldump,
                escapeshellarg($username),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($errorPath),
                $gzip,
                escapeshellarg($path)
            );
        } else {
            $filename = "backup-" . now()->format('Y-m-d-H-i-s') . ".sql";
            $path = storage_path('app/backups/' . $filename);
            $command = sprintf(
                'MYSQL_PWD=%s %s --user=%s --host=%s --port=%s %s > %s 2>%s',
                escapeshellarg($password),
                $mysqldump,
                escapeshellarg($username),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($path),
                escapeshellarg($errorPath)
            );
        }

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorOutput = file_exists($errorPath) ? file_get_contents($errorPath) : 'Unknown error';
            @unlink($errorPath);
            @unlink($path);
            return back()->with('error', 'Database backup failed. Error: ' . $errorOutput);
        }

        @unlink($errorPath);

        if (!file_exists($path) || filesize($path) === 0) {
            return back()->with('error', 'Database backup file was not created or is empty.');
        }

        return Response::download($path, $filename)->deleteFileAfterSend(true);
    }

    public function restore(Request $request)
    {
        if (config('database.default') !== 'mysql') {
            return back()->with('error', 'Only MySQL/MariaDB database restores are supported currently.');
        }

        $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');
        if (!$file || !$file->isValid()) {
            $maxPost = ini_get('post_max_size');
            $maxUpload = ini_get('upload_max_filesize');
            return back()->with('error', "Uploaded backup file is invalid or exceeds server limits (upload_max_filesize: {$maxUpload}, post_max_size: {$maxPost}).");
        }

        $path = $file->getRealPath();
        $tempDecompressedPath = null;

        // Check if file is GZIP compressed (magic bytes \x1f\x8b)
        $handle = fopen($path, 'rb');
        $magic = fread($handle, 2);
        fclose($handle);

        if ($magic === "\x1f\x8b") {
            $tempDecompressedPath = storage_path('app/backups/decompressed_' . time() . '_' . uniqid() . '.sql');
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }
            $sfp = gzopen($path, 'rb');
            $dfp = fopen($tempDecompressedPath, 'wb');
            if ($sfp && $dfp) {
                while (!gzeof($sfp)) {
                    fwrite($dfp, gzread($sfp, 16384));
                }
                fclose($dfp);
                gzclose($sfp);
                $path = $tempDecompressedPath;
            } else {
                if ($sfp) gzclose($sfp);
                if ($dfp) fclose($dfp);
                return back()->with('error', 'Failed to decompress the uploaded GZIP backup file.');
            }
        }

        // Diagnostic: Check first few bytes of the file
        $handle = fopen($path, 'r');
        $firstBytes = fread($handle, 1024);
        fclose($handle);

        // Check if file starts with valid SQL markers
        $validMarkers = ['--', '/*', 'INSERT', 'CREATE', 'SET', 'USE', 'DROP', 'LOCK'];
        $isValidSql = false;
        $trimmedHeader = ltrim($firstBytes);
        foreach ($validMarkers as $marker) {
            if (strpos($trimmedHeader, $marker) === 0) {
                $isValidSql = true;
                break;
            }
        }

        if (!$isValidSql) {
            if ($tempDecompressedPath && file_exists($tempDecompressedPath)) {
                @unlink($tempDecompressedPath);
            }
            \Illuminate\Support\Facades\Log::warning('Invalid SQL backup header detected: ' . substr($firstBytes, 0, 100));
            return back()->with('error', 'The uploaded file does not appear to be a valid SQL backup. It starts with: ' . substr($firstBytes, 0, 50));
        }

        $connection = config('database.connections.mysql');
        $host = $connection['host'];
        $port = $connection['port'];
        $database = $connection['database'];
        $username = $connection['username'];
        $password = $connection['password'];

        $mysql = $this->getToolPath('mysql');

        $command = sprintf(
            'MYSQL_PWD=%s %s --user=%s --host=%s --port=%s --default-character-set=utf8mb4 --binary-mode %s < %s 2>&1',
            escapeshellarg($password),
            $mysql,
            escapeshellarg($username),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($tempDecompressedPath && file_exists($tempDecompressedPath)) {
            @unlink($tempDecompressedPath);
        }

        if ($returnVar !== 0) {
            $errorMessage = implode("\n", $output);
            \Illuminate\Support\Facades\Log::error('Database restore failed. Header: ' . substr($firstBytes, 0, 100) . ' | Error: ' . $errorMessage);
            
            if (strpos($errorMessage, "Unknown command '\-'") !== false) {
                return back()->with('error', 'Restore failed: The file contains invalid characters at the start. Please ensure you are using a NEWly downloaded backup file from this dashboard.');
            }

            return back()->with('error', 'Database restore failed. Error: ' . ($errorMessage ?: 'Unknown error'));
        }

        return back()->with('success', 'Database restored successfully.');
    }
}

