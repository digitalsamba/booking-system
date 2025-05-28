// Test script for Booking System API
// This script tests authentication and availability endpoints

const API_URL = 'http://localhost:8000';

// Test user credentials
const testUser = {
    username: 'apitest_' + Date.now(),  // Unique username
    email: 'apitest_' + Date.now() + '@example.com',  // Unique email
    password: 'testpass123',
    display_name: 'API Test User'
};

// Helper function to make API requests
async function apiRequest(endpoint, method = 'GET', data = null, token = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`${API_URL}${endpoint}`, options);
        const responseData = await response.json();
        return {
            ok: response.ok,
            status: response.status,
            data: responseData
        };
    } catch (error) {
        return {
            ok: false,
            status: 0,
            data: { error: error.message }
        };
    }
}

// Main test function
async function runTests() {
    console.log('Starting API tests...\n');
    
    // Test 1: Register user
    console.log('1. Testing user registration...');
    const registerResult = await apiRequest('/api/auth/register', 'POST', testUser);
    console.log('Register result:', registerResult);
    
    // Test 2: Login
    console.log('\n2. Testing user login...');
    const loginResult = await apiRequest('/api/auth/login', 'POST', {
        username: testUser.username,
        password: testUser.password
    });
    console.log('Login result:', loginResult);
    
    if (!loginResult.ok || !loginResult.data.data.token) {
        console.error('Login failed, cannot continue tests');
        return;
    }
    
    const token = loginResult.data.data.token;
    console.log('Token received:', token.substring(0, 20) + '...');
    
    // Test 3: Get availability (should be empty initially)
    console.log('\n3. Testing GET /api/availability...');
    const availabilityResult = await apiRequest('/api/availability', 'GET', null, token);
    console.log('Availability result:', availabilityResult);
    
    // Test 4: Generate availability slots
    console.log('\n4. Testing POST /api/availability/generate...');
    const today = new Date();
    const nextWeek = new Date(today);
    nextWeek.setDate(nextWeek.getDate() + 7);
    
    const generateData = {
        start_date: today.toISOString().split('T')[0],
        end_date: nextWeek.toISOString().split('T')[0],
        slot_duration: 30,
        daily_start_time: '09:00',
        daily_end_time: '17:00',
        days_of_week: [1, 2, 3, 4, 5] // Monday to Friday
    };
    
    const generateResult = await apiRequest('/api/availability/generate', 'POST', generateData, token);
    console.log('Generate result:', generateResult);
    
    // Test 5: Get availability again (should have slots now)
    console.log('\n5. Testing GET /api/availability again...');
    const availabilityResult2 = await apiRequest('/api/availability', 'GET', null, token);
    console.log('Availability result after generation:', availabilityResult2);
}

// Run the tests
runTests().then(() => {
    console.log('\nTests completed!');
}).catch(error => {
    console.error('Test error:', error);
});
