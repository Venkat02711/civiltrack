/**
 * CivilTrack - Frontend Interactive Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // Modal dialog open/close helpers
    window.openModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close when clicking backdrop
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close on Escape key press
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });

    // Material purchase amount calculation (Quantity * Unit Price)
    function initMaterialCalculator() {
        const qtyInputs = document.querySelectorAll('.mat-qty-input');
        const priceInputs = document.querySelectorAll('.mat-price-input');

        function updateMaterialTotal(context) {
            const container = context.closest('form') || document;
            const qtyEl = container.querySelector('.mat-qty-input');
            const priceEl = container.querySelector('.mat-price-input');
            const totalEl = container.querySelector('.mat-total-input');

            if (qtyEl && priceEl && totalEl) {
                const qty = parseFloat(qtyEl.value) || 0;
                const price = parseFloat(priceEl.value) || 0;
                const total = (qty * price).toFixed(2);
                totalEl.value = total;
            }
        }

        qtyInputs.forEach(input => {
            input.addEventListener('input', function () { updateMaterialTotal(this); });
        });

        priceInputs.forEach(input => {
            input.addEventListener('input', function () { updateMaterialTotal(this); });
        });
    }
    initMaterialCalculator();

    // Attendance wage preview calculation
    function initAttendanceCalculator() {
        const workerSelect = document.getElementById('att_worker_select');
        const statusRadios = document.querySelectorAll('input[name="status"]');
        const wageDisplay = document.getElementById('att_calculated_wage');
        const defaultWageInput = document.getElementById('att_default_wage');

        if (!workerSelect || !wageDisplay) return;

        function recalculateAttendanceWage() {
            const selectedOption = workerSelect.options[workerSelect.selectedIndex];
            const defaultWage = selectedOption ? (parseFloat(selectedOption.getAttribute('data-wage')) || 0) : 0;
            
            if (defaultWageInput) {
                defaultWageInput.value = defaultWage;
            }

            let status = 'Present';
            const checkedStatus = document.querySelector('input[name="status"]:checked');
            if (checkedStatus) {
                status = checkedStatus.value;
            }

            let calcWage = 0;
            if (status === 'Present') {
                calcWage = defaultWage;
            } else if (status === 'Half Day') {
                calcWage = defaultWage / 2;
            } else if (status === 'Absent') {
                calcWage = 0;
            }

            wageDisplay.value = calcWage.toFixed(2);
            const previewText = document.getElementById('wage_preview_text');
            if (previewText) {
                previewText.textContent = '₹ ' + calcWage.toFixed(2);
            }
        }

        workerSelect.addEventListener('change', recalculateAttendanceWage);
        statusRadios.forEach(radio => {
            radio.addEventListener('change', recalculateAttendanceWage);
        });

        recalculateAttendanceWage();
    }
    initAttendanceCalculator();

    // Batch attendance row wage updater
    window.updateBatchRowWage = function (radio) {
        const row = radio.closest('tr');
        if (!row) return;

        const defaultWage = parseFloat(row.getAttribute('data-daily-wage')) || 0;
        const status = radio.value;
        const wageSpan = row.querySelector('.row-calculated-wage');
        const wageHidden = row.querySelector('.row-calculated-wage-input');

        let wage = 0;
        if (status === 'Present') {
            wage = defaultWage;
        } else if (status === 'Half Day') {
            wage = defaultWage / 2;
        } else if (status === 'Absent') {
            wage = 0;
        }

        if (wageSpan) wageSpan.textContent = '₹ ' + wage.toFixed(2);
        if (wageHidden) wageHidden.value = wage.toFixed(2);
    };

    // Client-side table quick search
    const tableSearchInputs = document.querySelectorAll('.table-search-input');
    tableSearchInputs.forEach(input => {
        input.addEventListener('keyup', function () {
            const tableId = this.getAttribute('data-target-table');
            const table = document.getElementById(tableId);
            if (!table) return;

            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });

    // Auto-dismiss alert notifications
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) alert.parentNode.removeChild(alert);
            }, 500);
        }, 5000);
    });
});
