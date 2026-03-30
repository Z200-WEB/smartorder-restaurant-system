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

// Build detailed menu context from DB
$menuContext = '';
try {
    $stmt  = $pdo->query("
        SELECT i.itemName, i.price, i.description, i.tags, c.categoryName
        FROM sItem i
        LEFT JOIN sCategory c ON i.category = c.id
        WHERE i.available = 1
        ORDER BY c.id, i.id
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
            $lines[] = "【{$cat}】";
            foreach ($catItems as $it) {
                $line = "  - {$it['itemName']}（¥{$it['price']}）";
                if (!empty($it['description'])) {
                    $line .= " : {$it['description']}";
                }
                if (!empty($it['tags'])) {
                    $line .= " [タグ: {$it['tags']}]";
                }
                $lines[] = $line;
            }
        }
        $menuContext = implode("\n", $lines);
    } else {
        $menuContext = '（現在メニューデータがありません）';
    }
} catch (Exception $e) {
    $menuContext = '（メニュー取得エラー: ' . $e->getMessage() . '）';
}

// System prompt with full menu
$systemPrompt = <<<PROMPT
あなたは日本のレストラン「SmartOrder」のAIアシスタントです。テーブル{$tableNo}番のお客様を担当しています。

【現在のメニュー一覧】
{$menuContext}

【返答ルール】
- 必ず日本語で丁寧に答えてください。
- お客様がおすすめを聞いた場合は、上のメニューから具体的に2〜3品の名前と価格を挙げてください。
- 料理の説明を求められたら、メニュー情報を元に答えてください。
- ご注文はカートに追加ボタンを使うようお伝えください。
- アレルギーについては「スタッフにお伝えします」と答えてください。
- お水などのリクエストは「スタッフにお伝えします」と答えてください。
- 返答は2〜3文で簡潔に、でも具体的にお答えください。
PROMPT;

// Build Gemini contents array
$contents = [
    ['role' => 'user',  'parts' => [['text' => $systemPrompt]]],
    ['role' => 'model', 'parts' => [['text' => 'かしこまりました。テーブル' . $tableNo . '番を担当いたします。メニューを確認しました。何でもお気軽にどうぞ。']]],
];

// Add conversation history
foreach ($history as $msg) {
    if (isset($msg['role'], $msg['text'])) {
        $r          = ($msg['role'] === 'sent') ? 'user' : 'model';
        $contents[] = ['role' => $r, 'parts' => [['text' => $msg['text']]]];
    }
}

// Add current user message
$contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

// Call Gemini API
$url     = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-04-17:generateContent?key={$apiKey}";
$payload = json_encode([
    'contents'         => $contents,
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 300,
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST          => true,
    CURLOPT_POSTFIELDS    => $payload,
    CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
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
        $reply  = buildFallback($userMessage, $items ?? []);
        $source = 'fallback';
    }
} else {
    // Log error for debugging
    $debugInfo = "code={$code2}, curlErr={$err}, resp=" . substr((string)$resp, 0, 500);
    error_log("Gemini API error: " . $debugInfo);
    $reply  = buildFallback($userMessage, $items ?? []);
    $source = 'fallback_debug:' . $debugInfo;
}

// Save AI reply
try {
    $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'ai', ?, NOW())");
    $stmt->execute([$tableNo, $reply]);
} catch (Exception $e) {}

// Notify SSE listeners
$sseFile = sys_get_temp_dir() . '/smartorder_sse.json';
file_put_contents($sseFile, json_encode([
    'type'    => 'chat',
    'tableNo' => $tableNo,
    'message' => $userMessage,
    'reply'   => $reply,
    'ts'      => time(),
]));

echo json_encode(['reply' => $reply, 'source' => $source]);

// Fallback uses actual menu data
function buildFallback(string $text, array $items): string
{
    $l = mb_strtolower($text);

    if (mb_strpos($l, 'おすすめ') !== false || mb_strpos($l, 'おすすめ') !== false) {
        if (!empty($items)) {
            // Pick up to 3 random items
            shuffle($items);
            $picks = array_slice($items, 0, 3);
            $names = array_map(fn($i) => "{$i['itemName']}（¥{$i['price']}）", $picks);
            return 'おすすめは ' . implode('、', $names) . ' などがございます。ぜひお試しください！';
        }
        return 'メニューをご覧の上、お好きな一品をどうぞ！';
    }

    if (mb_strpos($l, 'アレルギ') !== false) {
        return 'アレルギー情報はスタッフへお伝えします。このまま内容を送ってください。';
    }

    if (mb_strpos($l, '水') !== false) {
        return 'お水をスタッフにお伝えします。少々お待ちください。';
    }

    return 'ありがとうございます。スタッフが確認してすぐ対応いたします。';
}
?>
