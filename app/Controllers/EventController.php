<?php

namespace App\Controllers;

use App\Models\EventModel;
use CodeIgniter\HTTP\ResponseInterface;

class EventController extends BaseController
{
    protected EventModel $eventModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Request body tidak valid.',
                ]);
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $eventDate = trim($data['event_date'] ?? '');
        $location = trim($data['location'] ?? '');
        $quota = $data['quota'] ?? null;

        if (
            $title === '' ||
            $eventDate === '' ||
            $location === '' ||
            $quota === null
        ) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Title, event_date, location, dan quota wajib diisi.',
                ]);
        }

        if (!is_numeric($quota) || (int) $quota <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Quota harus berupa angka lebih dari 0.',
                ]);
        }

        $timestamp = strtotime($eventDate);

        if ($timestamp === false) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Format event_date tidak valid.',
                ]);
        }

        if ($timestamp <= time()) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Event harus memiliki tanggal di masa depan.',
                ]);
        }

        $eventId = $this->eventModel->insert([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'event_date' => date('Y-m-d H:i:s', $timestamp),
            'location' => $location,
            'quota' => (int) $quota,
            'available_quota' => (int) $quota,
        ]);

        if ($eventId === false) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Gagal membuat event.',
                    'errors' => $this->eventModel->errors(),
                ]);
        }

        $event = $this->eventModel->find($eventId);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'success' => true,
                'message' => 'Event berhasil dibuat.',
                'data' => $event,
            ]);
    }

    public function index(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $perPage = min(
            20,
            max(1, (int) ($this->request->getGet('per_page') ?? 10))
        );

        $search = trim($this->request->getGet('search') ?? '');
        $date = trim($this->request->getGet('date') ?? '');

        $builder = $this->eventModel;

        if ($search !== '') {
            $builder->like('title', $search);
        }

        if ($date !== '') {
            $builder->where('DATE(event_date)', $date);
        }

        $events = $builder
            ->orderBy('event_date', 'ASC')
            ->paginate($perPage, 'default', $page);

        return $this->response
            ->setJSON([
                'success' => true,
                'message' => 'Daftar event berhasil diambil.',
                'data' => $events,
                'pagination' => [
                    'current_page' => $this->eventModel->pager->getCurrentPage(),
                    'per_page' => $this->eventModel->pager->getPerPage(),
                    'total' => $this->eventModel->pager->getTotal(),
                    'total_pages' => $this->eventModel->pager->getPageCount(),
                ],
            ]);
    }

    public function show(int $id): ResponseInterface
    {
        $event = $this->eventModel->find($id);

        if (!$event) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.',
                ]);
        }

        return $this->response
            ->setJSON([
                'success' => true,
                'message' => 'Detail event berhasil diambil.',
                'data' => $event,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $event = $this->eventModel->find($id);

        if (!$event) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Request body tidak valid.',
                ]);
        }

        $title = trim($data['title'] ?? $event['title']);
        $description = trim(
            $data['description'] ?? ($event['description'] ?? '')
        );
        $eventDate = trim($data['event_date'] ?? $event['event_date']);
        $location = trim($data['location'] ?? $event['location']);
        $quota = $data['quota'] ?? $event['quota'];

        if (
            $title === '' ||
            $eventDate === '' ||
            $location === '' ||
            $quota === null
        ) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Title, event_date, location, dan quota wajib diisi.',
                ]);
        }

        if (!is_numeric($quota) || (int) $quota <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Quota harus berupa angka lebih dari 0.',
                ]);
        }

        $timestamp = strtotime($eventDate);

        if ($timestamp === false) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Format event_date tidak valid.',
                ]);
        }

        if ($timestamp <= time()) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Event harus memiliki tanggal di masa depan.',
                ]);
        }

        $quota = (int) $quota;

        $bookedQuota =
            (int) $event['quota'] - (int) $event['available_quota'];

        if ($quota < $bookedQuota) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Quota baru tidak boleh lebih kecil dari jumlah tiket yang sudah dipesan.',
                ]);
        }

        $availableQuota = $quota - $bookedQuota;

        $updated = $this->eventModel->update($id, [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'event_date' => date('Y-m-d H:i:s', $timestamp),
            'location' => $location,
            'quota' => $quota,
            'available_quota' => $availableQuota,
        ]);

        if (!$updated) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Gagal memperbarui event.',
                ]);
        }

        $updatedEvent = $this->eventModel->find($id);

        return $this->response
            ->setJSON([
                'success' => true,
                'message' => 'Event berhasil diperbarui.',
                'data' => $updatedEvent,
            ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $event = $this->eventModel->find($id);

        if (!$event) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.',
                ]);
        }

        $deleted = $this->eventModel->delete($id);

        if (!$deleted) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus event.',
                ]);
        }

        return $this->response
            ->setJSON([
                'success' => true,
                'message' => 'Event berhasil dihapus.',
            ]);
    }
}