<?php

namespace App\Controllers;

use App\Models\UserModel;
use MongoDB\BSON\ObjectId;
use Exception;

class ProvidersController extends BaseController
{
    private $userModel;

    public function __construct() {
        try {
            $this->userModel = new UserModel();
            error_log("ProvidersController initialized successfully");
        } catch (Exception $e) {
            error_log("Error initializing ProvidersController: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get provider details by ID
     */
    public function getProviderDetails($id)
    {
        try {
            error_log("Fetching provider details for ID: " . $id);
            
            // Find the user by ID
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                error_log("Provider not found with ID: " . $id);
                return $this->jsonResponse(['error' => 'Provider not found'], 404);
            }

            error_log("Found provider: " . json_encode($user));
            
            // Return only the necessary provider information
            return $this->jsonResponse([
                'id' => $user['id'],
                'display_name' => $user['display_name'] ?? $user['username'],
                'email' => $user['email'],
                'username' => $user['username']
            ]);
        } catch (Exception $e) {
            error_log("Error in getProviderDetails: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse(['error' => 'Error fetching provider details: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available time slots for a provider on a specific date
     */
    public function getAvailableSlots($id, $date)
    {
        try {
            // Find the user by ID
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                return $this->jsonResponse(['error' => 'Provider not found'], 404);
            }

            // Get availability for the specified date
            $availability = $this->db->availability->findOne([
                'user_id' => new ObjectId($id),
                'date' => $date
            ]);

            if (!$availability) {
                return $this->jsonResponse([]);
            }

            // Get existing bookings for the date
            $bookings = $this->db->bookings->find([
                'provider_id' => new ObjectId($id),
                'date' => $date
            ])->toArray();

            // Create a map of booked slots
            $bookedSlots = [];
            foreach ($bookings as $booking) {
                $bookedSlots[$booking->slot_id] = true;
            }

            // Format available slots
            $slots = [];
            foreach ($availability->slots as $slot) {
                $slots[] = [
                    'id' => (string) $slot->_id,
                    'startTime' => $slot->start_time,
                    'endTime' => $slot->end_time,
                    'isBooked' => isset($bookedSlots[(string) $slot->_id])
                ];
            }

            return $this->jsonResponse($slots);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Error fetching available slots'], 500);
        }
    }

    /**
     * Create a new booking for a provider
     */
    public function createBooking($id)
    {
        try {
            $data = $this->getRequestData();
            
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['slot']) || empty($data['date'])) {
                return $this->jsonResponse(['error' => 'Missing required fields'], 400);
            }

            // Find the user by ID
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                return $this->jsonResponse(['error' => 'Provider not found'], 404);
            }

            // Check if the slot is available
            $availability = $this->db->availability->findOne([
                'user_id' => new ObjectId($id),
                'date' => $data['date']
            ]);

            if (!$availability) {
                return $this->jsonResponse(['error' => 'No availability found for this date'], 400);
            }

            // Check if the slot exists and is not booked
            $slotExists = false;
            foreach ($availability->slots as $slot) {
                if ((string) $slot->_id === $data['slot']['id']) {
                    $slotExists = true;
                    break;
                }
            }

            if (!$slotExists) {
                return $this->jsonResponse(['error' => 'Invalid time slot'], 400);
            }

            // Check if the slot is already booked
            $existingBooking = $this->db->bookings->findOne([
                'provider_id' => new ObjectId($id),
                'date' => $data['date'],
                'slot_id' => new ObjectId($data['slot']['id'])
            ]);

            if ($existingBooking) {
                return $this->jsonResponse(['error' => 'This time slot is already booked'], 400);
            }

            // Create the booking
            $booking = [
                'provider_id' => new ObjectId($id),
                'customer_name' => $data['name'],
                'customer_email' => $data['email'],
                'date' => $data['date'],
                'slot_id' => new ObjectId($data['slot']['id']),
                'notes' => $data['notes'] ?? '',
                'created_at' => new \MongoDB\BSON\UTCDateTime(),
                'status' => 'confirmed'
            ];

            $result = $this->db->bookings->insertOne($booking);

            if ($result->getInsertedCount() === 0) {
                return $this->jsonResponse(['error' => 'Failed to create booking'], 500);
            }

            return $this->jsonResponse([
                'message' => 'Booking created successfully',
                'booking_id' => (string) $result->getInsertedId()
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Error creating booking'], 500);
        }
    }
} 