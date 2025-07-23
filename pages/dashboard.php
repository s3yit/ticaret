<?php
require_once  './config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticaret Yönetim Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .content {
      padding: 2rem;
    }
    .dashboard-card {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
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
    .recent-transactions {
      max-height: 300px;
      overflow-y: auto;
    }
    .amount-field {
      color: #166534;
    }
    @media (max-width: 768px) {
      .content {
        padding: 1rem;
      }
      .dashboard-card {
        margin-bottom: 1rem;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="content">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Ticaret Yönetim Paneli</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="dashboard-card">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Toplam İşlem</h2>
        <p id="total-transactions" class="text-2xl font-bold text-blue-600">0</p>
      </div>
      <div class="dashboard-card">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Toplam Alacak</h2>
        <p id="total-alacak" class="text-2xl font-bold text-green-600 amount-field">0,00 ₺</p>
      </div>
      <div class="dashboard-card">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Toplam Verecek</h2>
        <p id="total-verecek" class="text-2xl font-bold text-red-600 amount-field">0,00 ₺</p>
      </div>
    </div>
    <div class="dashboard-card mt-6">
      <h2 class="text-xl font-semibold text-gray-700 mb-4">Son İşlemler</h2>
      <div class="recent-transactions">
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Tedarikçi</th>
              <th>Alıcı</th>
              <th>Ürün</th>
              <th>Net Tonaj</th>
              <th>Birim Fiyat</th>
              <th>Toplam Fiyat</th>
              <th>Tarih</th>
            </tr>
          </thead>
          <tbody id="recent-transactions">
            <tr><td colspan="7" class="text-center text-gray-500">Yükleniyor...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
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

    function loadDashboardStats() {
      console.log('Loading dashboard stats...');
      fetch('api/dash.php?action=get_dashboard_stats')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            document.getElementById('total-transactions').textContent = data.total_transactions;
            document.getElementById('total-alacak').textContent = formatNumber(data.total_alacak) + ' ₺';
            document.getElementById('total-verecek').textContent = formatNumber(data.total_verecek) + ' ₺';
            console.log('Dashboard stats loaded:', data);
          } else {
            Swal.fire('Hata!', data.message || 'İstatistikler yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error loading dashboard stats:', error);
          Swal.fire('Hata!', 'İstatistikler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    function loadRecentTransactions() {
      console.log('Loading recent transactions...');
      fetch('api/dash.php?action=get_recent_transactions')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const tbody = document.getElementById('recent-transactions');
            tbody.innerHTML = data.transactions.length === 0 ? `
              <tr><td colspan="7" class="text-center text-gray-500">İşlem bulunamadı</td></tr>
            ` : data.transactions.map(t => `
              <tr>
                <td>${t.SUPPLIER_NAME}</td>
                <td>${t.BUYER_NAME}</td>
                <td>${t.URUN_ADI}</td>
                <td>${formatNumber(t.FIRELI_TON)}</td>
                <td>${formatNumber(t.BIRIM_FIYATI)} ₺</td>
                <td class="amount-field">${formatNumber(t.TOTAL_FIYAT)} ₺</td>
                <td>${formatDate(t.TARIH)}</td>
              </tr>
            `).join('');
            console.log('Recent transactions loaded:', data.transactions);
          } else {
            Swal.fire('Hata!', data.message || 'Son işlemler yüklenirken hata oluştu.', 'error');
          }
        })
        .catch(error => {
          console.error('Error loading recent transactions:', error);
          Swal.fire('Hata!', 'Son işlemler yüklenirken hata oluştu: ' + error.message, 'error');
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      console.log('Dashboard loaded');
      loadDashboardStats();
      loadRecentTransactions();
    });
  </script>
</body>
</html>
