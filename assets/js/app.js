/**
 * Sistema de Gestión Médica - JavaScript Utilities
 * Paginación, filtros y funcionalidades cliente-side
 */

// =====================================================
// PAGINACIÓN
// =====================================================
class Pagination {
    constructor(items, itemsPerPage = 10, containerId = 'content-container', paginationId = 'pagination-controls') {
        this.allItems = items;
        this.itemsPerPage = itemsPerPage;
        this.currentPage = 1;
        this.containerId = containerId;
        this.paginationId = paginationId;
        this.filteredItems = items;
    }

    get totalPages() {
        return Math.ceil(this.filteredItems.length / this.itemsPerPage);
    }

    get currentItems() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        return this.filteredItems.slice(start, end);
    }

    setItemsPerPage(count) {
        this.itemsPerPage = parseInt(count);
        this.currentPage = 1;
        this.render();
    }

    goToPage(page) {
        const pageNum = parseInt(page);
        if (pageNum >= 1 && pageNum <= this.totalPages) {
            this.currentPage = pageNum;
            this.render();
        }
    }

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.render();
        }
    }

    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.render();
        }
    }

    applyFilter(filterFn) {
        this.filteredItems = this.allItems.filter(filterFn);
        this.currentPage = 1;
        this.render();
    }

    resetFilter() {
        this.filteredItems = this.allItems;
        this.currentPage = 1;
        this.render();
    }

    renderControls() {
        const paginationContainer = document.getElementById(this.paginationId);
        if (!paginationContainer || this.totalPages <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, this.filteredItems.length);

        let html = '<div class="pagination">';

        // Botón anterior
        html += `<button class="pagination-btn" onclick="pagination.prevPage()" ${this.currentPage === 1 ? 'disabled' : ''}>
            ← Anterior
        </button>`;

        // Números de página
        const maxButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(this.totalPages, startPage + maxButtons - 1);

        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            html += `<button class="pagination-btn" onclick="pagination.goToPage(1)">1</button>`;
            if (startPage > 2) {
                html += `<span class="pagination-info">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                     onclick="pagination.goToPage(${i})">${i}</button>`;
        }

        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                html += `<span class="pagination-info">...</span>`;
            }
            html += `<button class="pagination-btn" onclick="pagination.goToPage(${this.totalPages})">${this.totalPages}</button>`;
        }

        // Botón siguiente
        html += `<button class="pagination-btn" onclick="pagination.nextPage()" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
            Siguiente →
        </button>`;

        // Info de items
        html += `<span class="pagination-info">
            Mostrando ${start}-${end} de ${this.filteredItems.length}
        </span>`;

        html += '</div>';
        paginationContainer.innerHTML = html;
    }

    // Este método debe ser sobrescrito por la implementación específica
    renderItems() {
        console.warn('renderItems() should be overridden');
    }

    render() {
        this.renderItems();
        this.renderControls();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// =====================================================
// UTILIDADES DE FILTRADO
// =====================================================
const FilterUtils = {
    byText: function (item, searchText, fields) {
        if (!searchText) return true;
        const text = searchText.toLowerCase();
        return fields.some(field => {
            const value = this.getNestedProperty(item, field);
            return value && String(value).toLowerCase().includes(text);
        });
    },

    bySelect: function (item, selectValue, field) {
        if (!selectValue || selectValue === '') return true;
        return this.getNestedProperty(item, field) == selectValue;
    },

    byDateRange: function (item, startDate, endDate, field) {
        if (!startDate && !endDate) return true;
        const itemDate = new Date(this.getNestedProperty(item, field));

        if (startDate && itemDate < new Date(startDate)) return false;
        if (endDate && itemDate > new Date(endDate + 'T23:59:59')) return false;

        return true;
    },

    getNestedProperty: function (obj, path) {
        return path.split('.').reduce((curr, prop) => curr?.[prop], obj);
    }
};

// =====================================================
// UTILIDADES DE EXPORTACIÓN
// =====================================================
const ExportUtils = {
    toCSV: function (data, filename = 'export.csv') {
        if (!data || data.length === 0) {
            alert('No hay datos para exportar');
            return;
        }

        // Obtener headers
        const headers = Object.keys(data[0]);

        // Crear CSV content
        let csv = headers.join(',') + '\n';

        data.forEach(row => {
            const values = headers.map(header => {
                const value = row[header] || '';
                // Escapar comillas y envolver en comillas si contiene coma o salto de línea
                const escaped = String(value).replace(/"/g, '""');
                return escaped.includes(',') || escaped.includes('\n') ? `"${escaped}"` : escaped;
            });
            csv += values.join(',') + '\n';
        });

        // Crear blob y descargar
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

// =====================================================
// UTILIDADES DE UI
// =====================================================
const UIUtils = {
    showLoading: function (message = 'Cargando...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="spinner-large"></div>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    },

    hideLoading: function () {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    },

    showToast: function (message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// =====================================================
// VALIDACIÓN DE FORMULARIOS
// =====================================================
const FormValidator = {
    validateEstrato: function (value) {
        const estrato = parseInt(value);
        return estrato >= 1 && estrato <= 6;
    },

    validateDocumento: function (value) {
        return value && value.length >= 5 && value.length <= 20;
    },

    validateRequired: function (value) {
        return value && value.trim().length > 0;
    },

    validateEmail: function (value) {
        if (!value) return true; // Email es opcional
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    },

    validatePhone: function (value) {
        if (!value) return true; // Teléfono es opcional
        const phoneRegex = /^[0-9]{7,15}$/;
        return phoneRegex.test(value.replace(/[\s\-]/g, ''));
    }
};
