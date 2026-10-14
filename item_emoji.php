<?php
// Placeholder emoji for items without a photo: keyword match, then category icon
function itemEmoji($name, $catId, $catIcons) {
    $map = ['カレー'=>'🍛','おにく'=>'🍖','ご飯'=>'🍚','寿司'=>'🍣','巻き'=>'🍣','枝豆'=>'🫛','焼き鳥'=>'🍢','炭火焼'=>'🍖',
            '唐揚げ'=>'🍗','チキン'=>'🍗','ポテト'=>'🍟','飲み放題'=>'🍻','ビール'=>'🍺','サワー'=>'🍋','ハイボール'=>'🥃',
            'ワッフル'=>'🧇','パンケーキ'=>'🥞','アイス'=>'🍨','マカロン'=>'🍨','ケーキ'=>'🍰','デザート'=>'🍰'];
    foreach ($map as $k => $emoji) { if (mb_strpos($name, $k) !== false) return $emoji; }
    return $catIcons[(int)$catId] ?? '🍽️';
}
