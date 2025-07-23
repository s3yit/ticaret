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
    } elseif ($action === 'add_alacak') {
        $input = json_decode(file_get_contents('php://input'), true);
        $kisi_id = $input['KISI_ID'] ?? null;
        $ucret = $input['ÜCRET'] ?? null;
        $tarih = $input['TARIH'] ?? null;
        $aciklama = trim($input['ACIKLAMA'] ?? '');

        if (!$kisi_id || !is_numeric($kisi_id)) {
            error_log('add_alacak: Invalid or missing KISI_ID');
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }
        if (!is_numeric($ucret) || $ucret <= 0) {
            error_log('add_alacak: Invalid or missing ÜCRET');
            echo json_encode(['success' => false, 'message' => 'Geçersiz tutar']);
            exit;
        }
        if (!$aciklama) {
            error_log('add_alacak: Missing ACIKLAMA');
            echo json_encode(['success' => false, 'message' => 'Açıklama zorunludur']);
            exit;
        }
        if (!$tarih || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            error_log('add_alacak: Invalid or missing TARIH');
            echo json_encode(['success' => false, 'message' => 'Geçersiz tarih']);
            exit;
        }

        $stmt = $db->prepare('
            INSERT INTO ALACAK_VERECEK (KISI_ID, ALACAK_VERECEK_TYPE_ID, ÜCRET, ACIKLAMA, TARIH, ODENDI_MI)
            VALUES (:kisi_id, 1, :ucret, :aciklama, :tarih, 0)
        ');
        $stmt->execute([
            'kisi_id' => $kisi_id,
            'ucret' => $ucret,
            'aciklama' => $aciklama,
            'tarih' => $tarih
        ]);
        error_log("add_alacak: Inserted for KISI_ID: $kisi_id, ÜCRET: $ucret");
        echo json_encode(['success' => true]);
    } elseif ($action === 'add_verecek') {
        $input = json_decode(file_get_contents('php://input'), true);
        $kisi_id = $input['KISI_ID'] ?? null;
        $ucret = $input['ÜCRET'] ?? null;
        $tarih = $input['TARIH'] ?? null;
        $aciklama = trim($input['ACIKLAMA'] ?? '');

        if (!$kisi_id || !is_numeric($kisi_id)) {
            error_log('add_verecek: Invalid or missing KISI_ID');
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }
        if (!is_numeric($ucret) || $ucret <= 0) {
            error_log('add_verecek: Invalid or missing ÜCRET');
            echo json_encode(['success' => false, 'message' => 'Geçersiz tutar']);
            exit;
        }
        if (!$aciklama) {
            error_log('add_verecek: Missing ACIKLAMA');
            echo json_encode(['success' => false, 'message' => 'Açıklama zorunludur']);
            exit;
        }
        if (!$tarih || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            error_log('add_verecek: Invalid or missing TARIH');
            echo json_encode(['success' => false, 'message' => 'Geçersiz tarih']);
            exit;
        }

        $stmt = $db->prepare('
            INSERT INTO ALACAK_VERECEK (KISI_ID, ALACAK_VERECEK_TYPE_ID, ÜCRET, ACIKLAMA, TARIH, ODENDI_MI)
            VALUES (:kisi_id, 2, :ucret, :aciklama, :tarih, 0)
        ');
        $stmt->execute([
            'kisi_id' => $kisi_id,
            'ucret' => $ucret,
            'aciklama' => $aciklama,
            'tarih' => $tarih
        ]);
        error_log("add_verecek: Inserted for KISI_ID: $kisi_id, ÜCRET: $ucret");
        echo json_encode(['success' => true]);
    } elseif ($action === 'get_alacak') {
        $stmt = $db->query('
            SELECT av.ID, k.NAME, av.ÜCRET, av.ACIKLAMA, av.TARIH, av.KISI_ID
            FROM ALACAK_VERECEK av
            JOIN KISILER k ON av.KISI_ID = k.ID
            WHERE av.ALACAK_VERECEK_TYPE_ID = 1 AND av.ODENDI_MI = 0
            ORDER BY av.TARIH DESC
        ');
        $alacak = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'alacak' => $alacak]);
    } elseif ($action === 'get_verecek') {
        $stmt = $db->query('
            SELECT av.ID, k.NAME, av.ÜCRET, av.ACIKLAMA, av.TARIH, av.KISI_ID
            FROM ALACAK_VERECEK av
            JOIN KISILER k ON av.KISI_ID = k.ID
            WHERE av.ALACAK_VERECEK_TYPE_ID = 2 AND av.ODENDI_MI = 0
            ORDER BY av.TARIH DESC
        ');
        $verecek = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'verecek' => $verecek]);
    } elseif ($action === 'make_payment') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['ID'] ?? null;
        $kisi_id = $input['KISI_ID'] ?? null;
        $payment_amount = $input['PAYMENT_AMOUNT'] ?? null;
        $original_amount = $input['ORIGINAL_AMOUNT'] ?? null;
        $aciklama = trim($input['ACIKLAMA'] ?? '');
        $type = $input['TYPE'] ?? null;

        if (!$id || !is_numeric($id)) {
            error_log('make_payment: Invalid or missing ID');
            echo json_encode(['success' => false, 'message' => 'Geçersiz alacak/verecek ID']);
            exit;
        }
        if (!$kisi_id || !is_numeric($kisi_id)) {
            error_log('make_payment: Invalid or missing KISI_ID');
            echo json_encode(['success' => false, 'message' => 'Geçersiz kişi ID']);
            exit;
        }
        if (!is_numeric($payment_amount) || $payment_amount <= 0) {
            error_log('make_payment: Invalid or missing PAYMENT_AMOUNT');
            echo json_encode(['success' => false, 'message' => 'Geçersiz ödeme tutarı']);
            exit;
        }
        if (!is_numeric($original_amount) || $original_amount <= 0) {
            error_log('make_payment: Invalid or missing ORIGINAL_AMOUNT');
            echo json_encode(['success' => false, 'message' => 'Geçersiz orijinal tutar']);
            exit;
        }
        if ($payment_amount > $original_amount) {
            error_log('make_payment: PAYMENT_AMOUNT exceeds ORIGINAL_AMOUNT');
            echo json_encode(['success' => false, 'message' => 'Ödeme tutarı orijinal tutardan fazla']);
            exit;
        }
        if (!$aciklama) {
            error_log('make_payment: Missing ACIKLAMA');
            echo json_encode(['success' => false, 'message' => 'Açıklama zorunludur']);
            exit;
        }
        if (!in_array($type, ['alacak', 'verecek'])) {
            error_log('make_payment: Invalid TYPE');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem tipi']);
            exit;
        }

        // Verify ALACAK_VERECEK_TYPE_ID exists
        $stmt = $db->prepare('SELECT ID FROM ALACAK_VERECEK_TYPE WHERE ID = 3');
        $stmt->execute();
        if (!$stmt->fetch()) {
            error_log('make_payment: ALACAK_VERECEK_TYPE_ID 3 does not exist');
            echo json_encode(['success' => false, 'message' => 'Ödeme tipi tanımlı değil']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Insert payment record (TYPE_ID=3)
            $stmt = $db->prepare('
                INSERT INTO ALACAK_VERECEK (KISI_ID, ALACAK_VERECEK_TYPE_ID, ÜCRET, ACIKLAMA, TARIH, ODENDI_MI)
                VALUES (:kisi_id, 3, :ucret, :aciklama, :tarih, 1)
            ');
            $stmt->execute([
                'kisi_id' => $kisi_id,
                'ucret' => $payment_amount,
                'aciklama' => $aciklama . ' (Ödeme)',
                'tarih' => date('Y-m-d')
            ]);
            error_log("make_payment: Inserted payment for KISI_ID: $kisi_id, ÜCRET: $payment_amount");

            // Mark original record as paid
            $stmt = $db->prepare('UPDATE ALACAK_VERECEK SET ODENDI_MI = 1 WHERE ID = :id');
            $stmt->execute(['id' => $id]);
            error_log("make_payment: Marked as paid ID: $id");

            // If partial payment, create new alacak/verecek with remaining amount
            if ($payment_amount < $original_amount) {
                $remaining_amount = $original_amount - $payment_amount;
                $new_type_id = ($type === 'alacak') ? 1 : 2;
                $new_aciklama = $aciklama . ' (Kalan tutar)';
                
                // Get original record's TARIH
                $stmt = $db->prepare('SELECT TARIH FROM ALACAK_VERECEK WHERE ID = :id');
                $stmt->execute(['id' => $id]);
                $original_tarih = $stmt->fetchColumn();
                $new_date = date('Y-m-d', strtotime($original_tarih . ' +7 days'));

                $stmt = $db->prepare('
                    INSERT INTO ALACAK_VERECEK (KISI_ID, ALACAK_VERECEK_TYPE_ID, ÜCRET, ACIKLAMA, TARIH, ODENDI_MI)
                    VALUES (:kisi_id, :type_id, :ucret, :aciklama, :tarih, 0)
                ');
                $stmt->execute([
                    'kisi_id' => $kisi_id,
                    'type_id' => $new_type_id,
                    'ucret' => $remaining_amount,
                    'aciklama' => $new_aciklama,
                    'tarih' => $new_date
                ]);
                error_log("make_payment: Inserted new $type for KISI_ID: $kisi_id, ÜCRET: $remaining_amount, TARIH: $new_date");
            }

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log('make_payment: Database error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ödeme kaydedilirken hata: ' . $e->getMessage()]);
        }
    } elseif ($action === 'get_dashboard_stats') {
        try {
            // Total transactions
            $stmt = $db->query('SELECT COUNT(*) AS total FROM ISLEM');
            $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Total unpaid alacak
            $stmt = $db->prepare('
                SELECT COALESCE(SUM(ÜCRET), 0) AS total
                FROM ALACAK_VERECEK
                WHERE ALACAK_VERECEK_TYPE_ID = 1 AND ODENDI_MI = 0
            ');
            $stmt->execute();
            $total_alacak = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Total unpaid verecek
            $stmt = $db->prepare('
                SELECT COALESCE(SUM(ÜCRET), 0) AS total
                FROM ALACAK_VERECEK
                WHERE ALACAK_VERECEK_TYPE_ID = 2 AND ODENDI_MI = 0
            ');
            $stmt->execute();
            $total_verecek = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo json_encode([
                'success' => true,
                'total_transactions' => $total_transactions,
                'total_alacak' => $total_alacak,
                'total_verecek' => $total_verecek
            ]);
        } catch (Exception $e) {
            error_log('get_dashboard_stats: Database error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'İstatistikler alınırken hata: ' . $e->getMessage()]);
        }
    } elseif ($action === 'get_recent_transactions') {
        try {
            $stmt = $db->prepare('
                SELECT 
                    i.ID, i.TARIH, k1.NAME AS SUPPLIER_NAME, k2.NAME AS BUYER_NAME, 
                    u.NAME AS URUN_ADI, i.FIRELI_TON, i.BIRIM_FIYATI, 
                    (i.FIRELI_TON * i.BIRIM_FIYATI) AS TOTAL_FIYAT
                FROM ISLEM i
                JOIN KISILER k1 ON i.TEDARIKCI_ID = k1.ID
                JOIN KISILER k2 ON i.ALICI_ID = k2.ID
                JOIN URUN u ON i.URUN_ID = u.ID
                ORDER BY i.TARIH DESC
                LIMIT 5
            ');
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'transactions' => $transactions]);
        } catch (Exception $e) {
            error_log('get_recent_transactions: Database error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Son işlemler alınırken hata: ' . $e->getMessage()]);
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