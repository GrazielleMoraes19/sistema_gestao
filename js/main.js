// ========================================
// SISTEMA DE GESTAO RH - MAIN JAVASCRIPT
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initModals();
    initFormValidation();
    initAutoFormat();
    initConfirmDialogs();
    initTabs();
});

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const text = e.target.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = `
                position: absolute;
                background: #4a3728;
                color: white;
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 999;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            const rect = e.target.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - 35) + 'px';
            e.target._tooltip = tooltip;
        });
        el.addEventListener('mouseleave', (e) => {
            if (e.target._tooltip) {
                e.target._tooltip.remove();
            }
        });
    });
}

// Modals
function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalOpen;
            document.getElementById(modalId)?.classList.add('active');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
}

function openModal(id) {
    document.getElementById(id)?.classList.add('active');
}

function closeModal(id) {
    document.getElementById(id)?.classList.remove('active');
}

// Form Validation
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#a85444';
                    field.addEventListener('input', function handler() {
                        if (field.value.trim()) {
                            field.style.borderColor = '';
                            field.removeEventListener('input', handler);
                        }
                    });
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Preencha todos os campos obrigatórios.', 'danger');
            }
        });
    });
}

// Auto Format
function initAutoFormat() {
    // CPF
    document.querySelectorAll('.cpf-input').forEach(input => {
        input.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            this.value = v;
        });
    });

    // Currency
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            v = (parseInt(v) / 100).toFixed(2);
            this.value = v;
        });
    });

    // Phone
    document.querySelectorAll('.phone-input').forEach(input => {
        input.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            v = v.replace(/(\d{2})(\d)/, '($1) $2');
            v = v.replace(/(\d{5})(\d)/, '$1-$2');
            this.value = v;
        });
    });
}

// Confirm Dialogs
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
}

// Tabs
function initTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Alert System
function showAlert(message, type = 'info', duration = 4000) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} fade-in`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    `;
    alert.innerHTML = `
        <span style="flex:1">${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:18px;">&times;</button>
    `;
    document.body.appendChild(alert);
    
    if (duration > 0) {
        setTimeout(() => alert.remove(), duration);
    }
}

// AJAX Helper
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showAlert('Erro na comunicação com o servidor.', 'danger');
        return null;
    }
}

// PDF Export
function exportPDF(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = endpoint + (queryString ? '?' + queryString : '');
    window.open(url, '_blank');
}

// Date helpers
function getDaysInMonth(month, year) {
    return new Date(year, month, 0).getDate();
}

function formatDateBR(date) {
    const d = new Date(date);
    return d.toLocaleDateString('pt-BR');
}

// Table search
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    
    input.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Toggle sidebar on mobile
function toggleSidebar() {
    document.querySelector('.sidebar')?.classList.toggle('open');
}

// Export function
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"'));
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Real-time clock
function updateClock() {
    const el = document.getElementById('realtime-clock');
    if (el) {
        el.textContent = new Date().toLocaleTimeString('pt-BR');
    }
}
setInterval(updateClock, 1000);