<?php
/**
 * Digital Samba API Integration Test
 * 
 * This script tests the Digital Samba integration by:
 * 1. Creating a test user with Digital Samba credentials (if one doesn't exist)
 * 2. Creating a test booking
 * 3. Generating meeting links for the booking
 * 4. Testing the API directly
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Manual autoload for App classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

class DigitalSambaTest {
    private $userModel;
    private $bookingModel;
    private $dsController;
    private $testUser;
    private $testBooking;
    
    /**
     * Constructor - initialize models and controllers
     */
    public function __construct() {
        $this->userModel = new \App\Models\UserModel();
        $this->bookingModel = new \App\Models\BookingModel();
        $this->dsController = new \App\Controllers\DigitalSambaController();
    }
    
    /**
     * Run all tests
     */
    public function runTests() {
        echo "=== Digital Samba Integration Test ===\n\n";
        
        try {
            // Step 1: Create or find test user
            $this->setupTestUser();
            
            // Step 2: Create test booking
            $this->createTestBooking();
            
            // Step 3: Test meeting link generation
            $this->testMeetingLinkGeneration();
            
            // Step 4: Test direct API access
            $this->testDirectApiAccess();
            
            echo "\n=== All Tests Completed ===\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Create or find a test user with Digital Samba credentials
     */
    private function setupTestUser() {
        echo "Creating/finding test user...\n";
        
        // Look for existing test user
        $testUsername = 'ds_test_user';
        $this->testUser = $this->userModel->findByUsername($testUsername);
        
        if ($this->testUser) {
            echo "Found existing test user: {$testUsername}\n";
            
            // Check if user has complete Digital Samba credentials
            if (empty($this->testUser['developer_key']) || empty($this->testUser['team_id'])) {
                echo "Test user doesn't have complete Digital Samba credentials. Please provide:\n";
                echo "Developer Key: ";
                $developerKey = trim(fgets(STDIN));
                
                echo "Team ID (required): ";
                $teamId = trim(fgets(STDIN));
                
                // Validate that team ID is provided
                while (empty($teamId)) {
                    echo "Team ID is required for Digital Samba API. Please enter a valid Team ID: ";
                    $teamId = trim(fgets(STDIN));
                }
                
                // Update user with credentials
                $this->userModel->update((string)$this->testUser['_id'], [
                    'developer_key' => $developerKey,
                    'team_id' => $teamId
                ]);
                
                // Reload user
                $this->testUser = $this->userModel->findByUsername($testUsername);
                echo "Updated test user with Digital Samba credentials\n";
            } else {
                echo "Test user already has complete Digital Samba credentials\n";
            }
        } else {
            // Create new test user
            echo "Creating new test user...\n";
            echo "Please provide Digital Samba credentials:\n";
            
            echo "Developer Key: ";
            $developerKey = trim(fgets(STDIN));
            
            echo "Team ID (required): ";
            $teamId = trim(fgets(STDIN));
            
            // Validate that team ID is provided
            while (empty($teamId)) {
                echo "Team ID is required for Digital Samba API. Please enter a valid Team ID: ";
                $teamId = trim(fgets(STDIN));
            }
            
            // Register user
            $userData = [
                'username' => $testUsername,
                'email' => 'ds_test@example.com',
                'password' => 'password123',
                'display_name' => 'DS Test Provider',
                'developer_key' => $developerKey,
                'team_id' => $teamId
            ];
            
            $this->testUser = $this->userModel->register($userData);
            
            if (!$this->testUser) {
                throw new Exception("Failed to create test user");
            }
            
            echo "Created new test user with Digital Samba credentials\n";
        }
        
        echo "Test user ID: " . (string)$this->testUser['_id'] . "\n";
        echo "Test user has developer key: " . (empty($this->testUser['developer_key']) ? 'No' : 'Yes') . "\n";
        echo "Test user has team ID: " . (empty($this->testUser['team_id']) ? 'No' : 'Yes') . "\n";
    }
    
    /**
     * Create a test booking
     */
    private function createTestBooking() {
        echo "\nCreating test booking...\n";
        
        // First, create an availability slot for the test user
        echo "Creating availability slot...\n";
        
        $availabilityModel = new \App\Models\AvailabilityModel();
        
        // Today's date
        $date = new DateTime();
        $dateStr = $date->format('Y-m-d');
        
        // Start and end times
        $startTime = new DateTime();
        $startTime->setTime($startTime->format('H'), 0, 0); // Round to current hour
        $endTime = clone $startTime;
        $endTime->modify('+1 hour');
        
        $startTimeStr = $startTime->format('H:i:s');
        $endTimeStr = $endTime->format('H:i:s');
        
        // Create a slot using the model methods
        $slots = [
            [
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'is_available' => true
            ]
        ];
        
        // Use the addSlots method to create an availability slot
        $result = $availabilityModel->addSlots((string)$this->testUser['_id'], $slots);
        
        if (!$result) {
            throw new Exception("Failed to create availability slot");
        }
        
        // Find the slot we just created
        $createdSlots = $availabilityModel->getSlots(
            (string)$this->testUser['_id'],
            $dateStr,
            $dateStr
        );
        
        if (empty($createdSlots)) {
            throw new Exception("Failed to find the created availability slot");
        }
        
        $slot = $createdSlots[0];
        $slotId = $slot['id'];
        
        if (!$slotId) {
            throw new Exception("Failed to create availability slot");
        }
        
        echo "Created availability slot ID: " . (string)$slotId . "\n";
        
        // Now create the booking using the slot
        $bookingData = [
            'provider_id' => (string)$this->testUser['_id'],
            'slot_id' => (string)$slotId, // Required field
            'title' => 'Test Booking ' . date('Y-m-d H:i:s'),
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'customer@example.com'
            ],
            'status' => 'confirmed'
        ];
        
        echo "Creating booking with data: " . json_encode($bookingData) . "\n";
        
        // Insert into database
        $result = $this->bookingModel->create($bookingData);
        
        if (!$result) {
            throw new Exception("Failed to create test booking");
        }
        
        // Get the booking ID (it may be in the return value or we need to extract it)
        $bookingId = is_array($result) && isset($result['_id']) ? (string)$result['_id'] : null;
        
        if (!$bookingId) {
            // Try to find the booking by other means
            echo "Booking created but couldn't get ID from result. Trying to find it...\n";
            
            // Look for the booking by user ID and slot ID
            $bookings = $this->bookingModel->getProviderBookings((string)$this->testUser['_id'], [
                'slot_id' => new \MongoDB\BSON\ObjectId((string)$slotId)
            ]);
            
            if (!empty($bookings)) {
                $this->testBooking = $bookings[0];
                $bookingId = $this->testBooking['id'];
                echo "Found test booking ID: {$bookingId}\n";
            } else {
                throw new Exception("Failed to locate the created booking");
            }
        } else {
            // Get the booking
            $this->testBooking = $this->bookingModel->getById($bookingId);
            echo "Created test booking ID: {$bookingId}\n";
        }
    }
    
    /**
     * Test meeting link generation
     */
    private function testMeetingLinkGeneration() {
        echo "\nTesting meeting link generation...\n";
        
        // Get booking ID
        $bookingId = (string)$this->testBooking['id']; // Note: use 'id' not '_id' if formatted
        echo "Using booking ID: {$bookingId}\n";
        
        // Skip testing the controller's generateMeetingLinks method directly
        // since it uses Response::json() which outputs directly
        
        // Instead, use the DigitalSambaController's internal methods to test API integration
        echo "\nTesting direct meeting link generation...\n";
        
        $singleLink = $this->dsController->generateMeetingLink([
            'provider_id' => (string)$this->testUser['_id'],
            'booking_id' => $bookingId,
            'display_name' => 'Single Test Participant',
            'role' => 'attendee', // Use 'attendee' as the supported role
            'participant_id' => 'test-' . uniqid()
        ]);
        
        if (!$singleLink || isset($singleLink['error'])) {
            echo "ERROR: Failed to generate single participant link. Response:\n";
            print_r($singleLink);
            return;
        }
        
        echo "Successfully generated single participant link:\n";
        echo "URL: " . $singleLink['url'] . "\n";
        echo "Room ID: " . ($singleLink['ds_room_id'] ?? 'N/A') . "\n";
        echo "Token: " . (substr($singleLink['token'] ?? 'N/A', 0, 20) . '...') . "\n";
        
        // Now check the booking to see if it has meeting links already
        echo "\nChecking if booking has meeting links...\n";
        $updatedBooking = $this->bookingModel->getById($bookingId);
        
        if (isset($updatedBooking['provider_link']) || 
            (isset($updatedBooking['customer']) && 
             isset($updatedBooking['customer']['customer_link']))) {
            
            echo "Booking already has meeting links:\n";
            echo "Provider Link: " . ($updatedBooking['provider_link'] ?? 'N/A') . "\n";
            echo "Customer Link: " . ($updatedBooking['customer']['customer_link'] ?? 'N/A') . "\n";
            echo "Digital Samba Room ID: " . ($updatedBooking['ds_room_id'] ?? 'N/A') . "\n";
        } else {
            echo "Booking does not have meeting links yet.\n";
        }
    }
    
    /**
     * Test direct API access to Digital Samba
     */
    private function testDirectApiAccess() {
        echo "\nTesting direct API access to Digital Samba...\n";
        
        // Extract developer key from test user
        $developerKey = $this->testUser['developer_key'] ?? null;
        
        if (!$developerKey) {
            echo "ERROR: Test user doesn't have a developer key. Skipping direct API test.\n";
            return;
        }
        
        // Test API call to list rooms
        $url = "https://api.digitalsamba.com/api/v1/rooms";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $developerKey
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            echo "ERROR: Digital Samba API request failed: {$error}\n";
            return;
        }
        
        $roomsResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            echo "API Error (HTTP {$httpCode}): " . json_encode($roomsResponse) . "\n";
            return;
        }
        
        $rooms = $roomsResponse['items'] ?? [];
        $roomCount = count($rooms);
        
        echo "Successfully connected to Digital Samba API\n";
        echo "Found {$roomCount} rooms\n";
        
        if ($roomCount > 0) {
            echo "First room: " . ($rooms[0]['name'] ?? 'Unnamed') . " (ID: " . ($rooms[0]['id'] ?? 'N/A') . ")\n";
        }
    }
}

// Run the tests
$test = new DigitalSambaTest();
$test->runTests();