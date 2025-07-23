<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kişiler</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .form-container {
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .person-item {
      transition: background 0.2s ease;
    }

    .person-item:hover {
      background: #f1f5f9;
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

    .total-cost {
      font-size: 1.125rem;
      font-weight: 700;
      color: #166534;
      background: #dcfce7;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
    }

    .summary-card {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 0.75rem;
      padding: 1.5rem;
    }

    .summary-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
    }

    .summary-label {
      font-weight: 600;
      color: #374151;
    }

    .summary-value {
      color: #1f2937;
    }

    .total-cost-label,
    .total-cost-value {
      color: #166534 !important;
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
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Kişiler</h2>
      <div class="flex gap-4 mb-6">
        <input type="text" id="person-search" placeholder="Kişi ara..." class="input-field w-full p-3 border border-gray-300 rounded-lg">
        <button id="add-person-btn" class="py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Yeni Kişi Ekle</button>
      </div>
      <div id="person-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
    </div>
  </div>

  <script>
    let people = [];
    let products = [];

    // Initialize app
    document.addEventListener('DOMContentLoaded', () => {
      console.log('Kisiler.php loaded');
      loadPeople();
      loadProducts();
    });

    // Utility functions
    function formatNumber(value) {
      if (!value) return '0,00';
      const parts = parseFloat(value).toFixed(2).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return parts.join(',');
    }

    function formatDate(dateStr) {
      const [year, month, day] = dateStr.split('-');
      return `${day}.${month}.${year}`;
    }

    // Load data
    function loadPeople() {
      console.log('Loading people...');
      fetch('api/kisiler.php?action=get_people')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          people = data.sort((a, b) => a.NAME.localeCompare(b.NAME, 'tr'));
          console.log('People loaded:', people);
          renderPersonList();
        })
        .catch(error => {
          console.error('Error loading people:', error);
          Swal.fire('Hata!', 'Kişiler yüklenirken bir hata oluştu: ' + error.message, 'error');
        });
    }

    function loadProducts() {
      console.log('Loading products...');
      fetch('api/kisiler.php?action=get_products')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          products = data;
          console.log('Products loaded:', products);
        })
        .catch(error => {
          console.error('Error loading products:', error);
          Swal.fire('Hata!', 'Ürünler yüklenirken bir hata oluştu: ' + error.message, 'error');
        });
    }

    // Render person list
    function renderPersonList() {
      console.log('Rendering person list...');
      const personList = document.getElementById('person-list');
      const searchInput = document.getElementById('person-search');
      personList.innerHTML = people.length === 0 ? `
        <div class="text-center text-gray-500 py-4">Kişi bulunamadı</div>
      ` : people.map(person => `
        <div class="person-item flex justify-between items-center p-3 border border-gray-300 rounded-lg">
          <div>
            <div class="font-semibold">${person.NAME}</div>
            <div class="text-sm text-gray-500">${person.TEL_NO || 'Telefon yok'}</div>
          </div>
          <button onclick="showTransactionOptions(${person.ID}, '${person.NAME}')" class="py-2 px-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      `).join('');

      searchInput.addEventListener('input', () => {
        const filtered = people.filter(p => p.NAME.toLowerCase().includes(searchInput.value.toLowerCase()));
        personList.innerHTML = filtered.length === 0 ? `
          <div class="text-center text-gray-500 py-4">Kişi bulunamadı</div>
        ` : filtered.map(person => `
          <div class="person-item flex justify-between items-center p-3 border border-gray-300 rounded-lg">
            <div>
              <div class="font-semibold">${person.NAME}</div>
              <div class="text-sm text-gray-500">${person.TEL_NO || 'Telefon yok'}</div>
            </div>
            <button onclick="showTransactionOptions(${person.ID}, '${person.NAME}')" class="py-2 px-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        `).join('');
      });

      document.getElementById('add-person-btn').onclick = () => {
        Swal.fire({
          title: 'Yeni Kişi Ekle',
          html: `
            <input id="swal-name" class="swal2-input" placeholder="Kişi adı (zorunlu)">
            <input id="swal-tel" class="swal2-input" placeholder="Telefon numarası (opsiyonel)">
          `,
          showCancelButton: true,
          confirmButtonText: 'Ekle',
          cancelButtonText: 'İptal',
          preConfirm: () => {
            const name = document.getElementById('swal-name').value.trim();
            const tel = document.getElementById('swal-tel').value.trim();
            if (!name) {
              Swal.showValidationMessage('Kişi adı zorunludur');
              return false;
            }
            return { name, tel };
          }
        }).then(result => {
          if (result.isConfirmed) {
            const { name, tel } = result.value;
            fetch('api/kisiler.php?action=add_person', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ NAME: name, TEL_NO: tel || null })
            })
            .then(response => {
              if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
              return response.json();
            })
            .then(data => {
              if (data.success) {
                loadPeople();
                Swal.fire('Başarılı!', 'Kişi eklendi.', 'success');
              } else {
                console.error('Add person failed:', data.message);
                Swal.fire('Hata!', data.message || 'Kişi eklenirken bir hata oluştu.', 'error');
              }
            })
            .catch(error => {
              console.error('Error adding person:', error);
              Swal.fire('Hata!', 'Kişi eklenirken bir hata oluştu: ' + error.message, 'error');
            });
          }
        });
      };
    }

    // Show transaction options
    function showTransactionOptions(personId, personName) {
      Swal.fire({
        title: `${personName} için işlem detayları`,
        text: 'Hangi bilgileri görüntülemek istiyorsunuz?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Kişiden Alınan Mal',
        cancelButtonText: 'Kişiye Satılan Mal',
        showDenyButton: true,
        denyButtonText: 'İptal'
      }).then(result => {
        if (result.isConfirmed) {
          fetchSupplierTransactions(personId, personName);
        } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
          fetchBuyerTransactions(personId, personName);
        }
      });
    }

    // Fetch supplier transactions
    function fetchSupplierTransactions(personId, personName) {
      console.log(`Fetching supplier transactions for person ${personId}...`);
      fetch(`api/kisiler.php?action=get_supplier_transactions&person_id=${personId}`)
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            showTransactionModal(personName, 'Tedarikçi', data.transactions);
          } else {
            Swal.fire('Hata!', data.message || 'İşlemler yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error fetching supplier transactions:', error);
          Swal.fire('Hata!', 'İşlemler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    // Fetch buyer transactions
    function fetchBuyerTransactions(personId, personName) {
      console.log(`Fetching buyer transactions for person ${personId}...`);
      fetch(`api/kisiler.php?action=get_buyer_transactions&person_id=${personId}`)
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            showTransactionModal(personName, 'Alıcı', data.transactions);
          } else {
            Swal.fire('Hata!', data.message || 'İşlemler yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error fetching buyer transactions:', error);
          Swal.fire('Hata!', 'İşlemler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    // Show transaction modal
    function showTransactionModal(personName, role, transactions) {
      const headers = role === 'Tedarikçi' ? 
        ['Tarih', 'Tır Plakası', 'Alıcı İsmi', 'Ürün Adı', 'Firesiz Ton', 'Fireli Ton', 'Birim Fiyat', 'Toplam Fiyat', 'Detay'] :
        ['Tarih', 'Tır Plakası', 'Tedarikçi İsmi', 'Ürün Adı', 'Firesiz Ton', 'Fireli Ton', 'Birim Fiyat', 'Toplam Fiyat', 'Detay'];

      const tableRows = transactions.length === 0 ? `
        <tr><td colspan="9" class="text-center text-gray-500 py-4">Kayıt bulunamadı</td></tr>
      ` : transactions.map(t => `
        <tr>
          <td>${formatDate(t.TARIH)}</td>
          <td>${t.PLAKA}</td>
          <td>${role === 'Tedarikçi' ? t.BUYER_NAME : t.SUPPLIER_NAME}</td>
          <td>${t.URUN_ADI}</td>
          <td>${formatNumber(t.FIRESIZ_TON)} ton</td>
          <td>${formatNumber(t.FIRELI_TON)} ton</td>
          <td>${formatNumber(t.BIRIM_FIYATI)} ₺</td>
          <td class="total-cost-value">${formatNumber(t.TOTAL_FIYAT)} ₺</td>
          <td>
            <button onclick="showTransactionDetail(${t.ID}, '${role}', '${personName}')" class="py-1 px-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Detay
            </button>
          </td>
        </tr>
      `).join('');

      Swal.fire({
        title: `${personName} - ${role} İşlemleri`,
        html: `
          <table class="transaction-table">
            <thead>
              <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
            </thead>
            <tbody>${tableRows}</tbody>
          </table>
        `,
        width: '80%',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat'
      });
    }

    // Show transaction detail
    function showTransactionDetail(transactionId, role, personName) {
      console.log(`Fetching transaction detail for ID ${transactionId}...`);
      fetch(`api/kisiler.php?action=get_transaction_detail&transaction_id=${transactionId}`)
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const t = data.transaction;
            const totalCost = formatNumber((t.FIRELI_TON * t.BIRIM_FIYATI).toFixed(2));
            Swal.fire({
              title: `${personName} - ${role} İşlem Detayı`,
              html: `
                <div class="summary-card">
                  <h2 class="summary-title mb-6">İşlem Özeti</h2>
                  <div class="space-y-6">
                    <div>
                      <h3 class="text-lg font-semibold text-gray-700 mb-3">Taraflar</h3>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <span class="summary-label block text-sm">Tedarikçi</span>
                          <span class="summary-value">${t.SUPPLIER_NAME}</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Alıcı</span>
                          <span class="summary-value">${t.BUYER_NAME}</span>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-gray-700 mb-3">Ürün ve Tonaj Bilgileri</h3>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <span class="summary-label block text-sm">Ürün</span>
                          <span class="summary-value">${t.URUN_ADI}</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Brüt Tonaj</span>
                          <span class="summary-value">${formatNumber(t.FIRESIZ_TON)} ton</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Net Tonaj</span>
                          <span class="summary-value">${formatNumber(t.FIRELI_TON)} ton</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Birim Fiyat</span>
                          <span class="summary-value">${formatNumber(t.BIRIM_FIYATI)} ₺</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Toplam Maliyet</span>
                          <span class="summary-value total-cost-value">${totalCost} ₺</span>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-gray-700 mb-3">Lojistik Bilgileri</h3>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <span class="summary-label block text-sm">Tır Plakası</span>
                          <span class="summary-value">${t.PLAKA}</span>
                        </div>
                        <div>
                          <span class="summary-label block text-sm">Teslimat Tarihi</span>
                          <span class="summary-value">${formatDate(t.TARIH)}</span>
                        </div>
                      </div>
                    </div>
                    ${t.ACIKLAMA ? `
                      <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Notlar</h3>
                        <div>
                          <span class="summary-label block text-sm">Notlar</span>
                          <span class="summary-value">${t.ACIKLAMA}</span>
                        </div>
                      </div>
                    ` : ''}
                    <div>
                      <h3 class="text-lg font-semibold text-gray-700 mb-3">Genel Toplam Maliyet</h3>
                      <div class="total-cost">${totalCost} ₺</div>
                    </div>
                  </div>
                </div>
              `,
              width: '60%',
              showConfirmButton: false,
              showCancelButton: true,
              cancelButtonText: 'Kapat'
            });
          } else {
            Swal.fire('Hata!', data.message || 'İşlem detayı yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error fetching transaction detail:', error);
          Swal.fire('Hata!', 'İşlem detayı yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }
  </script>
</body>
</html>