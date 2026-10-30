<?php
/**
 * RoboAIAPaths Chatbot Handler (PHP)
 * Replaces the Python FastAPI backend entirely.
 * Handles: /chat (public) and /lead (public)
 * Uses Google Gemini API via curl, stores in MySQL.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

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
    $gemini_error = '';
    $bot_reply = callGeminiAPI($prompt, $gemini_error);

    // --- Fallback if Gemini fails ---
    if ($bot_reply === false) {
        error_log("Chatbot: Gemini API failed for session {$session_id}. Error: {$gemini_error}");
        if ($lead_data['is_lead'] && !empty($lead_data['phone'])) {
            $bot_reply = "✅ Thank you! Your enquiry has been received. RoboAIAPaths team will contact you soon.";
        } else {
            $bot_reply = "Sorry, our AI assistant is currently busy. Please try again after a few moments or contact us at +91 9990911093.";
            // In debug mode, append the actual error for troubleshooting
            if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
                $bot_reply .= "\n\n[DEBUG] Error: " . $gemini_error;
            }
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
 * Tries multiple models for resilience against rate limits.
 * Returns the text reply or false on failure.
 * @param string $prompt  The prompt to send
 * @param string &$error  Reference to capture the last error message for debugging
 */
function callGeminiAPI($prompt, &$error = '') {
    $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    if (empty($api_key)) {
        $error = 'API key is empty or not configured';
        error_log("Gemini API Error: {$error}");
        return false;
    }

    // Try models in order (flash-lite has higher free-tier RPM)
    $models = ['gemini-2.0-flash-lite', 'gemini-2.0-flash'];

    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ]);

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

        // Try with SSL verification first, then fallback without it
        // (XAMPP/WAMP on Windows often lack proper CA certificates)
        $ssl_modes = [true, false];

        foreach ($ssl_modes as $verify_ssl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
            if (!$verify_ssl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);

            // If SSL cert error and we haven't tried without verify yet, retry
            if ($curl_errno !== 0 && $verify_ssl && in_array($curl_errno, [60, 77, 35])) {
                error_log("Gemini API ({$model}): SSL cert error #{$curl_errno}, retrying without SSL verify...");
                continue; // try next ssl_mode (false)
            }

            // If curl itself failed (network error)
            if ($response === false || $curl_errno !== 0) {
                $error = "cURL error #{$curl_errno}: {$curl_error} (model: {$model})";
                error_log("Gemini API Error ({$model}): {$error}");
                break; // skip this model's SSL retry, move to next model
            }

            // Success path - we got a response
            if ($http_code === 429) {
                $error = "Rate limited (429) on model {$model}";
                error_log("Gemini API Rate Limited ({$model}): trying next model...");
                break; // move to next model
            }

            if ($http_code === 400 || $http_code === 403) {
                $resp_data = json_decode($response, true);
                $api_msg = isset($resp_data['error']['message']) ? $resp_data['error']['message'] : $response;
                $error = "HTTP {$http_code}: {$api_msg} (model: {$model})";
                error_log("Gemini API Error ({$model}): {$error}");
                // Invalid key errors won't be fixed by trying another model
                return false;
            }

            if ($http_code !== 200) {
                $error = "HTTP {$http_code} from {$model}: " . substr($response, 0, 200);
                error_log("Gemini API Error ({$model}): HTTP {$http_code} | response: {$response}");
                break; // move to next model
            }

            $result = json_decode($response, true);

            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($result['candidates'][0]['content']['parts'][0]['text']);
            }

            // Handle safety blocks or empty candidates
            if (isset($result['candidates'][0]['finishReason']) && $result['candidates'][0]['finishReason'] === 'SAFETY') {
                $error = "Response blocked by safety filter (model: {$model})";
                error_log("Gemini API Safety Block ({$model}): " . $response);
                break; // try next model
            }

            $error = "Unexpected response structure from {$model}";
            error_log("Gemini API unexpected response ({$model}): " . $response);
            break; // move to next model
        }
    }

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
    if (!$conn || $conn->connect_error) {
        error_log("Lead Save Error: Database connection is not available.");
        return;
    }
    try {
        $stmt = $conn->prepare("INSERT INTO chatbot_leads (name, phone, child_class, course_interest, city, message, session_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'New')");
        if ($stmt) {
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
        } else {
            error_log("Lead Save Error: Failed to prepare statement: " . $conn->error);
        }
    } catch (Throwable $e) {
        error_log("Lead Save Error: " . $e->getMessage());
    }
}

/**
 * Save chat conversation log to MySQL chatbot_logs table.
 */
function saveChatLog($conn, $session_id, $user_message, $bot_reply, $lead_detected) {
    if (!$conn || $conn->connect_error) {
        error_log("Chat Log Error: Database connection is not available.");
        return;
    }
    try {
        $ld = $lead_detected ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO chatbot_logs (session_id, user_message, bot_reply, lead_detected) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssi", $session_id, $user_message, $bot_reply, $ld);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Chat Log Error: Failed to prepare statement: " . $conn->error);
        }
    } catch (Throwable $e) {
        error_log("Chat Log Error: " . $e->getMessage());
    }
}
?>
