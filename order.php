<?php
// UTF-8 ENCODING - MUST BE FIRST!
header('Content-Type: text/html; charset=UTF-8');

// Authentication required - order details should only be visible to staff
require_once 'auth.php';
requireAuth();

// Load database connection
require_once 'pdo.php';

$orderNo = isset($_GET['orderNo']) ? $_GET['orderNo'] : '';
if (!$orderNo) {
    echo "注文番号が指定されていません。";
    exit;
}

$sqlMgmt = "SELECT * FROM sManagement WHERE orderNo = :orderNo";
$stmtMgmt = $pdo->prepare($sqlMgmt);
$stmtMgmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
$stmtMgmt->execute();
$mgmt = $stmtMgmt->fetch(PDO::FETCH_ASSOC);

if (!$mgmt) {
    echo "注文が見つかりません。";
    exit;
}

$sqlDetail = "
    SELECT o.*, i.name, i.price
    FROM sOrder o
    JOIN sItem i ON o.itemNo = i.id
    WHERE o.orderNo = :orderNo
";
$stmtDetail = $pdo->prepare($sqlDetail);
$stmtDetail->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
$stmtDetail->execute();
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($details as $d) {
    $total += $d['price'] * $d['amount'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧾 注文詳細 - <?php echo htmlspecialchars($orderNo); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 40%, #14b8a6 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 1.5em;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 0.88em;
            color: rgba(255,255,255,0.75);
            margin-top: 2px;
        }

        .receipt-wrapper {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 28px;
            margin-bottom: 28px;
        }

        .receipt-title {
            font-size: 2em;
            font-weight: 800;
            color: #134e4a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .receipt-subtitle {
            color: #64748b;
            font-size: 0.95em;
        }

        .order-info-box {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 28px;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 0.8em;
            opacity: 0.85;
            font-weight: 500;
        }

        .info-value {
            font-size: 1.1em;
            font-weight: 700;
        }

        .items-section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 1.1em;
            font-weight: 700;
            color: #134e4a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead {
            background: #f0fdf9;
        }

        .items-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 700;
            color: #134e4a;
            border-bottom: 2px solid #99f6e4;
            font-size: 0.88em;
            letter-spacing: 0.02em;
        }

        .items-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #f0fdf9;
            font-size: 0.95em;
        }

        .items-table tbody tr:hover {
            background: #f0fdf9;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
        }

        .item-price, .item-qty {
            color: #64748b;
            text-align: right;
        }

        .item-subtotal {
            font-weight: 700;
            color: #0d9488;
            text-align: right;
        }

        .total-section {
            border-top: 2px dashed #e2e8f0;
            padding-top: 24px;
            margin-top: 8px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1em;
            color: #64748b;
        }

        .total-row.grand-total {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 16px;
            font-size: 1.4em;
            font-weight: 800;
            margin-top: 14px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 0.95em;
            font-weight: 700;
            font-family: inherit;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-back:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .btn-print {
            background: linear-gradient(135deg, #0f766e, #0d9488);
            color: white;
            box-shadow: 0 4px 12px rgba(15,118,110,0.35);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15,118,110,0.45);
        }

        .receipt-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px dashed #e2e8f0;
            color: #94a3b8;
            font-size: 0.9em;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions, .page-header {
                display: none;
            }
            .receipt-wrapper {
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .order-info-grid {
                grid-template-columns: 1fr;
            }
            .actions {
                flex-direction: column;
            }
            .items-table th:nth-child(2),
            .items-table td:nth-child(2) {
                display: none;
            }
            .receipt-wrapper {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">🧾 注文詳細</div>
            <div class="page-subtitle">Order Receipt</div>
        </div>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-header">
            <div class="receipt-title">🍽️ SmartOrder</div>
            <div class="receipt-subtitle">Order Receipt / 注文レシート</div>
        </div>

        <div class="order-info-box">
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="info-label">📋 注文番号</div>
                    <div class="info-value"><?php echo htmlspecialchars(substr($mgmt['orderNo'], -12)); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🍽️ テーブル番号</div>
                    <div class="info-value">Table <?php echo htmlspecialchars($mgmt['tableNo']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">📅 注文日時</div>
                    <div class="info-value"><?php echo date('Y/m/d H:i', strtotime($mgmt['dateA'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🔄 最終更新</div>
                    <div class="info-value"><?php echo date('Y/m/d H:i', strtotime($mgmt['dateB'])); ?></div>
                </div>
            </div>
        </div>

        <div class="items-section">
            <div class="section-title">
                <span>📝</span>
                <span>注文商品</span>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>商品名</th>
                        <th style="text-align: right;">単価</th>
                        <th style="text-align: right;">数量</th>
                        <th style="text-align: right;">小計</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                    <tr>
                        <td class="item-name"><?php echo htmlspecialchars($detail['name']); ?></td>
                        <td class="item-price">&yen;<?php echo number_format($detail['price']); ?></td>
                        <td class="item-qty">&times; <?php echo htmlspecialchars($detail['amount']); ?></td>
                        <td class="item-subtotal">&yen;<?php echo number_format($detail['price'] * $detail['amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>商品点数:</span>
                <span><?php echo count($details); ?>点</span>
            </div>
            <div class="total-row grand-total">
                <span>💰 合計金額</span>
                <span>&yen;<?php echo number_format($total); ?></span>
            </div>
        </div>

        <div class="actions">
            <a href="management.php" class="btn btn-back">&larr; 一覧に戻る</a>
            <button onclick="window.print()" class="btn btn-print">🖨️ 印刷する</button>
        </div>

        <div class="receipt-footer">
            <p>✨ ご注文ありがとうございました ✨</p>
        </div>
    </div>
</div>

</body>
</html>
