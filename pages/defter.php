<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Defter</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .form-container {
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
    .total-cost {
      font-size: 1.25rem;
      font-weight: 700;
      color: #166534;
      background: #dcfce7;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
    }
    .input-field {
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .input-field:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
    .product-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      align-items: center;
      padding: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }
    .product-row:last-child {
      border-bottom: none;
    }
    @media (max-width: 768px) {
      .content {
        padding: 1rem;
      }
      .product-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="content">
    <div class="form-container p-8 w-full max-w-4xl mx-auto">
      <!-- Supplier Selection -->
      <div id="supplier-selection" class="space-y-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Lütfen Tedarikçi Seçiniz</h2>
        <div class="flex gap-4">
          <input type="text" id="supplier-search" placeholder="Tedarikçi ara..." class="input-field w-full p-3 border border-gray-300 rounded-lg">
          <button id="add-supplier-btn" class="py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Yeni Kişi Ekle</button>
        </div>
        <div id="supplier-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
      </div>

      <!-- Buyer Selection -->
      <div id="buyer-selection" class="space-y-4 hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Lütfen Alıcı Seçiniz</h2>
        <div class="flex gap-4">
          <input type="text" id="buyer-search" placeholder="Alıcı ara..." class="input-field w-full p-3 border border-gray-300 rounded-lg">
          <button id="add-buyer-btn" class="py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Yeni Kişi Ekle</button>
        </div>
        <div id="buyer-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
        <div class="flex gap-4">
          <button id="buyer-back-btn" class="w-full py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Geri</button>
        </div>
      </div>

      <!-- First Transaction Form (Single Product) -->
      <div id="transaction-form" class="space-y-4 hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Alım Bilgileri</h2>
        <div id="form-section" class="space-y-4"></div>
        <div class="flex gap-4">
          <button id="form-back-btn" class="w-full py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Geri</button>
          <button id="form-multi-btn" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Çoklu Ürün Ekle</button>
          <button id="form-review-btn" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Gözden Geçir</button>
        </div>
      </div>

      <!-- Second Transaction Form (Multiple Products) -->
      <div id="multi-product-form" class="space-y-4 hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Çoklu Ürün Alım Bilgileri</h2>
        <div id="multi-form-section" class="space-y-4"></div>
        <div class="flex gap-4">
          <button id="add-product-btn" class="py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> Ürün Ekle
          </button>
          <button id="multi-back-btn" class="w-full py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Geri</button>
          <button id="multi-review-btn" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Gözden Geçir</button>
        </div>
      </div>

      <!-- Summary -->
      <div id="summary" class="space-y-4 hidden"></div>
    </div>
  </div>

  <script>
    // Global state
    let currentStep = 'supplier';
    let formData = {
      supplierId: null,
      supplierName: '',
      buyerId: null,
      buyerName: '',
      products: [],
      plaka: '',
      tarih: '',
      not: ''
    };
    let people = [];
    let products = [];

    // Initialize app
    document.addEventListener('DOMContentLoaded', () => {
      console.log('Defter.php loaded');
      loadPeople();
      loadProducts();
      renderSupplierSelection();
    });

    // Utility functions
    function formatNumber(value) {
      if (!value) return '';
      const parts = value.toString().split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return parts.join(',');
    }

    function parseFormattedNumber(value) {
      if (!value) return 0;
      const cleanValue = value.replace(/\./g, '').replace(',', '.').replace(/[^0-9.]/g, '');
      const result = parseFloat(cleanValue);
      return isNaN(result) ? 0 : result;
    }

    // Load data
    function loadPeople() {
      console.log('Loading people...');
      fetch('api/api.php?action=get_people')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          people = data;
          console.log('People loaded:', people);
          renderSupplierSelection();
          renderBuyerSelection();
        })
        .catch(error => {
          console.error('Error loading people:', error);
          Swal.fire('Hata!', 'Kişiler yüklenirken bir hata oluştu: ' + error.message, 'error');
        });
    }

    function loadProducts() {
      console.log('Loading products...');
      fetch('api/api.php?action=get_products')
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

    // Supplier selection
    function renderSupplierSelection() {
      console.log('Rendering supplier selection...');
      const supplierList = document.getElementById('supplier-list');
      const searchInput = document.getElementById('supplier-search');
      supplierList.innerHTML = '';
      searchInput.value = '';
      searchInput.addEventListener('input', () => {
        const filtered = people.filter(p => p.NAME.toLowerCase().includes(searchInput.value.toLowerCase()));
        supplierList.innerHTML = filtered.length === 0 ? `
          <div class="text-center text-gray-500 py-4">Kişi bulunamadı</div>
        ` : filtered.map(person => `
          <div class="person-item p-3 border border-gray-300 rounded-lg cursor-pointer" onclick="selectSupplier(${person.ID}, '${person.NAME}')">
            <div class="font-semibold">${person.NAME}</div>
            <div class="text-sm text-gray-500">${person.TEL_NO || 'Telefon yok'}</div>
          </div>
        `).join('');
      });

      document.getElementById('add-supplier-btn').onclick = () => {
        Swal.fire({
          title: 'Yeni Tedarikçi Ekle',
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
            fetch('api/api.php?action=add_person', {
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
                Swal.fire('Başarılı!', 'Tedarikçi eklendi.', 'success');
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

    function selectSupplier(id, name) {
      formData.supplierId = id;
      formData.supplierName = name;
      console.log('Supplier selected:', { id, name });
      Swal.fire({
        title: 'Tedarikçi Seçimi',
        text: `Seçtiğiniz tedarikçi: ${name}. Devam edelim mi?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'Hayır'
      }).then(result => {
        if (result.isConfirmed) {
          goToStep('buyer');
        }
      });
    }

    // Buyer selection
    function renderBuyerSelection() {
      console.log('Rendering buyer selection...');
      const buyerList = document.getElementById('buyer-list');
      const searchInput = document.getElementById('buyer-search');
      buyerList.innerHTML = '';
      searchInput.value = '';
      searchInput.addEventListener('input', () => {
        const filtered = people.filter(p => p.NAME.toLowerCase().includes(searchInput.value.toLowerCase()));
        buyerList.innerHTML = filtered.length === 0 ? `
          <div class="text-center text-gray-500 py-4">Kişi bulunamadı</div>
        ` : filtered.map(person => `
          <div class="person-item p-3 border border-gray-300 rounded-lg cursor-pointer" onclick="selectBuyer(${person.ID}, '${person.NAME}')">
            <div class="font-semibold">${person.NAME}</div>
            <div class="text-sm text-gray-500">${person.TEL_NO || 'Telefon yok'}</div>
          </div>
        `).join('');
      });

      document.getElementById('add-buyer-btn').onclick = () => {
        Swal.fire({
          title: 'Yeni Alıcı Ekle',
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
            fetch('api/api.php?action=add_person', {
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
                Swal.fire('Başarılı!', 'Alıcı eklendi.', 'success');
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

    function selectBuyer(id, name) {
      formData.buyerId = id;
      formData.buyerName = name;
      console.log('Buyer selected:', { id, name });
      Swal.fire({
        title: 'Alıcı Seçimi',
        text: `Seçtiğiniz alıcı: ${name}. Devam edelim mi?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'Hayır'
      }).then(result => {
        if (result.isConfirmed) {
          goToStep('form');
        }
      });
    }

    // First Transaction Form (Single Product)
    function renderForm() {
      console.log('Rendering single product form...');
      const formSection = document.getElementById('form-section');
      const fields = [
        {
          key: 'urun',
          label: 'Ürün Seçimi',
          type: 'select',
          options: products.map(p => ({ value: p.ID, text: p.NAME }))
        },
        {
          key: 'plaka',
          label: 'Tır Plakası',
          type: 'text',
          placeholder: 'Örn: 34 ABC 123'
        },
        {
          key: 'tonaj',
          label: 'Brüt Tonaj (ton)',
          type: 'text',
          placeholder: 'Örn: 12.500'
        },
        {
          key: 'net_tonaj',
          label: 'Net Tonaj (%2 fire düşülmüş)',
          type: 'text',
          placeholder: 'Örn: 12.250'
        },
        {
          key: 'fiyat',
          label: 'Birim Fiyat (₺)',
          type: 'text',
          placeholder: 'Örn: 8,75'
        },
        {
          key: 'total_cost',
          label: 'Toplam Maliyet',
          type: 'text',
          placeholder: 'Otomatik hesaplanır',
          readonly: true,
          class: 'total-cost-value'
        },
        {
          key: 'tarih',
          label: 'Teslimat Tarihi',
          type: 'date'
        },
        {
          key: 'not',
          label: 'Notlar',
          type: 'textarea',
          placeholder: 'Ek notlar (opsiyonel)'
        }
      ];

      formSection.innerHTML = '';
      const form = document.createElement('div');
      form.className = 'space-y-4';

      fields.forEach(field => {
        const div = document.createElement('div');
        const label = document.createElement('label');
        label.textContent = field.label;
        label.className = `block mb-1 text-sm font-medium text-gray-700 ${field.class || ''}`;

        let input;
        if (field.type === 'select') {
          input = document.createElement('select');
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          field.options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.value == formData[field.key]) option.selected = true;
            input.appendChild(option);
          });
        } else if (field.type === 'textarea') {
          input = document.createElement('textarea');
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          input.rows = 4;
          input.placeholder = field.placeholder || '';
        } else {
          input = document.createElement('input');
          input.type = field.type;
          input.placeholder = field.placeholder || '';
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          if (field.readonly) input.readOnly = true;
        }

        input.id = field.key;
        input.value = formData[field.key] || '';

        if (field.key === 'tonaj' || field.key === 'fiyat') {
          input.addEventListener('input', () => {
            let val = input.value.replace(/[^0-9,]/g, '');
            if (val) {
              const parts = val.split(',');
              if (parts[0]) {
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                input.value = parts.join(',');
              }
            }
            if (field.key === 'tonaj') {
              const ton = parseFormattedNumber(input.value);
              if (!isNaN(ton) && ton > 0) {
                document.getElementById('net_tonaj').value = formatNumber((ton * 0.98).toFixed(0));
              } else {
                document.getElementById('net_tonaj').value = '';
              }
            }
            updateTotalCost();
          });
        }

        div.appendChild(label);
        div.appendChild(input);
        form.appendChild(div);
      });

      formSection.appendChild(form);
    }

    function updateTotalCost() {
      const price = parseFormattedNumber(document.getElementById('fiyat').value);
      const netTon = parseFormattedNumber(document.getElementById('net_tonaj').value);
      const totalCostField = document.getElementById('total_cost');
      if (!isNaN(price) && !isNaN(netTon) && price > 0 && netTon > 0) {
        formData.total_cost = formatNumber((price * netTon).toFixed(0)) + ' ₺';
        totalCostField.value = formData.total_cost;
      } else {
        formData.total_cost = '';
        totalCostField.value = '';
      }
    }

    // Second Transaction Form (Multiple Products)
    function renderMultiForm() {
      console.log('Rendering multi product form...');
      const formSection = document.getElementById('multi-form-section');
      formSection.innerHTML = '';
      const form = document.createElement('div');
      form.className = 'space-y-4';

      // Shared fields (Plaka, Tarih, Not)
      const sharedFields = [
        {
          key: 'plaka',
          label: 'Tır Plakası',
          type: 'text',
          placeholder: 'Örn: 34 ABC 123'
        },
        {
          key: 'tarih',
          label: 'Teslimat Tarihi',
          type: 'date'
        },
        {
          key: 'not',
          label: 'Notlar',
          type: 'textarea',
          placeholder: 'Ek notlar (opsiyonel)'
        }
      ];

      sharedFields.forEach(field => {
        const div = document.createElement('div');
        const label = document.createElement('label');
        label.textContent = field.label;
        label.className = 'block mb-1 text-sm font-medium text-gray-700';

        let input;
        if (field.type === 'textarea') {
          input = document.createElement('textarea');
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          input.rows = 4;
          input.placeholder = field.placeholder || '';
        } else {
          input = document.createElement('input');
          input.type = field.type;
          input.placeholder = field.placeholder || '';
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
        }

        input.id = field.key;
        input.value = formData[field.key] || '';

        // Add input event listener for shared fields to update formData
        input.addEventListener('input', () => {
          formData[field.key] = input.value.trim();
          console.log(`Updated formData.${field.key}:`, formData[field.key]);
        });

        div.appendChild(label);
        div.appendChild(input);
        form.appendChild(div);
      });

      // Product rows
      const productContainer = document.createElement('div');
      productContainer.id = 'product-rows';
      formData.products = formData.products.length ? formData.products : [{ urun: '', tonaj: '', net_tonaj: '', fiyat: '', total_cost: '' }];
      formData.products.forEach((product, index) => {
        addProductRow(productContainer, index, product);
      });

      form.appendChild(productContainer);
      formSection.appendChild(form);
    }

    function addProductRow(container, index, product = { urun: '', tonaj: '', net_tonaj: '', fiyat: '', total_cost: '' }) {
      const row = document.createElement('div');
      row.className = 'product-row';
      row.dataset.index = index;

      const fields = [
        {
          key: `urun_${index}`,
          label: 'Ürün',
          type: 'select',
          options: products.map(p => ({ value: p.ID, text: p.NAME }))
        },
        {
          key: `tonaj_${index}`,
          label: 'Brüt Tonaj (ton)',
          type: 'text',
          placeholder: 'Örn: 12.500'
        },
        {
          key: `net_tonaj_${index}`,
          label: 'Net Tonaj (%2 fire)',
          type: 'text',
          placeholder: 'Örn: 12.250'
        },
        {
          key: `fiyat_${index}`,
          label: 'Birim Fiyat (₺)',
          type: 'text',
          placeholder: 'Örn: 8,75'
        },
        {
          key: `total_cost_${index}`,
          label: 'Toplam Maliyet',
          type: 'text',
          placeholder: 'Otomatik hesaplanır',
          readonly: true,
          class: 'total-cost-value'
        }
      ];

      fields.forEach(field => {
        const div = document.createElement('div');
        const label = document.createElement('label');
        label.textContent = field.label;
        label.className = `block mb-1 text-sm font-medium text-gray-700 ${field.class || ''}`;

        let input;
        if (field.type === 'select') {
          input = document.createElement('select');
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          field.options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.value == product.urun) option.selected = true;
            input.appendChild(option);
          });
        } else {
          input = document.createElement('input');
          input.type = field.type;
          input.placeholder = field.placeholder || '';
          input.className = 'input-field w-full p-3 border border-gray-300 rounded-lg';
          if (field.readonly) input.readOnly = true;
          input.value = product[field.key.split('_')[0]] || '';
        }

        input.id = field.key;

        if (field.key.includes('tonaj') || field.key.includes('fiyat')) {
          input.addEventListener('input', () => {
            let val = input.value.replace(/[^0-9,]/g, '');
            if (val) {
              const parts = val.split(',');
              if (parts[0]) {
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                input.value = parts.join(',');
              }
            }
            if (field.key.includes('tonaj') && !field.key.includes('net')) {
              const ton = parseFormattedNumber(input.value);
              if (!isNaN(ton) && ton > 0) {
                document.getElementById(`net_tonaj_${index}`).value = formatNumber((ton * 0.98).toFixed(0));
                formData.products[index].net_tonaj = document.getElementById(`net_tonaj_${index}`).value;
              } else {
                document.getElementById(`net_tonaj_${index}`).value = '';
                formData.products[index].net_tonaj = '';
              }
            }
            formData.products[index][field.key.split('_')[0]] = input.value;
            updateMultiTotalCost(index);
          });
        } else if (field.key.includes('urun')) {
          input.addEventListener('change', () => {
            formData.products[index].urun = input.value;
          });
        }

        div.appendChild(label);
        div.appendChild(input);
        row.appendChild(div);
      });

      // Delete button
      if (formData.products.length > 1) {
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'py-2 px-3 bg-red-600 text-white rounded-lg hover:bg-red-700';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.onclick = () => {
          formData.products.splice(index, 1);
          renderMultiForm();
        };
        row.appendChild(deleteBtn);
      }

      container.appendChild(row);
    }

    function updateMultiTotalCost(index) {
      const price = parseFormattedNumber(document.getElementById(`fiyat_${index}`).value);
      const netTon = parseFormattedNumber(document.getElementById(`net_tonaj_${index}`).value);
      const totalCostField = document.getElementById(`total_cost_${index}`);
      if (!isNaN(price) && !isNaN(netTon) && price > 0 && netTon > 0) {
        formData.products[index].total_cost = formatNumber((price * netTon).toFixed(0)) + ' ₺';
        totalCostField.value = formData.products[index].total_cost;
      } else {
        formData.products[index].total_cost = '';
        totalCostField.value = '';
      }
    }

    // Summary
    function showSummary() {
      console.log('Showing summary...', formData);
      let valid = true;

      if (currentStep === 'form') {
        const fields = ['urun', 'plaka', 'tonaj', 'net_tonaj', 'fiyat', 'tarih'];
        fields.forEach(field => {
          const input = document.getElementById(field);
          const val = input.value.trim();
          if (!val) {
            valid = false;
            Swal.fire('Hata!', `${input.previousElementSibling.textContent} alanı boş bırakılamaz.`, 'error');
            return;
          }
          formData[field] = val;
        });
        formData.not = document.getElementById('not').value.trim();
        if (!formData.supplierId || !formData.buyerId) {
          valid = false;
          Swal.fire('Hata!', 'Tedarikçi veya alıcı seçimi eksik.', 'error');
          return;
        }
        formData.products = [{
          urun: formData.urun,
          tonaj: formData.tonaj,
          net_tonaj: formData.net_tonaj,
          fiyat: formData.fiyat,
          total_cost: formData.total_cost
        }];
      } else if (currentStep === 'multi') {
        const sharedFields = ['plaka', 'tarih'];
        sharedFields.forEach(field => {
          const input = document.getElementById(field);
          const val = input.value.trim();
          if (!val) {
            valid = false;
            Swal.fire('Hata!', `${input.previousElementSibling.textContent} alanı boş bırakılamaz.`, 'error');
            return;
          }
          formData[field] = val;
        });
        formData.not = document.getElementById('not').value.trim();
        formData.products.forEach((product, index) => {
          const productFields = ['urun', 'tonaj', 'net_tonaj', 'fiyat'];
          productFields.forEach(key => {
            const input = document.getElementById(`${key}_${index}`);
            const val = input.value.trim();
            if (!val) {
              valid = false;
              Swal.fire('Hata!', `Ürün ${index + 1}: ${input.previousElementSibling.textContent} alanı boş bırakılamaz.`, 'error');
              return;
            }
            product[key] = val;
          });
        });
        if (!formData.supplierId || !formData.buyerId) {
          valid = false;
          Swal.fire('Hata!', 'Tedarikçi veya alıcı seçimi eksik.', 'error');
          return;
        }
      }

      if (!valid) return;

      const totalCost = formData.products.reduce((sum, p) => sum + parseFormattedNumber(p.total_cost), 0);

      const summaryContainer = document.getElementById('summary');
      summaryContainer.innerHTML = `
        <div class="summary-card">
          <h2 class="summary-title mb-6">Alım Özeti</h2>
          <div class="space-y-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-700 mb-3">Taraflar</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <span class="summary-label block text-sm">Tedarikçi</span>
                  <span class="summary-value">${formData.supplierName}</span>
                </div>
                <div>
                  <span class="summary-label block text-sm">Alıcı</span>
                  <span class="summary-value">${formData.buyerName}</span>
                </div>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-700 mb-3">Ürün ve Tonaj Bilgileri</h3>
              ${formData.products.map((p, index) => `
                <div class="border-t pt-2">
                  <h4 class="font-semibold text-gray-700 mb-2">Ürün ${index + 1}</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <span class="summary-label block text-sm">Ürün</span>
                      <span class="summary-value">${products.find(prod => prod.ID == p.urun)?.NAME || 'Bilinmeyen'}</span>
                    </div>
                    <div>
                      <span class="summary-label block text-sm">Brüt Tonaj</span>
                      <span class="summary-value">${p.tonaj} ton</span>
                    </div>
                    <div>
                      <span class="summary-label block text-sm">Net Tonaj</span>
                      <span class="summary-value">${p.net_tonaj} ton</span>
                    </div>
                    <div>
                      <span class="summary-label block text-sm">Birim Fiyat</span>
                      <span class="summary-value">${p.fiyat} ₺</span>
                    </div>
                    <div>
                      <span class="summary-label block text-sm">Toplam Maliyet</span>
                      <span class="summary-value total-cost-value">${p.total_cost}</span>
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-700 mb-3">Lojistik Bilgileri</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <span class="summary-label block text-sm">Tır Plakası</span>
                  <span class="summary-value">${formData.plaka}</span>
                </div>
                <div>
                  <span class="summary-label block text-sm">Teslimat Tarihi</span>
                  <span class="summary-value">${formData.tarih}</span>
                </div>
              </div>
            </div>
            ${formData.not ? `
              <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Notlar</h3>
                <div>
                  <span class="summary-label block text-sm">Notlar</span>
                  <span class="summary-value">${formData.not}</span>
                </div>
              </div>
            ` : ''}
            <div>
              <h3 class="text-lg font-semibold text-gray-700 mb-3">Genel Toplam Maliyet</h3>
              <div class="total-cost">${formatNumber(totalCost.toFixed(0))} ₺</div>
            </div>
            <div class="flex gap-4">
              <button class="w-full py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700" onclick="goToStep('${currentStep}')">Geri</button>
              <button class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700" onclick="confirmSave()">Kaydet</button>
            </div>
          </div>
        </div>
      `;
      goToStep('summary');
    }

    // Confirm save
    function confirmSave() {
      Swal.fire({
        title: 'Emin misiniz?',
        text: 'Bu işlemi kaydetmek istediğinizden emin misiniz?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'Hayır'
      }).then(result => {
        if (result.isConfirmed) {
          saveTransaction();
        }
      });
    }

    // Save to database
    function saveTransaction() {
      console.log('Saving transaction...', formData);
      const transactions = formData.products.map(product => ({
        TEDARIKCI_ID: formData.supplierId,
        ALICI_ID: formData.buyerId,
        URUN_ID: product.urun,
        PLAKA: formData.plaka,
        FIRESIZ_TON: parseFormattedNumber(product.tonaj),
        FIRELI_TON: parseFormattedNumber(product.net_tonaj),
        BIRIM_FIYATI: parseFormattedNumber(product.fiyat),
        TARIH: formData.tarih,
        ACIKLAMA: formData.not
      }));

      fetch('api/api.php?action=save_transaction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transactions })
      })
      .then(response => {
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        return response.json();
      })
      .then(data => {
        if (data.success) {
          Swal.fire('Başarılı!', 'Kayıt başarılı!', 'success').then(() => {
            formData = { supplierId: null, supplierName: '', buyerId: null, buyerName: '', products: [], plaka: '', tarih: '', not: '' };
            goToStep('supplier');
          });
        } else {
          console.error('Save transaction failed:', data.message);
          Swal.fire('Hata!', data.message || 'Kayıt sırasında hata oluştu.', 'error');
        }
      })
      .catch(error => {
        console.error('Error saving transaction:', error);
        Swal.fire('Hata!', 'Kayıt sırasında hata oluştu: ' + error.message, 'error');
      });
    }

    // Step navigation
    function goToStep(step) {
      console.log('Navigating to step:', step);
      currentStep = step;
      document.getElementById('supplier-selection').classList.add('hidden');
      document.getElementById('buyer-selection').classList.add('hidden');
      document.getElementById('transaction-form').classList.add('hidden');
      document.getElementById('multi-product-form').classList.add('hidden');
      document.getElementById('summary').classList.add('hidden');

      if (step === 'supplier') {
        document.getElementById('supplier-selection').classList.remove('hidden');
      } else if (step === 'buyer') {
        document.getElementById('buyer-selection').classList.remove('hidden');
      } else if (step === 'form') {
        document.getElementById('transaction-form').classList.remove('hidden');
        renderForm();
      } else if (step === 'multi') {
        document.getElementById('multi-product-form').classList.remove('hidden');
        renderMultiForm();
      } else if (step === 'summary') {
        document.getElementById('summary').classList.remove('hidden');
      }
    }

    // Event listeners
    document.getElementById('buyer-back-btn').onclick = () => goToStep('supplier');
    document.getElementById('form-back-btn').onclick = () => goToStep('buyer');
    document.getElementById('form-multi-btn').onclick = () => goToStep('multi');
    document.getElementById('form-review-btn').onclick = showSummary;
    document.getElementById('multi-back-btn').onclick = () => goToStep('form');
    document.getElementById('multi-review-btn').onclick = showSummary;
    document.getElementById('add-product-btn').onclick = () => {
      formData.products.push({ urun: '', tonaj: '', net_tonaj: '', fiyat: '', total_cost: '' });
      renderMultiForm();
    };
  </script>
</body>
</html>