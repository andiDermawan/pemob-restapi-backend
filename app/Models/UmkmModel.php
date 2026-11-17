<?php

namespace App\Models;

use RuntimeException;

/**
 * Small file-backed model for a runnable classroom API demo.
 * Data is stored in writable/data/umkm.json and guarded by a file lock.
 */
class UmkmModel
{
    private string $dataPath;
    private string $lockPath;

    public function __construct(?string $directory = null)
    {
        $directory ??= WRITEPATH . 'data';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan data tidak dapat dibuat.');
        }
        $this->dataPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'umkm.json';
        $this->lockPath = $this->dataPath . '.lock';
    }

    /** @return array{records: list<array<string, mixed>>, next_id: int} */
    private function initialState(): array
    {
        return [
            'next_id' => 4,
            'records' => [
                ['id' => 1, 'nama_umkm' => 'Kopi Nusantara', 'pemilik' => 'Rafi A', 'alamat' => 'Jl. A. Yani, Banjarbaru', 'kategori' => 'Kuliner', 'kontak' => '081234567890'],
                ['id' => 2, 'nama_umkm' => 'Anyaman Banjar', 'pemilik' => 'Siti Rahmah', 'alamat' => 'Martapura, Banjar', 'kategori' => 'Kerajinan', 'kontak' => '081298765432'],
                ['id' => 3, 'nama_umkm' => 'Dapur Kayuh', 'pemilik' => 'Budi Santoso', 'alamat' => 'Banjarmasin Utara', 'kategori' => 'Kuliner', 'kontak' => '082155501234'],
            ],
        ];
    }

    /** @return array{records: list<array<string, mixed>>, next_id: int} */
    private function readState(): array
    {
        if (! is_file($this->dataPath)) {
            return $this->initialState();
        }
        $contents = file_get_contents($this->dataPath);
        $state = json_decode($contents === false ? '' : $contents, true);
        if (! is_array($state) || ! isset($state['records'], $state['next_id']) || ! is_array($state['records'])) {
            throw new RuntimeException('Berkas data UMKM tidak berisi JSON yang valid.');
        }
        return $state;
    }

    /** @param array{records: list<array<string, mixed>>, next_id: int} $state */
    private function writeState(array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $tempPath = $this->dataPath . '.tmp';
        if (file_put_contents($tempPath, $json . PHP_EOL, LOCK_EX) === false || ! rename($tempPath, $this->dataPath)) {
            @unlink($tempPath);
            throw new RuntimeException('Data UMKM tidak dapat disimpan.');
        }
    }

    private function withLock(bool $write, callable $callback): mixed
    {
        $lock = fopen($this->lockPath, 'c+');
        if ($lock === false) {
            throw new RuntimeException('Penyimpanan data UMKM tidak dapat dikunci.');
        }
        try {
            if (! flock($lock, $write ? LOCK_EX : LOCK_SH)) {
                throw new RuntimeException('Penyimpanan data UMKM sedang tidak tersedia.');
            }
            $state = $this->readState();
            $result = $callback($state);
            if ($write) {
                $this->writeState($state);
            }
            flock($lock, LOCK_UN);
            return $result;
        } finally {
            fclose($lock);
        }
    }

    /** @return list<array<string, mixed>> */
    public function all(int $offset = 0, ?int $limit = null): array
    {
        return $this->withLock(false, static function (array &$state) use ($offset, $limit): array {
            $records = $state['records'];
            usort($records, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);
            return array_slice($records, $offset, $limit);
        });
    }

    public function countAll(): int
    {
        return $this->withLock(false, static fn (array &$state): int => count($state['records']));
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->withLock(false, static function (array &$state) use ($id): ?array {
            foreach ($state['records'] as $record) {
                if ((int) $record['id'] === $id) {
                    return $record;
                }
            }
            return null;
        });
    }

    /** @param array<string, string> $data @return array<string, mixed> */
    public function insert(array $data): array
    {
        return $this->withLock(true, static function (array &$state) use ($data): array {
            $record = ['id' => (int) $state['next_id']++, ...$data];
            $state['records'][] = $record;
            return $record;
        });
    }

    /** @param array<string, string> $data @return array<string, mixed>|null */
    public function replace(int $id, array $data): ?array
    {
        return $this->withLock(true, static function (array &$state) use ($id, $data): ?array {
            foreach ($state['records'] as $index => $record) {
                if ((int) $record['id'] === $id) {
                    $state['records'][$index] = ['id' => $id, ...$data];
                    return $state['records'][$index];
                }
            }
            return null;
        });
    }

    /** @return array<string, mixed>|null */
    public function remove(int $id): ?array
    {
        return $this->withLock(true, static function (array &$state) use ($id): ?array {
            foreach ($state['records'] as $index => $record) {
                if ((int) $record['id'] === $id) {
                    array_splice($state['records'], $index, 1);
                    return $record;
                }
            }
            return null;
        });
    }
}
