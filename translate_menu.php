<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'no key']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];
$lang  = $input['lang']  ?? 'en';

if (empty($items) || !in_array($lang, ['en', 'zh'], true)) {
    echo json_encode(['translations' => []]);
    exit;
}

$langNames = ['en' => 'English', 'zh' => 'Chinese (Simplified)'];
$langName  = $langNames[$lang];

// Build list for Gemini
$lines = [];
foreach ($items as $item) {
    $id   = (int)($item['id']   ?? 0);
    $name = trim($item['name']  ?? '');
    if ($id && $name) {
        $lines[] = "{$id}|{$name}";
    }
}
if (empty($lines)) {
    echo json_encode(['translations' => []]);
    exit;
}

$itemList = implode("\n", $lines);

$prompt = <<<PROMPT
You are a restaurant menu translator. Translate these Japanese menu item names into {$langName}.
Keep translations natural for a restaurant context. Return ONLY a JSON object where each key is the item ID and value is the translated name.
Do not include any other text or markdown.

Items (format: ID|JapaneseName):
{$itemList}

Return format example:
{"1":"Translated Name 1","2":"Translated Name 2"}
PROMPT;

$url     = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
$payload = json_encode([
    'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.2,
        'maxOutputTokens' => 1000,
        'thinkingConfig'  => ['thinkingBudget' => 0],
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST          => true,
    CURLOPT_POSTFIELDS    => $payload,
    CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER=> true,
    CURLOPT_TIMEOUT       => 20,
]);
$resp  = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$resp || $code2 !== 200) {
    echo json_encode(['translations' => [], 'error' => 'api_error_' . $code2]);
    exit;
}

$data = json_decode($resp, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Extract JSON from response
$text = trim($text);
// Remove markdown code blocks if present
$text = preg_replace('/^```(?:json)?s*/i', '', $text);
$text = preg_replace('/s*```$/', '', $text);
$text = trim($text);

$translated = json_decode($text, true);

if (!is_array($translated)) {
    echo json_encode(['translations' => [], 'error' => 'parse_error', 'raw' => $text]);
    exit;
}

// Build response: {itemId: {name: translatedName}}
$translations = [];
foreach ($translated as $id => $name) {
    $translations[(string)$id] = ['name' => $name];
}

echo json_encode(['translations' => $translations]);
?>
