<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "error" => "Method Not Allowed"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$prompt = trim($data["prompt"] ?? "");

if ($prompt == "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Prompt is empty"
    ]);
    exit;
}

if (!defined("GROQ_API_KEY") || empty(GROQ_API_KEY)) {
    http_response_code(500);
    echo json_encode([
        "error" => "Groq API Key is missing"
    ]);
    exit;
}


// موديل Groq
$model = "llama-3.1-8b-instant";


$url = "https://api.groq.com/openai/v1/chat/completions";


$body = [
    "model" => $model,
    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.7
];


$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . GROQ_API_KEY
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);


$response = curl_exec($ch);


if (curl_errno($ch)) {
    echo json_encode([
        "error" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}


$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);


if ($http != 200) {
    echo $response;
    exit;
}


$result = json_decode($response, true);


$reply = $result["choices"][0]["message"]["content"] ?? "لم يتم الحصول على رد.";


echo json_encode([
    "reply" => $reply
], JSON_UNESCAPED_UNICODE);