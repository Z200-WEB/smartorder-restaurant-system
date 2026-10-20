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
$lang        = $input['lang'] ?? 'ja'; // ja / en / zh / my

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
        SELECT i.id, i.name AS itemName, i.price, i.description,
               i.is_popular, i.is_new, i.is_spicy, i.allergens,
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
                $name = trim(preg_replace('/\s*\[[^\]]*\]/u', '', $it['itemName']));
                $line = "  - ID {$it['id']} | {$name} | {$it['price']} yen";
                if (!empty($it['description'])) $line .= " | {$it['description']}";
                $line .= ' | Allergens: ' . (($it['allergens'] ?? '') !== '' ? $it['allergens'] : 'UNKNOWN');
                $badges = [];
                if ($it['is_popular']) $badges[] = 'popular';
                if ($it['is_new'])     $badges[] = 'limited-time';
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
// Always answer in the language of the customer's latest message.
// The screen language ($lang) is only a fallback when the language is unclear.
$screenLang = ['ja' => 'Japanese', 'en' => 'English', 'zh' => 'Chinese (Simplified)', 'my' => 'Myanmar (Burmese)'][$lang] ?? 'Japanese';
$langInstruction = "Reply in the SAME language as the customer's latest message (Japanese -> Japanese, English -> English, Chinese -> Chinese, Myanmar -> Myanmar, Korean -> Korean, etc.). "
    . "The menu screen is shown in {$screenLang}; only use {$screenLang} if the customer's language is unclear. "
    . "Menu item names are written in Myanmar script on the screen: when you mention a dish, write its name exactly as it appears in the menu (Myanmar script) followed by a short translation/explanation in the customer's language, so they can find it on the screen.";

$systemPrompt = <<<PROMPT
You are an AI assistant for restaurant "SmartOrder", serving table {$tableNo}.

[LANGUAGE RULE]
{$langInstruction}

[TODAY'S MENU]
{$menuContext}

[RESPONSE RULES]
- This is a Myanmar restaurant. Many customers are foreign tourists who cannot read Myanmar script, so explain dishes simply.
- When asked for recommendations, name 2-3 specific items with prices from the menu above.
- For item descriptions, use only the menu info provided.
- ALLERGIES: answer ONLY from the "Allergens" field of each item. Never guess. An item is safe for an allergy only if that allergen is not in its Allergens list. If Allergens is UNKNOWN, say you are not sure and the customer should ask the staff. Add a short note that the staff can double-check.
- Every time you recommend or mention a dish the customer could order, put the tag [[add:ID]] right after the dish name (ID = the item's ID number from the menu). The screen turns these tags into "add to cart" buttons. Never invent IDs.
- For water or service requests, say you will inform the staff.
- Keep responses concise: 2-4 short sentences.
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
    // answer in the language the customer typed
    if (preg_match('/[\x{3040}-\x{30FF}]/u', $text)) $lang = 'ja';
    elseif (preg_match('/[\x{1000}-\x{109F}]/u', $text)) $lang = 'my';
    elseif (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) $lang = 'zh';
    elseif (preg_match('/[a-zA-Z]/', $text)) $lang = 'en';
    $l = mb_strtolower($text);
    $isRec = mb_strpos($l, 'recommend') !== false || mb_strpos($l, 'suggest') !== false
          || mb_strpos($l, 'おすすめ') !== false || mb_strpos($l, '推荐') !== false || mb_strpos($l, 'အကြံပြု') !== false;

    if ($isRec) {
        if (!empty($items)) {
            $popular = array_values(array_filter($items, fn($i) => $i['is_popular']));
            $pool    = !empty($popular) ? $popular : $items;
            shuffle($pool);
            $picks = array_slice($pool, 0, 3);
            $names = array_map(fn($i) => trim(preg_replace('/\s*\[[^\]]*\]/u', '', $i['itemName'])) . " ({$i['price']} yen) [[add:{$i['id']}]]", $picks);
            $list  = implode(', ', $names);
            return match($lang) {
                'en' => "We recommend: {$list}. Please enjoy!",
                'zh' => "我们推荐：{$list}。请享用！",
                'my' => "အကြံပြုချင်တာကတော့ {$list} ဖြစ်ပါတယ်။ အရသာရှိရှိ သုံးဆောင်ပါ!",
                default => "おすすめは {$list} などがございます。ぜひお試しください！",
            };
        }
    }
    return match($lang) {
        'en' => 'Thank you. Our staff will assist you shortly.',
        'zh' => '谢谢您。工作人员将很快为您服务。',
        'my' => 'ကျေးဇူးတင်ပါတယ်။ ဝန်ထမ်းက မကြာမီ ကူညီပေးပါမယ်။',
        default => 'ありがとうございます。スタッフが確認してすぐ対応いたします。',
    };
}
?>
