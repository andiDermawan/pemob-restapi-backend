<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UmkmModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class UmkmController extends BaseController
{
    private UmkmModel $umkm;

    protected $helpers = ['form'];

    public function initController($request, $response, $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->umkm = new UmkmModel();
    }

    public function options(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(204)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    public function index(): ResponseInterface
    {
        try {
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $perPage = min(50, max(1, (int) ($this->request->getGet('per_page') ?? 10)));
            $total = $this->umkm->countAll();
            $records = $this->umkm->all(($page - 1) * $perPage, $perPage);
            return $this->json(true, 'Data UMKM berhasil diambil.', $records, 200, [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function show(int $id): ResponseInterface
    {
        try {
            $record = $this->umkm->find($id);
            if ($record === null) {
                return $this->json(false, 'Data UMKM tidak ditemukan.', null, 404);
            }
            return $this->json(true, 'Data UMKM berhasil diambil.', $record);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function create(): ResponseInterface
    {
        $input = $this->readJsonObject();
        if ($input['error'] !== null) {
            return $this->json(false, $input['error'], null, 400, null, ['body' => $input['error']]);
        }
        $errors = $this->validateUmkm($input['data']);
        if ($errors !== []) {
            return $this->json(false, 'Validasi data gagal.', null, 422, null, $errors);
        }
        try {
            return $this->json(true, 'Data UMKM berhasil ditambahkan.', $this->umkm->insert($this->cleanData($input['data'])), 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function update(int $id): ResponseInterface
    {
        $input = $this->readJsonObject();
        if ($input['error'] !== null) {
            return $this->json(false, $input['error'], null, 400, null, ['body' => $input['error']]);
        }
        $errors = $this->validateUmkm($input['data']);
        if ($errors !== []) {
            return $this->json(false, 'Validasi data gagal.', null, 422, null, $errors);
        }
        try {
            $record = $this->umkm->replace($id, $this->cleanData($input['data']));
            if ($record === null) {
                return $this->json(false, 'Data UMKM tidak ditemukan.', null, 404);
            }
            return $this->json(true, 'Data UMKM berhasil diperbarui.', $record);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        try {
            $record = $this->umkm->remove($id);
            if ($record === null) {
                return $this->json(false, 'Data UMKM tidak ditemukan.', null, 404);
            }
            return $this->json(true, 'Data UMKM berhasil dihapus.', [
                'id' => $record['id'],
                'nama_umkm' => $record['nama_umkm'],
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /** @return array{data: array<string, mixed>, error: string|null} */
    private function readJsonObject(): array
    {
        $decoded = json_decode($this->request->getBody());
        if (! $decoded instanceof \stdClass) {
            return ['data' => [], 'error' => 'Body request harus berupa objek JSON yang valid.'];
        }
        return ['data' => (array) $decoded, 'error' => null];
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function validateUmkm(array $data): array
    {
        $rules = [
            'nama_umkm' => 'required|min_length[2]|max_length[100]',
            'pemilik' => 'required|min_length[2]|max_length[100]',
            'alamat' => 'required|min_length[5]|max_length[180]',
            'kategori' => 'required|min_length[2]|max_length[60]',
            'kontak' => 'required|min_length[6]|max_length[30]',
        ];
        $messages = [
            'nama_umkm' => ['required' => 'Nama UMKM wajib diisi.', 'min_length' => 'Nama UMKM minimal 2 karakter.', 'max_length' => 'Nama UMKM maksimal 100 karakter.'],
            'pemilik' => ['required' => 'Nama pemilik wajib diisi.', 'min_length' => 'Nama pemilik minimal 2 karakter.', 'max_length' => 'Nama pemilik maksimal 100 karakter.'],
            'alamat' => ['required' => 'Alamat wajib diisi.', 'min_length' => 'Alamat minimal 5 karakter.', 'max_length' => 'Alamat maksimal 180 karakter.'],
            'kategori' => ['required' => 'Kategori wajib diisi.', 'min_length' => 'Kategori minimal 2 karakter.', 'max_length' => 'Kategori maksimal 60 karakter.'],
            'kontak' => ['required' => 'Kontak wajib diisi.', 'min_length' => 'Kontak minimal 6 karakter.', 'max_length' => 'Kontak maksimal 30 karakter.'],
        ];
        if ($this->validateData($data, $rules, $messages)) {
            return [];
        }
        return $this->validator->getErrors();
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    private function cleanData(array $data): array
    {
        $keys = ['nama_umkm', 'pemilik', 'alamat', 'kategori', 'kontak'];
        $clean = [];
        foreach ($keys as $key) {
            $clean[$key] = trim((string) $data[$key]);
        }
        return $clean;
    }

    /** @param mixed $data @param array<string, mixed>|null $meta @param array<string, string>|null $errors */
    private function json(bool $success, string $message, mixed $data, int $status = 200, ?array $meta = null, ?array $errors = null): ResponseInterface
    {
        $body = ['success' => $success, 'message' => $message, 'data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
            ->setJSON($body);
    }

    private function serverError(Throwable $exception): ResponseInterface
    {
        log_message('error', 'UMKM API error: {message}', ['message' => $exception->getMessage()]);
        return $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
    }
}
