<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Booking System Debug</h1>";

// Create MongoDB client
$client = new \MongoDB\Client(DB_CONNECTION_STRING);
$db = $client->selectDatabase(DB_NAME);
$collection = $db->selectCollection('bookings');

echo "<h2>Database Connection Info</h2>";
echo "<p>Connection: " . DB_CONNECTION_STRING . "</p>";
echo "<p>Database: " . DB_NAME . "</p>";

echo "<h2>Recent Bookings</h2>";

// Get most recent bookings
$cursor = $collection->find(
    [], // Empty query to get all
    [
        'limit' => 10,
        'sort' => ['_id' => -1] // Sort by ID descending (most recent first)
    ]
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Provider ID</th>
        <th>Customer</th>
        <th>Date/Time</th>
        <th>Status</th>
        <th>Created</th>
      </tr>";

$count = 0;
foreach ($cursor as $doc) {
    $count++;
    $id = (string)$doc['_id'] ?? 'unknown';
    $providerId = $doc['provider_id'] ?? 'unknown';
    $customer = $doc['customer_name'] ?? ($doc['customer']['name'] ?? 'unknown');
    
    $startTime = '';
    if (isset($doc['start_time'])) {
        if (is_object($doc['start_time']) && method_exists($doc['start_time'], 'toDateTime')) {
            $startTime = $doc['start_time']->toDateTime()->format('Y-m-d H:i');
        } else {
            $startTime = $doc['start_time'];
        }
    } elseif (isset($doc['date'])) {
        $startTime = $doc['date'];
    } else {
        $startTime = 'unknown';
    }
    
    $status = $doc['status'] ?? 'unknown';
    $created = isset($doc['created_at']) ? date('Y-m-d H:i', strtotime($doc['created_at'])) : 'unknown';
    
    echo "<tr>
            <td>{$id}</td>
            <td>{$providerId}</td>
            <td>{$customer}</td>
            <td>{$startTime}</td>
            <td>{$status}</td>
            <td>{$created}</td>
          </tr>";
}

echo "</table>";

if ($count === 0) {
    echo "<p>No bookings found in the database.</p>";
} else {
    echo "<p>Found {$count} booking(s).</p>";
}

echo "<h2>Test Specific Provider</h2>";
echo "<form method='get'>
        Provider ID: <input type='text' name='provider_id'>
        <input type='submit' value='Find Bookings'>
      </form>";

if (isset($_GET['provider_id'])) {
    $providerId = $_GET['provider_id'];
    echo "<h3>Bookings for Provider ID: {$providerId}</h3>";
    
    $cursor = $collection->find(['provider_id' => $providerId]);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Date/Time</th>
            <th>Status</th>
            <th>Created</th>
          </tr>";
    
    $count = 0;
    foreach ($cursor as $doc) {
        $count++;
        $id = (string)$doc['_id'] ?? 'unknown';
        $customer = $doc['customer_name'] ?? ($doc['customer']['name'] ?? 'unknown');
        
        $startTime = '';
        if (isset($doc['start_time'])) {
            if (is_object($doc['start_time']) && method_exists($doc['start_time'], 'toDateTime')) {
                $startTime = $doc['start_time']->toDateTime()->format('Y-m-d H:i');
            } else {
                $startTime = $doc['start_time'];
            }
        } elseif (isset($doc['date'])) {
            $startTime = $doc['date'];
        } else {
            $startTime = 'unknown';
        }
        
        $status = $doc['status'] ?? 'unknown';
        $created = isset($doc['created_at']) ? date('Y-m-d H:i', strtotime($doc['created_at'])) : 'unknown';
        
        echo "<tr>
                <td>{$id}</td>
                <td>{$customer}</td>
                <td>{$startTime}</td>
                <td>{$status}</td>
                <td>{$created}</td>
              </tr>";
    }
    
    echo "</table>";
    
    if ($count === 0) {
        echo "<p>No bookings found for provider ID: {$providerId}</p>";
    } else {
        echo "<p>Found {$count} booking(s) for provider ID: {$providerId}.</p>";
    }
}

echo "<h2>API Test</h2>";
echo "<p>Visit <a href='bookings?limit=5' target='_blank'>bookings?limit=5</a> to test the API directly (requires authentication).</p>";
?>