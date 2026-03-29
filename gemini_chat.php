<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');
require_once 'pdo.php';

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) { http_response_code(500); echo json_encode(['error'=>'no key']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$tableNo = intval($input['tableNo'] ?? 1);
$history = $input['history'] ?? [];

if (!$userMessage) { http_response_code(400); echo json_encode(['error'=>'no msg']); exit; }

// Save user message to DB
try {
    $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'user', ?, NOW())");
    $stmt->execute([$tableNo, $userMessage]);
} catch(Exception $e) {}

// Build menu context
$menuContext = '';
try {
    $stmt = $pdo->query("SELECT i.itemName, i.price, c.categoryName, i.tags FROM sItem i LEFT JOIN sCategory c ON i.category = c.id WHERE i.available = 1 ORDER BY i.id LIMIT 50");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lines = array_map(fn($it) => "- {$it['itemName']}(¥{$it['price']})[{$it['categoryName']}]", $items);
    $menuContext = implode("\n", $lines);
} catch(Exception $e) { $menuContext = '(error)'; }

// Build Gemini request
$systemPrompt = "あなたは日本のレストランSmartOrderのAIアシスタントです。テーブル{$tableNo}のお客様担当。\n現在のメニュー:\n{$menuContext}\n\n必ず日本語で。丁寧に。メニューの質問は具体的に案内。アレルギーは「スタッフに伝えます」。注文は「カートに追加ボタンを使ってください」。2-3文で簡潔に。";

$contents = [
    ['role'=>'user','parts'=>[['text'=>$systemPrompt]]],
    ['role'=>'model','parts'=>[['text'=>'かしこまりました。テーブル'.$tableNo.'担当します。']]]
];

foreach ($history as $msg) {
    if (isset($msg['role'], $msg['text'])) {
        $r = ($msg['role']==='sent') ? 'user' : 'model';
        $contents[] = ['role'=>$r,'parts'=>[['text'=>$msg['text']]]];
    }
}
$contents[] = ['role'=>'user','parts'=>[['text'=>$userMessage]]];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
$payload = json_encode(['contents'=>$contents,'generationConfig'=>['temperature'=>0.7,'maxOutputTokens'=>200]]);

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
$resp = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp && $code2 === 200) {
    $data = json_decode($resp, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? fallback($userMessage);
} else {
    $reply = fallback($userMessage);
}

// Save AI reply
try {
    $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'ai', ?, NOW())");
    $stmt->execute([$tableNo, $reply]);
} catch(Exception $e) {}

// Notify SSE listeners (write to tmp file)
$sseFile = sys_get_temp_dir().'/smartorder_sse.json';
$sseData = ['type'=>'chat','tableNo'=>$tableNo,'message'=>$userMessage,'reply'=>$reply,'ts'=>time()];
file_put_contents($sseFile, json_encode($sseData));

echo json_encode(['reply'=>$reply,'source'=>'gemini']);

function fallback($t) {
    $l = mb_strtolower($t);
    if (mb_strpos($l,'おすすめ')!==false) return 'メニューからお好きな一品をどうぞ。おすすめの欄も参考にしてみてください！';
    if (mb_strpos($l,'アレルギ')!==false) return 'アレルギー情報はスタッフへお伝えします。このまま内容を送ってください。';
    if (mb_strpos($l,'水')!==false) return 'お水をすぐにお持ちします。少々お待ちください。';
    return 'ありがとうございます。スタッフが確認してすぐ対応いたします。';
}
?>
