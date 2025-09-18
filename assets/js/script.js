// OneClick Insurance - Custom JavaScript

// Show loading spinner
function showLoading() {
    document.getElementById('loadingSpinner').classList.remove('hidden');
}

// Hide loading spinner
function hideLoading() {
    document.getElementById('loadingSpinner').classList.add('hidden');
}

// Format currency input
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    if (value) {
        input.value = parseFloat(value).toFixed(2);
        calculateGST();
    }
}

// Calculate GST automatically
function calculateGST() {
    const commissionAmount = parseFloat(document.getElementById('commission_amount')?.value) || 0;
    const partnerSelect = document.getElementById('partner_id');
    
    if (!partnerSelect) return;
    
    const selectedOption = partnerSelect.options[partnerSelect.selectedIndex];
    const stateCode = selectedOption.getAttribute('data-state-code');
    
    let cgstAmount = 0, sgstAmount = 0, igstAmount = 0;
    
    // Check if same state (CGST + SGST) or different state (IGST)
    if (stateCode === '09') { // UP state code
        cgstAmount = commissionAmount * 0.09; // 9%
        sgstAmount = commissionAmount * 0.09; // 9%
        igstAmount = 0;
    } else {
        cgstAmount = 0;
        sgstAmount = 0;
        igstAmount = commissionAmount * 0.18; // 18%
    }
    
    const totalGst = cgstAmount + sgstAmount + igstAmount;
    const totalAmount = commissionAmount + totalGst;
    
    // Update form fields
    if (document.getElementById('cgst_amount')) {
        document.getElementById('cgst_amount').value = cgstAmount.toFixed(2);
    }
    if (document.getElementById('sgst_amount')) {
        document.getElementById('sgst_amount').value = sgstAmount.toFixed(2);
    }
    if (document.getElementById('igst_amount')) {
        document.getElementById('igst_amount').value = igstAmount.toFixed(2);
    }
    if (document.getElementById('total_gst')) {
        document.getElementById('total_gst').value = totalGst.toFixed(2);
    }
    if (document.getElementById('total_amount')) {
        document.getElementById('total_amount').value = totalAmount.toFixed(2);
    }
    
    // Update preview if exists
    updatePreview();
}

// Update bill preview
function updatePreview() {
    const preview = document.getElementById('billPreview');
    if (!preview) return;
    
    const commissionAmount = parseFloat(document.getElementById('commission_amount')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('total_amount')?.value) || 0;
    
    // Update preview amounts
    const previewCommission = preview.querySelector('.preview-commission');
    const previewTotal = preview.querySelector('.preview-total');
    
    if (previewCommission) previewCommission.textContent = '₹' + commissionAmount.toFixed(2);
    if (previewTotal) previewTotal.textContent = '₹' + totalAmount.toFixed(2);
}

// Form validation
function validateBillForm() {
    const requiredFields = ['partner_id', 'invoice_date', 'description', 'commission_amount'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field && !field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else if (field) {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Generate PDF
function generatePDF(billId) {
    showLoading();
    window.open(`../pdf/generate_pdf.php?bill_id=${billId}`, '_blank');
    setTimeout(hideLoading, 2000);
}

// Delete confirmation
function confirmDelete(item, id) {
    if (confirm(`Are you sure you want to delete this ${item}?`)) {
        window.location.href = `?delete=${id}`;
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Print invoice
function printInvoice() {
    window.print();
}

// Export to Excel (basic)
function exportToExcel(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let rowData = [];
        const cells = row.querySelectorAll('td, th');
        cells.forEach(cell => {
            rowData.push(cell.textContent.trim());
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'oneclick_bills_export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
