<!-- src/views/sales/SalesQuotationPrint.vue -->
<template>
    <div class="print-container">
      <div class="print-actions" v-if="!isPrinting">
        <button class="btn btn-primary" @click="printQuotation">
          <i class="fas fa-print"></i> Cetak
        </button>
        <button class="btn btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
      </div>
      
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Memuat data penawaran...
      </div>
      
      <div v-else-if="!quotation" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Penawaran tidak ditemukan</h3>
        <p>Penawaran yang Anda cari mungkin telah dihapus atau tidak ada.</p>
        <button class="btn btn-primary" @click="goBack">
          Kembali ke daftar penawaran
        </button>
      </div>
      
      <div v-else class="quotation-document">
        <!-- Company Header -->
        <div class="company-header">
          <div class="company-logo">
            <img src="/images/company-logo.png" alt="Company Logo" />
          </div>
          <div class="company-info">
            <h1>PT. Nama Perusahaan</h1>
            <p>Jl. Contoh Alamat No. 123, Jakarta Selatan</p>
            <p>Telp: (021) 123-4567 | Email: info@example.com</p>
            <p>Website: www.example.com</p>
          </div>
        </div>
        
        <!-- Document Title -->
        <div class="document-title">
          <h2>PENAWARAN HARGA</h2>
          <div class="doc-number">No: {{ quotation.quotation_number }}</div>
        </div>
        
        <!-- Customer & Quotation Info -->
        <div class="info-section">
          <div class="customer-section">
            <h3>Kepada:</h3>
            <div class="info-box">
              <p class="customer-name">{{ quotation.customer.name }}</p>
              <p>{{ quotation.customer.address || 'Alamat tidak tersedia' }}</p>
              <p v-if="quotation.customer.phone">Telp: {{ quotation.customer.phone }}</p>
              <p v-if="quotation.customer.email">Email: {{ quotation.customer.email }}</p>
              <p v-if="quotation.customer.contact_person">
                <strong>Up:</strong> {{ quotation.customer.contact_person }}
              </p>
            </div>
          </div>
          
          <div class="quotation-meta">
            <table class="meta-table">
              <tr>
                <td>Tanggal Penawaran</td>
                <td>:</td>
                <td>{{ formatDate(quotation.quotation_date) }}</td>
              </tr>
              <tr>
                <td>Berlaku Hingga</td>
                <td>:</td>
                <td>{{ formatDate(quotation.validity_date) }}</td>
              </tr>
              <tr v-if="quotation.payment_terms">
                <td>Syarat Pembayaran</td>
                <td>:</td>
                <td>{{ quotation.payment_terms }}</td>
              </tr>
              <tr v-if="quotation.delivery_terms">
                <td>Syarat Pengiriman</td>
                <td>:</td>
                <td>{{ quotation.delivery_terms }}</td>
              </tr>
            </table>
          </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-section">
          <table class="items-table">
            <thead>
              <tr>
                <th class="no-column">No</th>
                <th class="desc-column">Deskripsi Item</th>
                <th class="qty-column">Jumlah</th>
                <th class="uom-column">Satuan</th>
                <th class="price-column">Harga Satuan</th>
                <th class="discount-column">Diskon</th>
                <th class="total-column">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in quotation.salesQuotationLines" :key="line.line_id">
                <td class="center">{{ index + 1 }}</td>
                <td>
                  <div class="item-description">
                    <div class="item-name">{{ line.item.name }}</div>
                    <div class="item-code">{{ line.item.item_code }}</div>
                  </div>
                </td>
                <td class="right">{{ formatNumber(line.quantity) }}</td>
                <td class="center">{{ getUomSymbol(line.uom_id) }}</td>
                <td class="right">{{ formatCurrency(line.unit_price) }}</td>
                <td class="right">{{ line.discount ? formatCurrency(line.discount) : '-' }}</td>
                <td class="right">{{ formatCurrency(line.total) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" rowspan="3"></td>
                <td class="right">Subtotal:</td>
                <td class="right">{{ formatCurrency(calculateSubtotal()) }}</td>
              </tr>
              <tr>
                <td class="right">Pajak:</td>
                <td class="right">{{ formatCurrency(calculateTotalTax()) }}</td>
              </tr>
              <tr class="grand-total">
                <td class="right">Total:</td>
                <td class="right">{{ formatCurrency(calculateGrandTotal()) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
        
        <!-- Notes Section -->
        <div class="notes-section">
          <h3>Catatan & Syarat:</h3>
          <ol>
            <li>Harga belum termasuk PPN {{ calculateTaxPercentage() }}%.</li>
            <li>Penawaran berlaku hingga tanggal {{ formatDate(quotation.validity_date) }}.</li>
            <li v-if="quotation.payment_terms">Pembayaran: {{ quotation.payment_terms }}.</li>
            <li v-if="quotation.delivery_terms">Pengiriman: {{ quotation.delivery_terms }}.</li>
            <li>Spesifikasi teknis sesuai dengan yang disepakati.</li>
          </ol>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
          <div class="signature-box">
            <p>Hormat kami,</p>
            <p>PT. Nama Perusahaan</p>
            <div class="signature-placeholder"></div>
            <p class="signatory">(Nama Penandatangan)</p>
            <p>Sales Manager</p>
          </div>
          
          <div class="signature-box customer">
            <p>Disetujui oleh,</p>
            <p>{{ quotation.customer.name }}</p>
            <div class="signature-placeholder"></div>
            <p class="signatory">(Nama & Jabatan)</p>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="document-footer">
          <p>Terima kasih atas kepercayaan Anda kepada perusahaan kami.</p>
        </div>
      </div>
    </div>
  </template>
  
  <script>
import { ref, onMounted } from 'vue';

  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'SalesQuotationPrint',
    setup() {
      const router = useRouter();
      const route = useRoute();
      
      // Data
      const quotation = ref(null);
      const unitOfMeasures = ref([]);
      const isLoading = ref(true);
      const isPrinting = ref(false);
      
      // Load quotation and reference data
      const loadData = async () => {
        isLoading.value = true;
        
        try {
          // Load unit of measures for reference
          const uomResponse = await axios.get('/api/unit-of-measures');
          unitOfMeasures.value = uomResponse.data.data;
          
          // Load quotation details
          const quotationResponse = await axios.get(`/api/quotations/${route.params.id}`);
          quotation.value = quotationResponse.data.data;
        } catch (error) {
          console.error('Error loading quotation:', error);
          quotation.value = null;
        } finally {
          isLoading.value = false;
        }
      };
      
      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'long',
          year: 'numeric'
        });
      };
      
      // Format number
      const formatNumber = (value) => {
        return new Intl.NumberFormat('id-ID').format(value || 0);
      };
      
      // Format currency
      const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(value || 0);
      };
      
      // Get UOM symbol
      const getUomSymbol = (uomId) => {
        const uom = unitOfMeasures.value.find(u => u.uom_id === uomId);
        return uom ? uom.symbol : '-';
      };
      
      // Calculate subtotal of all lines
      const calculateSubtotal = () => {
        if (!quotation.value || !quotation.value.salesQuotationLines) return 0;
        return quotation.value.salesQuotationLines.reduce((sum, line) => sum + (line.subtotal || 0), 0);
      };
      
      // Calculate total tax of all lines
      const calculateTotalTax = () => {
        if (!quotation.value || !quotation.value.salesQuotationLines) return 0;
        return quotation.value.salesQuotationLines.reduce((sum, line) => sum + (line.tax || 0), 0);
      };
      
      // Calculate grand total of all lines
      const calculateGrandTotal = () => {
        if (!quotation.value || !quotation.value.salesQuotationLines) return 0;
        return quotation.value.salesQuotationLines.reduce((sum, line) => sum + (line.total || 0), 0);
      };
      
      // Calculate tax percentage (assuming 11% PPN in Indonesia)
      const calculateTaxPercentage = () => {
        return 11;
      };
      
      // Navigation methods
      const goBack = () => {
        router.push(`/quotations/${route.params.id}`);
      };
      
      // Print the quotation
      const printQuotation = () => {
        isPrinting.value = true;
        setTimeout(() => {
          window.print();
          isPrinting.value = false;
        }, 300);
      };
      
      // Handle print event
      const handlePrintEvent = () => {
        isPrinting.value = true;
        setTimeout(() => {
          isPrinting.value = false;
        }, 300);
      };
      
      onMounted(() => {
        loadData();
        window.addEventListener('beforeprint', handlePrintEvent);
        window.addEventListener('afterprint', () => {
          isPrinting.value = false;
        });
      });
      
      return {
        quotation,
        isLoading,
        isPrinting,
        formatDate,
        formatNumber,
        formatCurrency,
        getUomSymbol,
        calculateSubtotal,
        calculateTotalTax,
        calculateGrandTotal,
        calculateTaxPercentage,
        goBack,
        printQuotation
      };
    }
  };
  </script>
  
  <style>
  /* General styles */
  .print-container {
    max-width: 210mm; /* A4 width */
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
    color: #333;
  }
  
  .print-actions {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
  }
  
  .btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  
  .btn-primary {
    background-color: #2563eb;
    color: white;
  }
  
  .btn-secondary {
    background-color: #e5e7eb;
    color: #1f2937;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 0;
    color: #64748b;
  }
  
  .loading-indicator i {
    margin-right: 10px;
  }
  
  .empty-state {
    text-align: center;
    padding: 50px 0;
  }
  
  .empty-icon {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 20px;
  }
  
  /* Quotation Document Styles */
  .quotation-document {
    background-color: white;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .company-header {
    display: flex;
    margin-bottom: 30px;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 15px;
  }
  
  .company-logo {
    width: 150px;
    margin-right: 20px;
  }
  
  .company-logo img {
    max-width: 100%;
    height: auto;
  }
  
  .company-info h1 {
    font-size: 18px;
    margin: 0 0 5px 0;
    color: #1e293b;
  }
  
  .company-info p {
    margin: 3px 0;
    font-size: 12px;
    color: #64748b;
  }
  
  .document-title {
    text-align: center;
    margin-bottom: 20px;
  }
  
  .document-title h2 {
    font-size: 20px;
    font-weight: bold;
    margin: 0 0 5px 0;
    color: #1e293b;
  }
  
  .doc-number {
    font-size: 14px;
    color: #64748b;
  }
  
  .info-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
  }
  
  .customer-section {
    width: 48%;
  }
  
  .customer-section h3,
  .quotation-meta h3 {
    font-size: 14px;
    margin: 0 0 5px 0;
  }
  
  .info-box {
    border: 1px solid #e5e7eb;
    padding: 10px;
    border-radius: 4px;
  }
  
  .customer-name {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 5px;
  }
  
  .info-box p {
    margin: 3px 0;
    font-size: 12px;
  }
  
  .quotation-meta {
    width: 48%;
  }
  
  .meta-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .meta-table td {
    padding: 5px;
    font-size: 12px;
    vertical-align: top;
  }
  
  .meta-table td:first-child {
    width: 120px;
  }
  
  .meta-table td:nth-child(2) {
    width: 10px;
  }
  
  .items-section {
    margin-bottom: 30px;
  }
  
  .items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }
  
  .items-table th,
  .items-table td {
    border: 1px solid #e5e7eb;
    padding: 8px;
  }
  
  .items-table th {
    background-color: #f8fafc;
    font-weight: bold;
    text-align: left;
    color: #1e293b;
  }
  
  .no-column {
    width: 40px;
  }
  
  .desc-column {
    width: 30%;
  }
  
  .qty-column,
  .uom-column {
    width: 80px;
  }
  
  .price-column,
  .discount-column,
  .total-column {
    width: 120px;
  }
  
  .center {
    text-align: center;
  }
  
  .right {
    text-align: right;
  }
  
  .item-description {
    margin-bottom: 5px;
  }
  
  .item-name {
    font-weight: bold;
  }
  
  .item-code {
    font-size: 10px;
    color: #64748b;
  }
  
  .items-table tfoot tr {
    background-color: #f8fafc;
  }
  
  .items-table tfoot td {
    padding: 8px;
    font-weight: bold;
  }
  
  .grand-total td {
    background-color: #dbeafe;
    color: #1e40af;
    font-size: 14px;
  }
  
  .notes-section {
    margin-bottom: 30px;
  }
  
  .notes-section h3 {
    font-size: 14px;
    margin: 0 0 10px 0;
  }
  
  .notes-section ol {
    margin: 0;
    padding-left: 20px;
    font-size: 12px;
  }
  
  .notes-section li {
    margin-bottom: 5px;
  }
  
  .signature-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
  }
  
  .signature-box {
    width: 200px;
    text-align: center;
  }
  
  .signature-box p {
    margin: 5px 0;
    font-size: 12px;
  }
  
  .signature-placeholder {
    height: 60px;
    margin: 15px 0;
    border-bottom: 1px solid #e5e7eb;
  }
  
  .signatory {
    font-weight: bold;
  }
  
  .document-footer {
    text-align: center;
    font-size: 12px;
    color: #64748b;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
  }
  
  /* Print specific styles */
  @media print {
    .print-actions {
      display: none !important;
    }
    
    .print-container {
      padding: 0;
      max-width: none;
    }
    
    .quotation-document {
      box-shadow: none;
      padding: 0;
    }
    
    @page {
      size: A4;
      margin: 1cm;
    }
    
    .signature-section,
    .document-footer {
      page-break-inside: avoid;
    }
    
    .items-section {
      page-break-before: auto;
    }
  }
  </style>
