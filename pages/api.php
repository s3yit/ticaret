<?php
require_once '../config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_people') {
        $stmt = $db->query('SELECT ID, NAME, TEL_NO FROM KISILER ORDER BY NAME');
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($people);
    } elseif ($action === 'get_products') {
        $stmt = $db->query('SELECT ID, NAME FROM URUN ORDER BY NAME');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } elseif ($action === 'add_person') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['NAME'] ?? '');
        $tel = isset($input['TEL_NO']) ? trim($input['TEL_NO']) : null;

        if (empty($name)) {
            error_log('add_person: Missing NAME');
            echo json_encode(['success' => false, 'message' => 'Kişi adı zorunludur']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO KISILER (NAME, TEL_NO) VALUES (:name, :tel)');
        $stmt->execute(['name' => $name, 'tel' => $tel]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'save_transaction') {
        $input = json_decode(file_get_contents('php://input'), true);
        $transactions = $input['transactions'] ?? [];

        if (empty($transactions)) {
            error_log('save_transaction: No transactions provided');
            echo json_encode(['success' => false, 'message' => 'En az bir ürün gerekli']);
            exit;
        }

        foreach ($transactions as $index => $transaction) {
            $required_fields = [
                'TEDARIKCI_ID' => 'Tedarikçi ID',
                'ALICI_ID' => 'Alıcı ID',
                'URUN_ID' => 'Ürün ID',
                'PLAKA' => 'Tır Plakası',
                'FIRESIZ_TON' => 'Brüt Tonaj',
                'FIRELI_TON' => 'Net Tonaj',
                'BIRIM_FIYATI' => 'Birim Fiyat',
                'TARIH' => 'Teslimat Tarihi'
            ];

            foreach ($required_fields as $field => $label) {
                if (!isset($transaction[$field]) || $transaction[$field] === '' || $transaction[$field] === null) {
                    error_log("save_transaction: Missing or empty field '$field' in transaction $index");
                    echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": $label eksik"]);
                    exit;
                }
            }

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
            if (!is_numeric($transaction['BIRIM_FIYATI']) || $transaction['BIRIM_FIYATI'] <= 0) {
                error_log("save_transaction: Invalid BIRIM_FIYATI in transaction $index: " . $transaction['BIRIM_FIYATI']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Birim Fiyat geçersiz"]);
                exit;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $transaction['TARIH'])) {
                error_log("save_transaction: Invalid TARIH in transaction $index: " . $transaction['TARIH']);
                echo json_encode(['success' => false, 'message' => "Ürün " . ($index + 1) . ": Teslimat Tarihi geçersiz"]);
                exit;
            }
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO ISLEM (
                    TEDARIKCI_ID, ALICI_ID, URUN_ID, PLAKA, FIRESIZ_TON, FIRELI_TON, BIRIM_FIYATI, TARIH, ACIKLAMA
                ) VALUES (
                    :tedarikci_id, :alici_id, :urun_id, :plaka, :firesiz_ton, :fireli_ton, :birim_fiyati, :tarih, :aciklama
                )
            ');
            foreach ($transactions as $index => $transaction) {
                $stmt->execute([
                    'tedarikci_id' => $transaction['TEDARIKCI_ID'],
                    'alici_id' => $transaction['ALICI_ID'],
                    'urun_id' => $transaction['URUN_ID'],
                    'plaka' => $transaction['PLAKA'],
                    'firesiz_ton' => $transaction['FIRESIZ_TON'],
                    'fireli_ton' => $transaction['FIRELI_TON'],
                    'birim_fiyati' => $transaction['BIRIM_FIYATI'],
                    'tarih' => $transaction['TARIH'],
                    'aciklama' => $transaction['ACIKLAMA'] ?? null
                ]);
                error_log("save_transaction: Inserted transaction $index for URUN_ID: {$transaction['URUN_ID']}, PLAKA: {$transaction['PLAKA']}");
            }

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log('save_transaction: Database error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Veritabanına kaydedilirken hata: ' . $e->getMessage()]);
        }
    } elseif ($action === 'get_supplier_transactions') {
        $person_id = $_GET['person_id'] ?? null;
        if (!$person_id || !is_numeric($person_id)) {
            error_log('get_supplier_transactions: Invalid or missing person_id');
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }

        $stmt = $db->prepare('
            SELECT 
                i.ID, i.TARIH, i.PLAKA, k2.NAME AS BUYER_NAME, u.NAME AS URUN_ADI, 
                i.FIRESIZ_TON, i.FIRELI_TON, i.BIRIM_FIYATI, 
                (i.FIRELI_TON * i.BIRIM_FIYATI) AS TOTAL_FIYAT
            FROM ISLEM i
            JOIN KISILER k1 ON i.TEDARIKCI_ID = k1.ID
            JOIN KISILER k2 ON i.ALICI_ID = k2.ID
            JOIN URUN u ON i.URUN_ID = u.ID
            WHERE i.TEDARIKCI_ID = :person_id
            ORDER BY i.TARIH DESC
        ');
        $stmt->execute(['person_id' => $person_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'transactions' => $transactions]);
    } elseif ($action === 'get_buyer_transactions') {
        $person_id = $_GET['person_id'] ?? null;
        if (!$person_id || !is_numeric($person_id)) {
            error_log('get_buyer_transactions: Invalid or missing person_id');
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }

        $stmt = $db->prepare('
            SELECT 
                i.ID, i.TARIH, i.PLAKA, k1.NAME AS SUPPLIER_NAME, u.NAME AS URUN_ADI, 
                i.FIRESIZ_TON, i.FIRELI_TON, i.BIRIM_FIYATI, 
                (i.FIRELI_TON * i.BIRIM_FIYATI) AS TOTAL_FIYAT
            FROM ISLEM i
            JOIN KISILER k1 ON i.TEDARIKCI_ID = k1.ID
            JOIN KISILER k2 ON i.ALICI_ID = k2.ID
            JOIN URUN u ON i.URUN_ID = u.ID
            WHERE i.ALICI_ID = :person_id
            ORDER BY i.TARIH DESC
        ');
        $stmt->execute(['person_id' => $person_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'transactions' => $transactions]);
    } elseif ($action === 'get_transaction_detail') {
        $transaction_id = $_GET['transaction_id'] ?? null;
        if (!$transaction_id || !is_numeric($transaction_id)) {
            error_log('get_transaction_detail: Invalid or missing transaction_id');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem ID']);
            exit;
        }

        $stmt = $db->prepare('
            SELECT 
                i.ID, i.TARIH, i.PLAKA, k1.NAME AS SUPPLIER_NAME, k2.NAME AS BUYER_NAME, 
                u.NAME AS URUN_ADI, i.FIRESIZ_TON, i.FIRELI_TON, i.BIRIM_FIYATI, 
                i.ACIKLAMA
            FROM ISLEM i
            JOIN KISILER k1 ON i.TEDARIKCI_ID = k1.ID
            JOIN KISILER k2 ON i.ALICI_ID = k2.ID
            JOIN URUN u ON i.URUN_ID = u.ID
            WHERE i.ID = :transaction_id
        ');
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($transaction) {
            echo json_encode(['success' => true, 'transaction' => $transaction]);
        } else {
            error_log('get_transaction_detail: Transaction not found for ID ' . $transaction_id);
            echo json_encode(['success' => false, 'message' => 'İşlem bulunamadı']);
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