<?php
// generate_image.php
header('Content-Type: application/json');

// Start session and include database connection if not already included
session_start();

// Database configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'eva',
    'dbname' => 'diary'
];

// Create connection
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Retrieve POST data
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;
$text_entry = isset($data['text_entry']) ? trim($data['text_entry']) : '';

if ($id <= 0 || empty($text_entry)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// OpenAI API Configuration
$apiKey = 'API_KEY'; // Replace with your actual API key

// Step 1: Generate a prompt from the status text
$promptGenerationUrl = 'https://api.openai.com/v1/chat/completions';

$prompt = "Create a creative and descriptive image prompt based on the following status update:\n\n\"$text_entry\"";

$promptPayload = [
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a creative assistant that generates image prompts.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'max_tokens' => 100,
    'temperature' => 0.7
];

// Initialize cURL for prompt generation
$ch = curl_init($promptGenerationUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($promptPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$promptResponse = curl_exec($ch);

// Check for errors
if ($promptResponse === false) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the prompt response
$promptData = json_decode($promptResponse, true);

if (!isset($promptData['choices'][0]['message']['content'])) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate prompt from OpenAI API.']);
    exit;
}

$imagePrompt = trim($promptData['choices'][0]['message']['content']);

// Step 2: Generate image using DALL-E
$imageGenerationUrl = 'https://api.openai.com/v1/images/generations';

$imagePayload = [
    'prompt' => $imagePrompt,
    'n' => 1,
    'size' => '512x512'
];

// Initialize cURL for image generation
$ch = curl_init($imageGenerationUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($imagePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$imageResponse = curl_exec($ch);

// Check for errors
if ($imageResponse === false) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the image response
$imageData = json_decode($imageResponse, true);

if (!isset($imageData['data'][0]['url'])) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate image from OpenAI API.']);
    exit;
}

$imageUrl = $imageData['data'][0]['url'];

// Optionally, you can save the image URL to the database if needed
// Example:
// $stmt = $conn->prepare("UPDATE diary_entries SET image_url = ? WHERE id = ?");
// $stmt->bind_param("si", $imageUrl, $id);
// $stmt->execute();
// $stmt->close();

// Return the image URL
echo json_encode(['success' => true, 'image_url' => $imageUrl]);

$conn->close();
?>
