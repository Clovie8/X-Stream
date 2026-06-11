<?php
declare(strict_types=1);

class Cache {
    private string $cacheDir;
    private int $cacheTime;

    public function __construct(int $cacheTime = 86400, ?string $cacheDir = null) {
        $this->cacheTime = $cacheTime;
        $this->cacheDir = $cacheDir ?? __DIR__ . '/cache/';

        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cacheDir));
            }
        }
        
        // Security: Create a .htaccess to prevent direct access to cache files
        if (!file_exists($this->cacheDir . '.htaccess')) {
            file_put_contents($this->cacheDir . '.htaccess', 'Deny from all');
        }
    }

    private function getCacheFile(string $key): string {
        return $this->cacheDir . md5($key) . '.cache';
    }

    public function get(string $key): ?string {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        // Check expiration
        if ((time() - filemtime($file)) > $this->cacheTime) {
            return null;
        }

        // Use File Locking for reading
        $handle = fopen($file, 'rb');
        if ($handle === false) return null;

        if (flock($handle, LOCK_SH)) { // Shared Lock
            $content = stream_get_contents($handle);
            flock($handle, LOCK_UN);
            fclose($handle);
            return $content ?: null;
        }
        
        fclose($handle);
        return null;
    }

    public function save(string $key, string $content): void {
        $file = $this->getCacheFile($key);
        
        // 1. Save the file (Existing logic)
        $handle = fopen($file, 'wb');
        if ($handle === false) return;

        if (flock($handle, LOCK_EX)) { 
            fwrite($handle, $content);
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        // 2. NEW: Garbage Collection (1% chance to run)
        // rand(1, 100) generates a number between 1 and 100.
        // If it hits '1', we clean up old files.
        if (rand(1, 100) === 1) {
            $this->deleteExpired();
        }
    }

    /**
     * Helper to physically delete files older than cacheTime
     */
    private function deleteExpired(): void {
        $files = glob($this->cacheDir . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > $this->cacheTime) {
                    unlink($file);
                }
            }
        }
    }

    
    public function clear(string $key): void {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clearAll(): void {
        // glob finds all files ending in .cache
        $files = glob($this->cacheDir . '*.cache');
        
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Delete the file
                }
            }
        }
    }
}
?>