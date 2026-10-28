<?php
// table_assign.php - Give each visitor without ?tableNo its own table number.
// Used for user testing: everyone scans the same QR code, but each phone gets
// a separate table (101, 102, ...) so carts never mix.
// Real store tables (1-5) are unaffected: their QR codes already include ?tableNo.
if (!isset($_GET['tableNo']) || (int)$_GET['tableNo'] <= 0) {
    $assigned = 0;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sTableSeq (id INT AUTO_INCREMENT PRIMARY KEY, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) AUTO_INCREMENT=101");
        $pdo->exec("INSERT INTO sTableSeq () VALUES ()");
        $assigned = (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        $assigned = random_int(101, 9999); // fallback: still very unlikely to collide
    }
    $path  = strtok($_SERVER['REQUEST_URI'], '?');
    $query = $_GET;
    $query['tableNo'] = $assigned;
    header('Location: ' . $path . '?' . http_build_query($query), true, 302);
    exit;
}
