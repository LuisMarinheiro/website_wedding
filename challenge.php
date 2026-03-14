<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Question pool — answers are always in Portuguese, lowercase, accent-stripped for comparison
$questions = [
    'q1' => ['answers' => ['felicidade']],
    'q2' => ['answers' => ['mais']],
    'q3' => ['answers' => ['papa', 'papá']],
    'q4' => ['answers' => ['no', 'nó']],
];

/**
 * Normalize a string for comparison: lowercase, trim, strip accents.
 */
function normalize(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    // Remove common Portuguese accents
    $search  = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
    $replace = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
    return str_replace($search, $replace, $str);
}

// Validate session has a question
if (empty($_SESSION['challenge_question_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_question']);
    exit;
}

$questionId = $_SESSION['challenge_question_id'];
$userAnswer = $_POST['answer'] ?? '';

if (!isset($questions[$questionId])) {
    echo json_encode(['ok' => false, 'error' => 'invalid_question']);
    exit;
}

// Normalize user answer
$normalizedAnswer = normalize($userAnswer);

// Check against all accepted answers (also normalized)
$accepted = false;
foreach ($questions[$questionId]['answers'] as $validAnswer) {
    if ($normalizedAnswer === normalize($validAnswer)) {
        $accepted = true;
        break;
    }
}

if ($accepted) {
    $_SESSION['challenge_passed'] = true;

    // Return the protected sections HTML
    ob_start();
    include __DIR__ . '/includes/protected-sections.php';
    $html = ob_get_clean();

    echo json_encode(['ok' => true, 'html' => $html]);
} else {
    echo json_encode(['ok' => false, 'error' => 'wrong_answer']);
}
