<?php
// Myanmar display names for staff screens (kitchen, order detail, admin)
// Items/categories not in the maps keep their stored name.
function myItemName($id, string $storedName, bool $stripTags = true): string {
    static $map = null;
    if ($map === null) $map = require __DIR__ . '/menu_my.php';
    $name = $map[(int)$id] ?? $storedName;
    if ($stripTags) $name = trim(preg_replace('/\s*\[[^\]]*\]/u', '', $name));
    return $name;
}
function myCategoryName(string $storedName): string {
    static $cats = [
        'とりあえず注文' => 'အစပြုဟင်းလျာ',
        'お寿司' => 'ဆူရှီ',
        '焼き物' => 'အကင်',
        '揚げ物' => 'အကြော်',
        'ドリンク' => 'သောက်စရာ',
        'デザート' => 'အချိုပွဲ',
    ];
    return $cats[$storedName] ?? $storedName;
}
