<?php

namespace App\Controllers;

use App\Libraries\ApiLogger;
use App\Models\BookingModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class BookingController extends BaseController
{
    protected BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Membuat booking baru.
     * Menggunakan row locking untuk mencegah overselling.
     */
    public function create(): ResponseInterface
    {
        $user = $this->request->user ?? null;

        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'User belum terautentikasi.',
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

        $eventId = $data['event_id'] ?? null;
        $quantity = $data['quantity'] ?? null;

        if ($eventId === null || $quantity === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'event_id dan quantity wajib diisi.',
                ]);
        }

        if (!is_numeric($eventId) || (int) $eventId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'event_id tidak valid.',
                ]);
        }

        if (
            !is_numeric($quantity) ||
            (int) $quantity < 1 ||
            (int) $quantity > 5
        ) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Jumlah tiket harus antara 1 sampai 5.',
                ]);
        }

        $eventId = (int) $eventId;
        $quantity = (int) $quantity;

        $db = Database::connect();

        try {
            $db->transStart();

            /*
             * Lock baris event selama transaksi berlangsung.
             * Ini membantu mencegah overselling saat ada
             * beberapa request booking secara bersamaan.
             */
            $eventQuery = $db->query(
                'SELECT id, title, event_date, available_quota
                 FROM events
                 WHERE id = ?
                 FOR UPDATE',
                [$eventId]
            );

            $event = $eventQuery->getRowArray();

            if (!$event) {
                $db->transRollback();

                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Event tidak ditemukan.',
                    ]);
            }

            if (strtotime($event['event_date']) <= time()) {
                $db->transRollback();

                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Event sudah lewat dan tidak dapat dipesan.',
                    ]);
            }

            if ((int) $event['available_quota'] < $quantity) {
                $db->transRollback();

                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Kuota event tidak mencukupi.',
                        'available_quota' => (int) $event['available_quota'],
                    ]);
            }

            $bookingId = $this->bookingModel->insert([
                'user_id' => (int) $user->sub,
                'event_id' => $eventId,
                'quantity' => $quantity,
                'status' => 'booked',
            ]);

            if ($bookingId === false) {
                $db->transRollback();

                ApiLogger::error('Booking insertion failed', [
                    'user_id' => (int) $user->sub,
                    'event_id' => $eventId,
                    'quantity' => $quantity,
                    'validation_errors' => $this->bookingModel->errors(),
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Gagal membuat booking.',
                    ]);
            }

            $newAvailableQuota =
                (int) $event['available_quota'] - $quantity;

            $updated = $db->table('events')
                ->where('id', $eventId)
                ->update([
                    'available_quota' => $newAvailableQuota,
                ]);

            if (!$updated) {
                $db->transRollback();

                ApiLogger::error('Event quota update failed', [
                    'booking_id' => (int) $bookingId,
                    'user_id' => (int) $user->sub,
                    'event_id' => $eventId,
                    'quantity' => $quantity,
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Gagal memperbarui kuota event.',
                    ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                ApiLogger::error('Booking transaction failed', [
                    'user_id' => (int) $user->sub,
                    'event_id' => $eventId,
                    'quantity' => $quantity,
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Transaksi booking gagal.',
                    ]);
            }

            ApiLogger::info('Booking created successfully', [
                'booking_id' => (int) $bookingId,
                'user_id' => (int) $user->sub,
                'event_id' => $eventId,
                'quantity' => $quantity,
            ]);

            $booking = $this->bookingModel->find($bookingId);

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'success' => true,
                    'message' => 'Booking berhasil dibuat.',
                    'data' => $booking,
                ]);
        } catch (\Throwable $e) {
            if ($db->transDepth() > 0) {
                $db->transRollback();
            }

            ApiLogger::error('Booking creation failed', [
                'user_id' => (int) $user->sub,
                'event_id' => $eventId,
                'quantity' => $quantity,
                'exception' => $e::class,
                'error_message' => $e->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membuat booking.',
                ]);
        }
    }

    /**
     * Menampilkan booking milik user yang sedang login.
     */
    public function index(): ResponseInterface
    {
        $user = $this->request->user ?? null;

        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'User belum terautentikasi.',
                ]);
        }

        $bookings = $this->bookingModel
            ->select('
                bookings.id,
                bookings.event_id,
                events.title,
                events.event_date,
                events.location,
                bookings.quantity,
                bookings.status,
                bookings.created_at,
                bookings.updated_at
            ')
            ->join('events', 'events.id = bookings.event_id')
            ->where('bookings.user_id', (int) $user->sub)
            ->orderBy('bookings.created_at', 'DESC')
            ->findAll();

        return $this->response
            ->setJSON([
                'success' => true,
                'message' => 'Daftar booking berhasil diambil.',
                'data' => $bookings,
            ]);
    }

    /**
     * Membatalkan booking milik user dan mengembalikan kuota.
     */
    public function cancel(int $id): ResponseInterface
    {
        $user = $this->request->user ?? null;

        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'User belum terautentikasi.',
                ]);
        }

        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Booking tidak ditemukan.',
                ]);
        }

        // User hanya boleh membatalkan booking miliknya sendiri.
        if ((int) $booking['user_id'] !== (int) $user->sub) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke booking ini.',
                ]);
        }

        // Booking yang sudah dibatalkan tidak boleh dibatalkan lagi.
        if ($booking['status'] !== 'booked') {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'success' => false,
                    'message' => 'Booking sudah dibatalkan.',
                ]);
        }

        $db = Database::connect();

        try {
            $db->transStart();

            /*
             * Lock event selama pembatalan untuk menjaga
             * konsistensi kuota ketika ada request bersamaan.
             */
            $eventQuery = $db->query(
                'SELECT id, available_quota
                 FROM events
                 WHERE id = ?
                 FOR UPDATE',
                [(int) $booking['event_id']]
            );

            $event = $eventQuery->getRowArray();

            if (!$event) {
                $db->transRollback();

                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Event terkait booking tidak ditemukan.',
                    ]);
            }

            $newAvailableQuota =
                (int) $event['available_quota']
                + (int) $booking['quantity'];

            // Ubah status booking menjadi cancelled.
            $updatedBooking = $this->bookingModel->update($id, [
                'status' => 'cancelled',
            ]);

            if (!$updatedBooking) {
                $db->transRollback();

                ApiLogger::error('Booking status update failed', [
                    'booking_id' => $id,
                    'user_id' => (int) $user->sub,
                    'event_id' => (int) $booking['event_id'],
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Gagal membatalkan booking.',
                    ]);
            }

            // Kembalikan kuota tiket ke event.
            $updatedEvent = $db->table('events')
                ->where('id', (int) $booking['event_id'])
                ->update([
                    'available_quota' => $newAvailableQuota,
                ]);

            if (!$updatedEvent) {
                $db->transRollback();

                ApiLogger::error('Event quota restoration failed', [
                    'booking_id' => $id,
                    'user_id' => (int) $user->sub,
                    'event_id' => (int) $booking['event_id'],
                    'quantity' => (int) $booking['quantity'],
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Gagal mengembalikan kuota event.',
                    ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                ApiLogger::error('Booking cancellation transaction failed', [
                    'booking_id' => $id,
                    'user_id' => (int) $user->sub,
                    'event_id' => (int) $booking['event_id'],
                ]);

                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Transaksi pembatalan booking gagal.',
                    ]);
            }

            ApiLogger::info('Booking cancelled successfully', [
                'booking_id' => $id,
                'user_id' => (int) $user->sub,
                'event_id' => (int) $booking['event_id'],
                'returned_quota' => (int) $booking['quantity'],
            ]);

            return $this->response
                ->setJSON([
                    'success' => true,
                    'message' => 'Booking berhasil dibatalkan.',
                    'data' => [
                        'booking_id' => $id,
                        'status' => 'cancelled',
                        'returned_quota' => (int) $booking['quantity'],
                        'available_quota' => $newAvailableQuota,
                    ],
                ]);
        } catch (\Throwable $e) {
            if ($db->transDepth() > 0) {
                $db->transRollback();
            }

            ApiLogger::error('Booking cancellation failed', [
                'booking_id' => $id,
                'user_id' => (int) $user->sub,
                'event_id' => (int) $booking['event_id'],
                'exception' => $e::class,
                'error_message' => $e->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membatalkan booking.',
                ]);
        }
    }
}