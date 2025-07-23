<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verecek</title>
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
    .person-list {
      max-height: 200px;
      overflow-y: auto;
    }
    .person-item:hover {
      background-color: #f3f4f6;
      cursor: pointer;
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
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Verecek Listesi</h2>
      <div class="mb-6">
        <input type="text" id="search-person" class="w-full p-2 border rounded input-field" placeholder="Kişi ara...">
        <div id="person-list" class="person-list mt-2"></div>
        <div class="flex justify-between mt-4">
          <button id="add-person-btn" class="py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">Yeni Kişi Ekle</button>
        </div>
      </div>
      <div id="verecek-list" class="space-y-4"></div>
    </div>
  </div>

  <script>
    let people = [];
    let verecekList = [];

    document.addEventListener('DOMContentLoaded', () => {
      console.log('Verecek.php loaded');
      loadPeople();
      loadVerecek();
      document.getElementById('search-person').addEventListener('input', filterPeople);
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

    function loadPeople() {
      console.log('Loading people...');
      fetch('api/alacak.php?action=get_people')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          people = data.sort((a, b) => a.NAME.localeCompare(b.NAME, 'tr'));
          console.log('People loaded:', people);
          // Do not render person list initially
          document.getElementById('person-list').innerHTML = '';
        })
        .catch(error => {
          console.error('Error loading people:', error);
          Swal.fire('Hata!', 'Kişiler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    function renderPersonList(filteredPeople) {
      const personListDiv = document.getElementById('person-list');
      personListDiv.innerHTML = filteredPeople.length === 0 ? `
        <div class="text-gray-500 p-2">Kişi bulunamadı</div>
      ` : filteredPeople.map(p => `
        <div class="person-item p-2 border-b" onclick="showVerecekForm(${p.ID}, '${p.NAME}')">
          ${p.NAME}${p.TEL_NO ? ` (${p.TEL_NO})` : ''}
        </div>
      `).join('');
    }

    function filterPeople() {
      const searchTerm = document.getElementById('search-person').value.toLowerCase();
      const filtered = people.filter(p => p.NAME.toLowerCase().includes(searchTerm));
      renderPersonList(filtered);
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
              <th>Ücret (₺)</th>
              <th>Açıklama</th>
              <th>Tarih</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            ${verecekList.map(v => `
              <tr>
                <td>${v.NAME}</td>
                <td class="amount-field">${formatNumber(v.ÜCRET)}</td>
                <td>${v.ACIKLAMA}</td>
                <td>${formatDate(v.TARIH)}</td>
                <td>
                  <button onclick="makePayment(${v.ID}, ${v.ÜCRET}, ${v.KISI_ID}, '${v.NAME}', 'verecek')" 
                          class="py-1 px-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Ödeme Yap
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

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
          fetch('api/alacak.php?action=add_person', {
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
              Swal.fire('Başarılı!', 'Kişi eklendi.', 'success').then(() => {
                loadPeople();
              });
            } else {
              console.error('Add person failed:', data.message);
              Swal.fire('Hata!', data.message || 'Kişi eklenirken hata oluştu.', 'error');
            }
          })
          .catch(error => {
            console.error('Error adding person:', error);
            Swal.fire('Hata!', 'Kişi eklenirken hata oluştu: ' + error.message, 'error');
          });
        }
      });
    };

    function showVerecekForm(personId, personName) {
      Swal.fire({
        title: `${personName} için Verecek Ekle`,
        html: `
          <div class="text-left">
            <label class="block mb-1 text-sm font-medium text-gray-700 amount-field">Ücret (₺)</label>
            <input id="swal-amount" class="swal2-input amount-field" placeholder="Örn: 12.500,00">
            <label class="block mb-1 text-sm font-medium text-gray-700">Açıklama</label>
            <textarea id="swal-description" class="swal2-textarea" placeholder="Açıklama (zorunlu)"></textarea>
            <label class="block mb-1 text-sm font-medium text-gray-700">Tarih</label>
            <input id="swal-date" type="date" class="swal2-input">
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Kaydet',
        cancelButtonText: 'İptal',
        preConfirm: () => {
          const amount = parseFormattedNumber(document.getElementById('swal-amount').value);
          const description = document.getElementById('swal-description').value.trim();
          const date = document.getElementById('swal-date').value;
          if (!amount || amount <= 0) {
            Swal.showValidationMessage('Geçerli bir ücret giriniz.');
            return false;
          }
          if (!description) {
            Swal.showValidationMessage('Açıklama zorunludur.');
            return false;
          }
          if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            Swal.showValidationMessage('Geçerli bir tarih seçiniz.');
            return false;
          }
          return { amount, description, date };
        },
        didOpen: () => {
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
      }).then(result => {
        if (result.isConfirmed) {
          const { amount, description, date } = result.value;
          Swal.fire({
            title: 'Emin misiniz?',
            text: `${personName} için ${formatNumber(amount)} ₺ verecek kaydetmek istiyor musunuz?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'Hayır'
          }).then(confirmResult => {
            if (confirmResult.isConfirmed) {
              fetch('api/alacak.php?action=add_verecek', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  KISI_ID: personId,
                  ÜCRET: amount,
                  TARIH: date,
                  ACIKLAMA: description
                })
              })
              .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
              })
              .then(data => {
                if (data.success) {
                  Swal.fire('Başarılı!', 'Verecek kaydedildi.', 'success').then(() => {
                    loadVerecek();
                  });
                } else {
                  console.error('Add verecek failed:', data.message);
                  Swal.fire('Hata!', data.message || 'Verecek kaydedilirken hata oluştu.', 'error');
                }
              })
              .catch(error => {
                console.error('Error saving verecek:', error);
                Swal.fire('Hata!', 'Verecek kaydedilirken hata oluştu: ' + error.message, 'error');
              });
            }
          });
        }
      });
    }

    function makePayment(id, originalAmount, personId, personName, type) {
      Swal.fire({
        title: `${personName} için Ödeme Yap`,
        html: `
          <div class="text-left">
            <label class="block mb-1 text-sm font-medium text-gray-700 amount-field">Ödeme Tutarı (₺)</label>
            <input id="swal-payment-amount" class="swal2-input amount-field" placeholder="Örn: 5.000,00">
            <label class="block mb-1 text-sm font-medium text-gray-700">Açıklama</label>
            <textarea id="swal-description" class="swal2-textarea" placeholder="Açıklama (zorunlu)"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ödeme Yap',
        cancelButtonText: 'İptal',
        preConfirm: () => {
          const paymentAmount = parseFormattedNumber(document.getElementById('swal-payment-amount').value);
          const description = document.getElementById('swal-description').value.trim();
          if (!paymentAmount || paymentAmount <= 0) {
            Swal.showValidationMessage('Geçerli bir ödeme tutarı giriniz.');
            return false;
          }
          if (paymentAmount > originalAmount) {
            Swal.showValidationMessage('Ödeme tutarı, verecek tutarından fazla olamaz.');
            return false;
          }
          if (!description) {
            Swal.showValidationMessage('Açıklama zorunludur.');
            return false;
          }
          return { paymentAmount, description };
        },
        didOpen: () => {
          const paymentInput = document.getElementById('swal-payment-amount');
          paymentInput.addEventListener('input', () => {
            let val = paymentInput.value.replace(/[^0-9,]/g, '');
            if (val) {
              const parts = val.split(',');
              if (parts[0]) {
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                paymentInput.value = parts.join(',');
              }
            }
          });
        }
      }).then(result => {
        if (result.isConfirmed) {
          const { paymentAmount, description } = result.value;
          Swal.fire({
            title: 'Emin misiniz?',
            text: `${personName} için ${formatNumber(paymentAmount)} ₺ ödeme yapmak istiyor musunuz?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'Hayır'
          }).then(confirmResult => {
            if (confirmResult.isConfirmed) {
              fetch('api/alacak.php?action=make_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  ID: id,
                  KISI_ID: personId,
                  PAYMENT_AMOUNT: paymentAmount,
                  ORIGINAL_AMOUNT: originalAmount,
                  ACIKLAMA: description,
                  TYPE: type
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
                  console.error('Payment failed:', data.message);
                  Swal.fire('Hata!', data.message || 'Ödeme kaydedilirken hata oluştu.', 'error');
                }
              })
              .catch(error => {
                console.error('Error saving payment:', error);
                Swal.fire('Hata!', 'Ödeme kaydedilirken hata oluştu: ' + error.message, 'error');
              });
            }
          });
        }
      });
    }
  </script>
</body>
</html>