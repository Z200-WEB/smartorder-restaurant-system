<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');
require_once 'pdo.php';

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'no key']);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$tableNo     = intval($input['tableNo'] ?? 1);
$history     = $input['history'] ?? [];
$lang        = $input['lang'] ?? 'ja'; // ja / en / zh

if (!$userMessage) {
    http_response_code(400);
    echo json_encode(['error' => 'no msg']);
    exit;
}

// Save user message to DB
try {
    $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'user', ?, NOW())");
    $stmt->execute([$tableNo, $userMessage]);
} catch (Exception $e) {}

// Build menu context using CORRECT column names
$menuContext = '';
$items = [];
try {
    $stmt = $pdo->query("
        SELECT i.name AS itemName, i.price, i.description,
               i.is_popular, i.is_new, i.is_spicy,
               c.categoryName
        FROM sItem i
        LEFT JOIN sCategory c ON i.category = c.id
        WHERE i.state = 1
        ORDER BY c.sort_order, i.sort_order
        LIMIT 80
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        $byCategory = [];
        foreach ($items as $it) {
            $cat = $it['categoryName'] ?: 'その他';
            $byCategory[$cat][] = $it;
        }
        $lines = [];
        foreach ($byCategory as $cat => $catItems) {
            $lines[] = "[{$cat}]";
            foreach ($catItems as $it) {
                $line = "  - {$it['itemName']} ({$it['price']}yen)";
                if (!empty($it['description'])) $line .= ": {$it['description']}";
                $badges = [];
                if ($it['is_popular']) $badges[] = 'popular';
                if ($it['is_new'])     $badges[] = 'new';
                if ($it['is_spicy'])   $badges[] = 'spicy';
                if ($badges) $line .= ' [' . implode('/', $badges) . ']';
                $lines[] = $line;
            }
        }
        $menuContext = implode("\n", $lines);
    } else {
        $menuContext = '(No menu data available)';
    }
} catch (Exception $e) {
    $menuContext = '(Menu error: ' . $e->getMessage() . ')';
}

// Language-specific instructions
$langInstructions = [
    'ja' => 'Detect the language the customer is using and reply in the SAME language. If they write in Japanese, reply in Japanese. If English, reply in English. If Chinese, reply in Chinese.',
    'en' => 'The customer prefers English. Reply in English. But if the customer writes in another language, match their language.',
    'zh' => 'The customer prefers Chinese (Simplified). Reply in Chinese. But if the customer writes in another language, match their language.',
];
$langInstruction = $langInstructions[$lang] ?? $langInstructions['ja'];

$systemPrompt = <<<PROMPT
You are an AI assistant for restaurant "SmartOrder", serving table {$tableNo}.

[LANGUAGE RULE]
{$langInstruction}

[TODAY'S MENU]
{$menuContext}

[RESPONSE RULES]
- When asked for recommendations, name 2-3 specific items with prices from the menu above.
- For item descriptions, use the menu info provided.
- For ordering, tell customers to use the "Add to Cart" button.
- For allergies, say you will inform the staff.
- For water or service requests, say you will inform the staff.
- Keep responses concise: 2-3 sentences.
- Never recommend items not on the menu above.
PROMPT;

$contents = [
    ['role' => 'user',  'parts' => [['text' => $systemPrompt]]],
    ['role' => 'model', 'parts' => [['text' => 'Understood. I am ready to assist at table ' . $tableNo . '. I have reviewed the menu and will respond in the customer\'s language.']]],
];

foreach ($history as $msg) {
    if (isset($msg['role'], $msg['text'])) {
        $r          = ($msg['role'] === 'sent') ? 'user' : 'model';
        $contents[] = ['role' => $r, 'parts' => [['text' => $msg['text']]]];
    }
}

$contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

$url     = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
$payload = json_encode([
    'contents'         => $contents,
    'generationConfig' => [
        'temperature'    => 0.7,
        'maxOutputTokens'=> 500,
        'thinkingConfig' => ['thinkingBudget' => 0],
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST          => true,
    CURLOPT_POSTFIELDS    => $payload,
    CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER=> true,
    CURLOPT_TIMEOUT       => 15,
]);
$resp  = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err   = curl_error($ch);
curl_close($ch);

$source = 'gemini';
if ($resp && $code2 === 200) {
    $data  = json_decode($resp, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        $reply  = buildFallback($userMessage, $items, $lang);
        $source = 'fallback';
    }
} else {
    error_log("Gemini API error: code={$code2}, curlErr={$err}, resp=" . substr((string)$resp, 0, 500));
    $reply  = buildFallback($userMessage, $items, $lang);
    $source = 'fallback';
}

try {
    $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'ai', ?, NOW())");
    $stmt->execute([$tableNo, $reply]);
} catch (Exception $e) {}

$sseFile = sys_get_temp_dir() . '/smartorder_sse.json';
file_put_contents($sseFile, json_encode([
    'type'    => 'chat',
    'tableNo' => $tableNo,
    'message' => $userMessage,
    'reply'   => $reply,
    'ts'      => time(),
]));

echo json_encode(['reply' => $reply, 'source' => $source]);

function buildFallback(string $text, array $items, string $lang): string
{
    $l = mb_strtolower($text);
    $isRec = mb_strpos($l, 'recommend') !== false || mb_strpos($l, 'suggest') !== false
          || mb_strpos($l, 'おすすめ') !== false || mb_strpos($l, '推荐') !== false;

    if ($isRec) {
        if (!empty($items)) {
            $popular = array_values(array_filter($items, fn($i) => $i['is_popular']));
            $pool    = !empty($popular) ? $popular : $items;
            shuffle($pool);
            $picks = array_slice($pool, 0, 3);
            $names = array_map(fn($i) => "{$i['itemName']} ({$i['price']} yen)", $picks);
            $list  = implode(', ', $names);
            return match($lang) {
                'en' => "We recommend: {$list}. Please enjoy!",
                'zh' => "我们推荐：{$list}。请享用！",
                default => "おすすめは {$list} などがございます。ぜひお試しください！",
            };
        }
    }
    return match($lang) {
        'en' => 'Thank you. Our staff will assist you shortly.',
        'zh' => '谢谢您。工作人员将很快为您服务。',
        default => 'ありがとうございます。スタッフが確認してすぐ対応いたします。',
    };
}
?>
