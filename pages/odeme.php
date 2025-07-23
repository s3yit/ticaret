<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script><?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ödeme</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .form-container {
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .content {
      padding: 2rem;
    }

    .input-field {
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .input-field:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .amount-field {
      color: #166534;
    }

    .transaction-table {
      width: 100%;
      border-collapse: collapse;
    }

    .transaction-table th,
    .transaction-table td {
      padding: 0.75rem;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }

    .transaction-table th {
      background: #f9fafb;
      font-weight: 600;
      color: #374151;
    }

    @media (max-width: 768px) {
      .content {
        padding: 1rem;
      }

      .transaction-table {
        display: block;
        overflow-x: auto;
      }

      .transaction-table th,
      .transaction-table td {
        min-width: 120px;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="content">
    <div class="form-container p-8 w-full max-w-4xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Verecekler ve Ödemeler</h2>
      <div id="verecek-list" class="space-y-4"></div>
    </div>
  </div>

  <script>
    let verecekList = [];

    document.addEventListener('DOMContentLoaded', () => {
      console.log('Odeme.php loaded');
      loadVerecek();
    });

    function formatNumber(value) {
      if (!value) return '0,00';
      const parts = parseFloat(value).toFixed(2).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return parts.join(',');
    }

    function parseFormattedNumber(value) {
      if (!value) return 0;
      const cleanValue = value.replace(/\./g, '').replace(',', '.').replace(/[^0-9.]/g, '');
      const result = parseFloat(cleanValue);
      return isNaN(result) ? 0 : result;
    }

    function formatDate(dateStr) {
      const [year, month, day] = dateStr.split('-');
      return `${day}.${month}.${year}`;
    }

    function loadVerecek() {
      console.log('Loading verecek...');
      fetch('api/alacak.php?action=get_verecek')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            verecekList = data.verecek;
            console.log('Verecek loaded:', verecekList);
            renderVerecekList();
          } else {
            Swal.fire('Hata!', data.message || 'Verecekler yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error loading verecek:', error);
          Swal.fire('Hata!', 'Verecekler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    function renderVerecekList() {
      console.log('Rendering verecek list...');
      const verecekListDiv = document.getElementById('verecek-list');
      verecekListDiv.innerHTML = verecekList.length === 0 ? `
        <div class="text-center text-gray-500 py-4">Verecek bulunamadı</div>
      ` : `
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Kişi</th>
              <th>Verecek Tutarı (₺)</th>
              <th>Açıklama</th>
              <th>Tarih</th>
              <th>Ödeme Yap</th>
            </tr>
          </thead>
          <tbody>
            ${verecekList.map(v => `
              <tr>
                <td>${v.NAME}</td>
                <td class="amount-field">${formatNumber(v.AMOUNT)}</td>
                <td>${v.ACIKLAMA}</td>
                <td>${formatDate(v.TARIH)}</td>
                <td>
                  <button onclick="makePayment(${v.ID}, '${v.NAME}', ${v.AMOUNT})" class="py-1 px-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Ödeme Yap
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function makePayment(id, name, amount) {
      Swal.fire({
        title: `${name} için Ödeme`,
        html: `
          <div class="text-left">
            <p><strong>Verecek Tutarı:</strong> ${formatNumber(amount)} ₺</p>
            <label class="block mb-1 text-sm font-medium text-gray-700">Ödenecek Tutar (₺)</label>
            <input id="swal-amount" class="swal2-input amount-field" placeholder="Örn: 5.000,00">
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Öde',
        cancelButtonText: 'İptal',
        preConfirm: () => {
          const paymentAmount = parseFormattedNumber(document.getElementById('swal-amount').value);
          if (!paymentAmount || paymentAmount <= 0) {
            Swal.showValidationMessage('Geçerli bir tutar giriniz.');
            return false;
          }
          if (paymentAmount > amount) {
            Swal.showValidationMessage('Ödeme tutarı verecek tutarından fazla olamaz.');
            return false;
          }
          return paymentAmount;
        }
      }).then(result => {
        if (result.isConfirmed) {
          const paymentAmount = result.value;
          fetch('api/alacak.php?action=make_payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              ID: id,
              KISI_ID: verecekList.find(v => v.ID === id).KISI_ID,
              PAYMENT_AMOUNT: paymentAmount,
              ORIGINAL_AMOUNT: amount,
              ACIKLAMA: verecekList.find(v => v.ID === id).ACIKLAMA,
              TARIH: verecekList.find(v => v.ID === id).TARIH
            })
          })
          .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
          })
          .then(data => {
            if (data.success) {
              Swal.fire('Başarılı!', 'Ödeme kaydedildi.', 'success').then(() => {
                loadVerecek();
              });
            } else {
              console.error('Make payment failed:', data.message);
              Swal.fire('Hata!', data.message || 'Ödeme kaydedilirken hata oluştu.', 'error');
            }
          })
          .catch(error => {
            console.error('Error making payment:', error);
            Swal.fire('Hata!', 'Ödeme kaydedilirken hata oluştu: ' + error.message, 'error');
          });
        }
      });

      const amountInput = document.getElementById('swal-amount');
      amountInput.addEventListener('input', () => {
        let val = amountInput.value.replace(/[^0-9,]/g, '');
        if (val) {
          const parts = val.split(',');
          if (parts[0]) {
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            amountInput.value = parts.join(',');
          }
        }
      });
    }
  </script>
</body>
</html>