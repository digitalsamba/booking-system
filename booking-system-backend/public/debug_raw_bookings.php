<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Raw MongoDB Bookings Debug</h1>";

// Create MongoDB client
$client = new \MongoDB\Client(DB_CONNECTION_STRING);
$db = $client->selectDatabase(DB_NAME);
$collection = $db->selectCollection('bookings');

// Get provider ID from query params
$providerId = $_GET['provider_id'] ?? null;

if ($providerId) {
    echo "<h2>Searching for provider_id: $providerId</h2>";
    
    // Test different query formats
    echo "<h3>Test 1: Plain String Query</h3>";
    try {
        $cursor = $collection->find(['provider_id' => $providerId]);
        $count = 0;
        echo "<ul>";
        foreach ($cursor as $doc) {
            $count++;
            $id = (string)$doc['_id'];
            $provId = (string)$doc['provider_id'];
            $startTime = isset($doc['start_time']) ? $doc['start_time']->toDateTime()->format('Y-m-d H:i:s') : 'N/A';
            echo "<li>ID: $id | Provider: $provId | Start: $startTime</li>";
        }
        echo "</ul>";
        echo "<p>Found $count booking(s) with string query.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Test 2: ObjectId Query</h3>";
    try {
        $objId = new \MongoDB\BSON\ObjectId($providerId);
        $cursor = $collection->find(['provider_id' => $objId]);
        $count = 0;
        echo "<ul>";
        foreach ($cursor as $doc) {
            $count++;
            $id = (string)$doc['_id'];
            $provId = (string)$doc['provider_id'];
            $startTime = isset($doc['start_time']) ? $doc['start_time']->toDateTime()->format('Y-m-d H:i:s') : 'N/A';
            echo "<li>ID: $id | Provider: $provId | Start: $startTime</li>";
        }
        echo "</ul>";
        echo "<p>Found $count booking(s) with ObjectId query.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Test 3: Direct Document Inspection</h3>";
    try {
        $cursor = $collection->find([], ['limit' => 10]);
        $count = 0;
        echo "<table border='1' cellpadding='4' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Provider ID</th><th>Provider ID Type</th><th>Start Time</th></tr>";
        foreach ($cursor as $doc) {
            $count++;
            $id = (string)$doc['_id'];
            $provId = $doc['provider_id'];
            $provIdType = gettype($provId);
            if ($provIdType == 'object') {
                $provIdType .= ' (' . get_class($provId) . ')';
                $provIdStr = (string)$provId;
            } else {
                $provIdStr = $provId;
            }
            $startTime = isset($doc['start_time']) ? $doc['start_time']->toDateTime()->format('Y-m-d H:i:s') : 'N/A';
            
            $highlight = ($provIdStr == $providerId) ? "background-color:yellow" : "";
            
            echo "<tr style='$highlight'>";
            echo "<td>$id</td><td>$provIdStr</td><td>$provIdType</td><td>$startTime</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>Found $count booking(s) total.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    // Display form to input provider ID
    echo "<form method='get'>";
    echo "<p>Enter provider ID: <input type='text' name='provider_id' size='30'></p>";
    echo "<p><input type='submit' value='Search'></p>";
    echo "</form>";
    
    // Show all recent bookings
    echo "<h2>Recent Bookings</h2>";
    try {
        $cursor = $collection->find([], ['limit' => 20, 'sort' => ['_id' => -1]]);
        $count = 0;
        echo "<table border='1' cellpadding='4' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Provider ID</th><th>Provider ID Type</th><th>Start Time</th><th>Customer</th></tr>";
        foreach ($cursor as $doc) {
            $count++;
            $id = (string)$doc['_id'];
            $provId = $doc['provider_id'];
            $provIdType = gettype($provId);
            if ($provIdType == 'object') {
                $provIdType .= ' (' . get_class($provId) . ')';
                $provIdStr = (string)$provId;
            } else {
                $provIdStr = $provId;
            }
            $startTime = isset($doc['start_time']) ? $doc['start_time']->toDateTime()->format('Y-m-d H:i:s') : 'N/A';
            $customer = isset($doc['customer']['name']) ? $doc['customer']['name'] : 'N/A';
            
            echo "<tr>";
            echo "<td>$id</td><td>$provIdStr</td><td>$provIdType</td><td>$startTime</td><td>$customer</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>Found $count booking(s) total.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr><p><a href='debug_raw_bookings.php'>Back to form</a></p>";
?>