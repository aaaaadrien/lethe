<?php
declare(strict_types=1);

namespace Lethe;

use Lethe\Exceptions\UserException;

/**
 * Chunked file storage: fragments are received one by one (1 Mo each) in
 * uploads/tmp/<upload_id>/, then assembled into the final stored file.
 */
final class FileManager
{
    public static function chunkDir(string $uploadId): string
    {
        $uploadId = preg_replace('/[^a-zA-Z0-9]/', '', $uploadId);
        $dir = TMP_PATH . '/' . $uploadId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function saveChunk(string $uploadId, int $index, string $tmpFilePath): void
    {
        $dir = self::chunkDir($uploadId);
        $target = $dir . '/' . $index . '.part';

        if (!is_writable($dir)) {
            throw new UserException(Language::t('error.tmp_not_writable'));
        }
        if (!is_uploaded_file($tmpFilePath)) {
            throw new UserException(Language::t('error.chunk_invalid', ['index' => $index]));
        }
        if (!move_uploaded_file($tmpFilePath, $target)) {
            throw new UserException(Language::t('error.chunk_save_failed', ['index' => $index]));
        }
    }

    public static function currentSize(string $uploadId): int
    {
        $dir = self::chunkDir($uploadId);
        $total = 0;
        foreach (glob($dir . '/*.part') ?: [] as $f) {
            $total += (int)filesize($f);
        }
        return $total;
    }

    /**
     * Assemble all fragments in order. Returns the final stored file info.
     *
     * @return array{stored_name: string, size: int, path: string}
     */
    public static function assemble(string $uploadId, int $totalChunks, string $originalName): array
    {
        $dir = self::chunkDir($uploadId);

        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($dir . '/' . $i . '.part')) {
                throw new UserException("Fragment $i manquant, upload incomplet.");
            }
        }

        if (!is_dir(STORAGE_PATH)) {
            mkdir(STORAGE_PATH, 0775, true);
        }

        $safeName = Helpers::sanitizeFilename($originalName);
        $storedName = date('Ymd_His') . '_' . Helpers::generateCode(8) . '_' . $safeName;
        $finalPath = STORAGE_PATH . '/' . $storedName;

        $out = fopen($finalPath, 'wb');
        if ($out === false) {
            throw new UserException(Language::t('error.final_file_failed'));
        }

        $totalSize = 0;
        for ($i = 0; $i < $totalChunks; $i++) {
            $in = fopen($dir . '/' . $i . '.part', 'rb');
            if ($in === false) {
                fclose($out);
                @unlink($finalPath);
                throw new UserException("Impossible de lire le fragment $i.");
            }
            while (!feof($in)) {
                $buffer = fread($in, 1024 * 1024);
                fwrite($out, $buffer);
                $totalSize += strlen($buffer);
            }
            fclose($in);
        }
        fclose($out);

        self::cleanupChunks($uploadId);

        return ['stored_name' => $storedName, 'size' => $totalSize, 'path' => $finalPath];
    }

    public static function cleanupChunks(string $uploadId): void
    {
        $dir = TMP_PATH . '/' . preg_replace('/[^a-zA-Z0-9]/', '', $uploadId);
        foreach (glob($dir . '/*.part') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
}
