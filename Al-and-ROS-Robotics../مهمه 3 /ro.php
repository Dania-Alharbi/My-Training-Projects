<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';


// =====================================================
// التحقق من مفتاح Groq
// =====================================================

if (!defined("GROQ_API_KEY") || empty(GROQ_API_KEY)) {

    echo json_encode(
        [
            "error" => "مفتاح Groq API غير موجود في config.php."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// =====================================================
// دالة إرسال JSON
// =====================================================

function sendJson($data, $status = 200)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// =====================================================
// التحقق من POST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendJson(
        [
            "error" => "يجب إرسال الطلب باستخدام POST."
        ],
        405
    );
}


$prompt = "";


// =====================================================
// أولاً: هل يوجد ملف صوتي؟
// =====================================================

if (
    isset($_FILES["audio"]) &&
    $_FILES["audio"]["error"] === UPLOAD_ERR_OK
) {

    $audioPath = $_FILES["audio"]["tmp_name"];

    $originalName =
        $_FILES["audio"]["name"] ?? "audio";

    $mimeType =
        $_FILES["audio"]["type"] ?? "audio/mpeg";


    // -------------------------------------------------
    // التحقق من حجم الملف
    // -------------------------------------------------

    $maxSize = 25 * 1024 * 1024;

    if ($_FILES["audio"]["size"] > $maxSize) {

        sendJson(
            [
                "error" =>
                "حجم الملف كبير جدًا. الحد الأقصى 25MB."
            ],
            413
        );
    }


    // -------------------------------------------------
    // إرسال الصوت إلى Groq Whisper
    // -------------------------------------------------

    $transcriptionUrl =
        "https://api.groq.com/openai/v1/audio/transcriptions";


    $curlFile = new CURLFile(
        $audioPath,
        $mimeType,
        $originalName
    );


    $postFields = [
        "model" => "whisper-large-v3",
        "file" => $curlFile,
        "response_format" => "json"
    ];


    $ch = curl_init(
        $transcriptionUrl
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            "Authorization: Bearer " .
            GROQ_API_KEY
        ]
    );

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        $postFields
    );

    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        120
    );


    $response =
        curl_exec($ch);


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    if (curl_errno($ch)) {

        $error =
            curl_error($ch);

        curl_close($ch);

        sendJson(
            [
                "error" =>
                "فشل اتصال الصوت بالخادم: " .
                $error
            ],
            500
        );
    }


    curl_close($ch);


    $result =
        json_decode(
            $response,
            true
        );


    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        $message =
            $result["error"]["message"]
            ?? "خطأ غير معروف من Groq.";

        sendJson(
            [
                "error" =>
                "خطأ في تحويل الصوت: " .
                $message
            ],
            $httpCode
        );
    }


    $prompt =
        trim(
            $result["text"] ?? ""
        );


    if (empty($prompt)) {

        sendJson(
            [
                "error" =>
                "لم يتمكن النظام من استخراج كلام واضح من الملف الصوتي."
            ],
            400
        );
    }
}


// =====================================================
// ثانيًا: إذا لم يكن ملفًا، نقرأ النص JSON
// =====================================================

else {

    $rawInput =
        file_get_contents(
            "php://input"
        );


    $data =
        json_decode(
            $rawInput,
            true
        );


    if (!is_array($data)) {

        sendJson(
            [
                "error" =>
                "لم يتم استلام بيانات صحيحة."
            ],
            400
        );
    }


    $prompt =
        trim(
            $data["prompt"] ?? ""
        );
}


// =====================================================
// التأكد من وجود النص
// =====================================================

if (empty($prompt)) {

    sendJson(
        [
            "error" =>
            "لم يتم استلام أي نص أو صوت صالح."
        ],
        400
    );
}


// =====================================================
// إرسال النص إلى نموذج Groq
// =====================================================

$chatUrl =
    "https://api.groq.com/openai/v1/chat/completions";


$body = [

    "model" =>
    "llama-3.3-70b-versatile",

    "messages" => [

        [
            "role" => "system",

            "content" =>
            "أنت مساعد ذكي ومفيد. " .
            "أجب بوضوح وبأفضل أسلوب، " .
            "وبنفس لغة المستخدم."
        ],

        [
            "role" => "user",

            "content" => $prompt
        ]
    ],

    "temperature" => 0.5
];


$ch =
    curl_init($chatUrl);


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Content-Type: application/json",
        "Authorization: Bearer " .
        GROQ_API_KEY
    ]
);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode(
        $body,
        JSON_UNESCAPED_UNICODE
    )
);

curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    120
);


$response =
    curl_exec($ch);


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


if (curl_errno($ch)) {

    $error =
        curl_error($ch);

    curl_close($ch);

    sendJson(
        [
            "error" =>
            "فشل اتصال الشات بالخادم: " .
            $error
        ],
        500
    );
}


curl_close($ch);


$result =
    json_decode(
        $response,
        true
    );


// =====================================================
// فحص رد Groq
// =====================================================

if (
    $httpCode < 200 ||
    $httpCode >= 300
) {

    $message =
        $result["error"]["message"]
        ?? "خطأ غير معروف من Groq.";

    sendJson(
        [
            "error" =>
            "خطأ من Groq: " .
            $message
        ],
        $httpCode
    );
}


$reply =
    $result["choices"][0]["message"]["content"]
    ?? "";


if (empty($reply)) {

    sendJson(
        [
            "error" =>
            "لم يتم الحصول على رد من النموذج."
        ],
        500
    );
}


// =====================================================
// الرد النهائي للواجهة
// =====================================================

sendJson(
    [
        "transcription" => $prompt,
        "reply" => $reply
    ]
);

?>
