<?php
require_once '../config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    // Fetch all people
    if ($action === 'get_people') {
        $stmt = $db->query('SELECT ID, NAME, TEL_NO FROM KISILER ORDER BY NAME');
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($people);
    }
    // Fetch all products
    elseif ($action === 'get_products') {
        $stmt = $db->query('SELECT ID, NAME FROM URUN ORDER BY NAME');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    }
    // Add a new person
    elseif ($action === 'add_person') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['NAME'] ?? '');
        $tel = isset($input['TEL_NO']) ? trim($input['TEL_NO']) : null;

        if (empty($name)) {
            error_log("add_person: Empty name provided");
            echo json_encode(['success' => false, 'message' => 'Kişi adı zorunludur']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO KISILER (NAME, TEL_NO) VALUES (:name, :tel)');
        $stmt->execute(['name' => $name, 'tel' => $tel]);
        error_log("add_person: Added person '$name' with ID " . $db->lastInsertId());
        echo json_encode(['success' => true]);
    }
    // Fetch transactions for a person
    elseif ($action === 'get_transactions') {
        $person_id = $_GET['person_id'] ?? null;
        if (!$person_id || !is_numeric($person_id)) {
            error_log("get_transactions: Invalid or missing person_id: $person_id");
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }

        $stmt = $db->prepare('
            SELECT i.*, u.NAME AS URUN_ADI
            FROM ISLEM i
            JOIN URUN u ON i.URUN_ID = u.ID
            WHERE i.TEDARIKCI_ID = :person_id OR i.ALICI_ID = :person_id
            ORDER BY i.TARIH DESC
        ');
        $stmt->execute(['person_id' => $person_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($transactions);
    }
    // Save transactions
    elseif ($action === 'save_transaction') {
        $input = json_decode(file_get_contents('php://input'), true);
        $transactions = $input['transactions'] ?? [];

        if (empty($transactions)) {
            error_log('save_transaction: No transactions provided');
            echo json_encode(['success' => false, 'message' => 'En az bir ürün gerekli']);
            exit;
        }

        // Validate shared fields (checked on first transaction)
        $shared_fields = [
            'TEDARIKCI_ID' => 'Tedarikçi ID',
            'ALICI_ID' => 'Alıcı ID',
            'PLAKA' => 'Tır Plakası',
            'TARIH' => 'Teslimat Tarihi'
        ];
        $first_transaction = $transactions[0];
        foreach ($shared_fields as $field => $label) {
            if (!isset($first_transaction[$field]) || $first_transaction[$field] === '' || $first_transaction[$field] === null) {
                error_log("save_transaction: Missing or empty shared field '$field' in transaction 0");
                echo json_encode(['success' => false, 'message' => "$label eksik"]);
                exit;
            }
        }

        // Validate all transactions
        foreach ($transactions as $index => $transaction) {
            // Check consistency of shared fields
            foreach ($shared_fields as $field => $label) {
                if ($transaction[$field] !== $first_transaction[$field]) {
                    error_log("save_transaction: Inconsistent $field in transaction $index: expected {$first_transaction[$field]}, got {$transaction[$field]}");
                    echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": $label tutarsız"]);
                    exit;
                }
            }

            // Validate product-specific fields
            $product_fields = [
                'URUN_ID' => 'Ürün ID',
                'FIRESIZ_TON' => 'Brüt Tonaj',
                'FIRELI_TON' => 'Net Tonaj',
                'ALIS_FIYATI' => 'Alış Fiyatı',
                'SATIS_FIYATI' => 'Satış Fiyatı'
            ];
            foreach ($product_fields as $field => $label) {
                if (!isset($transaction[$field]) || $transaction[$field] === '' || $transaction[$field] === null) {
                    error_log("save_transaction: Missing or empty field '$field' in transaction $index");
                    echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": $label eksik"]);
                    exit;
                }
            }

            // Validate numeric fields
            if (!is_numeric($transaction['FIRESIZ_TON']) || $transaction['FIRESIZ_TON'] <= 0) {
                error_log("save_transaction: Invalid FIRESIZ_TON in transaction $index: " . $transaction['FIRESIZ_TON']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Brüt Tonaj geçersiz"]);
                exit;
            }
            if (!is_numeric($transaction['FIRELI_TON']) || $transaction['FIRELI_TON'] <= 0) {
                error_log("save_transaction: Invalid FIRELI_TON in transaction $index: " . $transaction['FIRELI_TON']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Net Tonaj geçersiz"]);
                exit;
            }
            if (!is_numeric($transaction['ALIS_FIYATI']) || $transaction['ALIS_FIYATI'] < 0) {
                error_log("save_transaction: Invalid ALIS_FIYATI in transaction $index: " . $transaction['ALIS_FIYATI']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Alış Fiyatı geçersiz"]);
                exit;
            }
            if (!is_numeric($transaction['SATIS_FIYATI']) || $transaction['SATIS_FIYATI'] < 0) {
                error_log("save_transaction: Invalid SATIS_FIYATI in transaction $index: " . $transaction['SATIS_FIYATI']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Satış Fiyatı geçersiz"]);
                exit;
            }
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO ISLEM (
                    TEDARIKCI_ID, ALICI_ID, URUN_ID, PLAKA, FIRESIZ_TON, FIRELI_TON, 
                    ALIS_FIYATI, SATIS_FIYATI, KAR, TARIH, ACIKLAMA
                ) VALUES (
                    :tedarikci_id, :alici_id, :urun_id, :plaka, :firesiz_ton, :fireli_ton, 
                    :alis_fiyati, :satis_fiyati, :kar, :tarih, :aciklama
                )
            ');
            foreach ($transactions as $index => $transaction) {
                $kar = $transaction['SATIS_FIYATI'] - $transaction['ALIS_FIYATI'];
                $stmt->execute([
                    'tedarikci_id' => $transaction['TEDARIKCI_ID'],
                    'alici_id' => $transaction['ALICI_ID'],
                    'urun_id' => $transaction['URUN_ID'],
                    'plaka' => $transaction['PLAKA'],
                    'firesiz_ton' => $transaction['FIRESIZ_TON'],
                    'fireli_ton' => $transaction['FIRELI_TON'],
                    'alis_fiyati' => $transaction['ALIS_FIYATI'],
                    'satis_fiyati' => $transaction['SATIS_FIYATI'],
                    'kar' => $kar,
                    'tarih' => $transaction['TARIH'],
                    'aciklama' => $transaction['ACIKLAMA'] ?? null
                ]);
                error_log("save_transaction: Inserted transaction $index for URUN_ID: {$transaction['URUN_ID']}, PLAKA: {$transaction['PLAKA']}, TARIH: {$transaction['TARIH']}, KAR: $kar");
            }

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log('save_transaction: Database error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Veritabanına kaydedilirken hata: ' . $e->getMessage()]);
        }
    } else {
        error_log("Invalid action: $action");
        echo json_encode(['success' => false, 'message' => 'Geçersiz eylem']);
    }
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?>