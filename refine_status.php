<?php
// refine_status.php
header('Content-Type: application/json');

session_start();

$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'eva',
    'dbname' => 'diary'
];

$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;
$text_entry = isset($data['text_entry']) ? trim($data['text_entry']) : '';

if ($id <= 0 || empty($text_entry)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// OpenAI API Configuration
$apiKey = 'YOUR-API-KEY-HERE'; // Replace with your actual API key
$apiUrl = 'https://api.openai.com/v1/chat/completions';

// Prepare the prompt
$prompt = "Refine the following status update to make it more engaging and clear and keep it below 100 characters:\n\n\"$text_entry\"";

// Prepare the payload
$payload = [
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'max_tokens' => 300,
    'temperature' => 0.7
];

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// Check for errors
if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the response
$responseData = json_decode($response, true);

if (isset($responseData['choices'][0]['message']['content'])) {
    $refinedText = trim($responseData['choices'][0]['message']['content']);

    // Update the database
    $stmt = $conn->prepare("UPDATE diary_entries SET text_entry = ? WHERE id = ?");
    $stmt->bind_param("si", $refinedText, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'refined_text' => $refinedText]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid response from OpenAI API.']);
}

$conn->close();
?>
