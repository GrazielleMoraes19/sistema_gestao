// Funções auxiliares JavaScript

// Formatar CPF
function formatCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return cpf;
    return cpf.substring(0, 3) + '.' + cpf.substring(3, 6) + '.' + cpf.substring(6, 9) + '-' + cpf.substring(9);
}

// Formatar moeda
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Formatar data
function formatDate(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// Formatar hora
function formatTime(time) {
    if (!time) return '';
    return time.substring(0, 5);
}

// Validar email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validar CPF
function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    
    if (cpf.length !== 11) return false;
    
    // Verificar se todos os dígitos são iguais
    if (/^(\d)\1{10}$/.test(cpf)) return false;
    
    // Calcular primeiro dígito verificador
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf[i]) * (10 - i);
    }
    let digit1 = 11 - (sum % 11);
    digit1 = digit1 >= 10 ? 0 : digit1;
    
    if (parseInt(cpf[9]) !== digit1) return false;
    
    // Calcular segundo dígito verificador
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf[i]) * (11 - i);
    }
    let digit2 = 11 - (sum % 11);
    digit2 = digit2 >= 10 ? 0 : digit2;
    
    return parseInt(cpf[10]) === digit2;
}

// Mostrar notificação
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Confirmar ação
function confirmAction(message) {
    return confirm(message);
}

// Fazer requisição AJAX
async function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erro na requisição:', error);
        showNotification('Erro ao processar requisição', 'error');
        return null;
    }
}

// Validar formulário
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Limpar formulário
function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
}

// Máscara de entrada
function applyMask(input, mask) {
    input.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        let maskedValue = '';
        let maskIndex = 0;
        
        for (let i = 0; i < value.length && maskIndex < mask.length; i++) {
            if (mask[maskIndex] === '#') {
                maskedValue += value[i];
                maskIndex++;
            } else {
                maskedValue += mask[maskIndex];
                maskIndex++;
                if (mask[maskIndex] === '#') {
                    maskedValue += value[i];
                    maskIndex++;
                }
            }
        }
        
        this.value = maskedValue;
    });
}

// Inicializar máscaras de CPF
document.addEventListener('DOMContentLoaded', function() {
    const cpfInputs = document.querySelectorAll('[data-mask="cpf"]');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = formatCPF(this.value);
        });
    });
    
    // Validar CPF em tempo real
    cpfInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !isValidCPF(this.value)) {
                this.classList.add('error');
                showNotification('CPF inválido', 'warning');
            } else {
                this.classList.remove('error');
            }
        });
    });
});

// Exportar para PDF (usando biblioteca externa)
function exportToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    if (!element) {
        showNotification('Elemento não encontrado', 'error');
        return;
    }
    
    // Usar html2pdf se disponível
    if (typeof html2pdf !== 'undefined') {
        const opt = {
            margin: 10,
            filename: filename || 'documento.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };
        html2pdf().set(opt).from(element).save();
    } else {
        showNotification('Biblioteca de PDF não disponível', 'warning');
    }
}

// Imprimir elemento
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        showNotification('Elemento não encontrado', 'error');
        return;
    }
    
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(element.innerHTML);
    printWindow.document.close();
    printWindow.print();
}

// Logout
function logout() {
    if (confirmAction('Tem certeza que deseja sair?')) {
        window.location.href = 'logout.php';
    }
}

// Tema escuro/claro (opcional)
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    
    if (isDark) {
        html.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
}

// Carregar tema salvo
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
});
