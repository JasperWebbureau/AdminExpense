class AdminExpenseAccountingForms {
    constructor(root = document) {
        this.root = root;
        this.onChange = this.onChange.bind(this);
        this.root.addEventListener('change', this.onChange);
        this.syncAll();
    }

    onChange(event) {
        if (event.target && event.target.name === 'reporting_type') {
            this.sync(event.target.closest('[data-admin-expense-accounting-form]'));
        }
    }

    syncAll() {
        this.root.querySelectorAll('[data-admin-expense-accounting-form]').forEach(form => this.sync(form));
    }

    sync(form) {
        if (!form) { return; }
        const type = form.elements.namedItem('reporting_type');
        const vat = form.elements.namedItem('vat_deductible_percentage');
        if (!type || !vat) { return; }
        const supportsVat = type.value === 'operating_cost';
        if (!supportsVat) {
            if (vat.value !== '0') { vat.dataset.previousPercentage = vat.value; }
            vat.value = '0';
        } else if (vat.value === '0' && vat.dataset.previousPercentage) {
            vat.value = vat.dataset.previousPercentage;
        }
        vat.readOnly = !supportsVat;
        vat.setAttribute('aria-disabled', supportsVat ? 'false' : 'true');
    }
}

new AdminExpenseAccountingForms();
