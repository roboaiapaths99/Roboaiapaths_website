<?php
/**
 * RoboAIAPaths Chatbot Handler (PHP)
 * Replaces the Python FastAPI backend entirely.
 * Handles: /chat (public) and /lead (public)
 * Uses Google Gemini API via curl, stores in MySQL.
 */
require_once 'config.php';
require_once 'db_connect.php';

// --- CORS Headers (allow chatbot widget from any page) ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Read input ---
$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : 'chat');

// ============================================================
// ACTION: chat - Main chatbot conversation
// ============================================================
if ($action === 'chat') {
    $message = isset($data['message']) ? trim($data['message']) : '';
    $session_id = isset($data['session_id']) ? trim($data['session_id']) : 'default';

    if (empty($message)) {
        echo json_encode(['reply' => 'Please type a message.']);
        exit;
    }

    // --- Load Knowledge Base ---
    $knowledge_path = __DIR__ . '/roboaiapaths_knowledge.txt';
    $knowledge = '';
    if (file_exists($knowledge_path)) {
        $knowledge = file_get_contents($knowledge_path);
    } else {
        $knowledge = 'RoboAIAPaths teaches Robotics, AI, Coding and STEM education.';
    }

    // --- Extract Lead Info ---
    $lead_data = extractLead($message);

    // --- Build Gemini Prompt ---
    $prompt = "You are the official AI chatbot of RoboAIAPaths.

Use only the knowledge below to answer website visitors.

ROBOAIAPATHS KNOWLEDGE:
{$knowledge}

USER MESSAGE:
{$message}

RESPONSE RULES:
1. Reply in simple English or Hinglish.
2. Keep answer short and helpful.
3. If user asks about course, ask child's class or age.
4. If user wants demo class, ask for name, phone number, child class and course interest.
5. If user already shared name and phone number, thank them and say RoboAIAPaths team will contact them soon.
6. Do not give random information outside RoboAIAPaths.
7. Be friendly and professional.";

    // --- Call Gemini API ---
    $bot_reply = callGeminiAPI($prompt);

    // --- Fallback if Gemini fails ---
    if ($bot_reply === false) {
        if ($lead_data['is_lead'] && !empty($lead_data['phone'])) {
            $bot_reply = "✅ Thank you! Your enquiry has been received. RoboAIAPaths team will contact you soon.";
        } else {
            $bot_reply = "Sorry, our AI assistant is currently busy. Please try again after a few moments or contact us at +91 9990911093.";
        }
    }

    // --- Save Lead if detected ---
    if ($lead_data['is_lead'] && !empty($lead_data['phone'])) {
        saveLead($conn, $lead_data, $message, $session_id);

        if (stripos($bot_reply, 'thank') === false && stripos($bot_reply, 'enquiry') === false) {
            $bot_reply .= "\n\n✅ Thank you! Your enquiry has been saved. RoboAIAPaths team will contact you soon.";
        }
    }

    // --- Log conversation ---
    saveChatLog($conn, $session_id, $message, $bot_reply, $lead_data['is_lead']);

    echo json_encode(['reply' => $bot_reply]);
    exit;
}

// ============================================================
// ACTION: lead - Direct lead submission (from contact forms)
// ============================================================
if ($action === 'lead') {
    $name = isset($data['name']) ? trim($data['name']) : '';
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    $child_class = isset($data['child_class']) ? trim($data['child_class']) : '';
    $course_interest = isset($data['course_interest']) ? trim($data['course_interest']) : '';
    $city = isset($data['city']) ? trim($data['city']) : '';
    $msg = isset($data['message']) ? trim($data['message']) : '';

    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number is required.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chatbot_leads (name, phone, child_class, course_interest, city, message, status) VALUES (?, ?, ?, ?, ?, ?, 'New')");
    $stmt->bind_param("ssssss", $name, $phone, $child_class, $course_interest, $city, $msg);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Lead saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save lead.']);
    }
    $stmt->close();
    exit;
}

// ============================================================
// ACTION: health - Simple health check
// ============================================================
if ($action === 'health') {
    $db_status = (isset($conn) && $conn->ping()) ? 'connected' : 'disconnected';
    $gemini_status = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? 'configured' : 'missing';
    echo json_encode([
        'status' => 'healthy',
        'database' => $db_status,
        'gemini' => $gemini_status
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
exit;

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Call Google Gemini API using curl.
 * Returns the text reply or false on failure.
 */
function callGeminiAPI($prompt) {
    $api_key = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;

    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        error_log("Gemini API Error: HTTP {$http_code} | curl error: {$curl_error} | response: {$response}");
        return false;
    }

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($result['candidates'][0]['content']['parts'][0]['text']);
    }

    error_log("Gemini API unexpected response: " . $response);
    return false;
}

/**
 * Extract lead information from user message using regex.
 */
function extractLead($message) {
    $text = strtolower($message);

    // Phone: Indian 10-digit starting with 6-9
    preg_match('/\b[6-9]\d{9}\b/', $message, $phone_match);
    $phone = !empty($phone_match[0]) ? $phone_match[0] : '';

    // Name
    $name = '';
    if (preg_match('/(my name is|i am|name is)\s+([A-Za-z ]+)/i', $message, $name_match)) {
        $name = trim($name_match[2]);
    }

    // Child class
    $child_class = '';
    if (preg_match('/(class|grade|standard)\s*([0-9]{1,2})/i', $message, $class_match)) {
        $child_class = $class_match[2];
    }

    // Course interest
    $course_interest = '';
    if (strpos($text, 'robotics') !== false || strpos($text, 'robot') !== false) {
        $course_interest = 'Robotics';
    } elseif (strpos($text, 'coding') !== false || strpos($text, 'code') !== false) {
        $course_interest = 'Coding';
    } elseif (strpos($text, 'ai') !== false || strpos($text, 'artificial intelligence') !== false) {
        $course_interest = 'AI';
    } elseif (strpos($text, 'stem') !== false) {
        $course_interest = 'STEM';
    }

    return [
        'is_lead' => !empty($phone),
        'name' => $name,
        'phone' => $phone,
        'child_class' => $child_class,
        'course_interest' => $course_interest,
        'city' => ''
    ];
}

/**
 * Save lead to MySQL chatbot_leads table.
 */
function saveLead($conn, $lead_data, $message, $session_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO chatbot_leads (name, phone, child_class, course_interest, city, message, session_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'New')");
        $stmt->bind_param("sssssss",
            $lead_data['name'],
            $lead_data['phone'],
            $lead_data['child_class'],
            $lead_data['course_interest'],
            $lead_data['city'],
            $message,
            $session_id
        );
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Lead Save Error: " . $e->getMessage());
    }
}

/**
 * Save chat conversation log to MySQL chatbot_logs table.
 */
function saveChatLog($conn, $session_id, $user_message, $bot_reply, $lead_detected) {
    try {
        $ld = $lead_detected ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO chatbot_logs (session_id, user_message, bot_reply, lead_detected) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $session_id, $user_message, $bot_reply, $ld);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Chat Log Error: " . $e->getMessage());
    }
}
?>
