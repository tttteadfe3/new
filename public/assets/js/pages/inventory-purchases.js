class InventoryPurchasesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            purchases: [],
            categories: [],
            plans: [],
            selectedPurchase: null
        };
        this.initializeApp();
    }

    async initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.populateYearFilter();
        await this.loadInitialData();
    }

    cacheDOMElements() {
        this.dom = {
            yearFilter: document.getElementById('year-filter'),
            stockedFilter: document.getElementById('stocked-filter'),
            tableBody: document.getElementById('purchases-table-body'),
            tablePlaceholder: document.getElementById('table-placeholder'),
            modal: new bootstrap.Modal(document.getElementById('purchase-modal')),
            form: document.getElementById('purchase-form'),
            purchaseIdInput: document.getElementById('purchase-id'),
            purchaseDateInput: document.getElementById('purchase-date'),
            planSelect: document.getElementById('purchase-plan'),
            categorySelect: document.getElementById('purchase-category'),
            itemSelect: document.getElementById('purchase-item'),
            unitPriceInput: document.getElementById('purchase-unit-price'),
            quantityInput: document.getElementById('purchase-quantity'),
            supplierInput: document.getElementById('purchase-supplier'),
        };
    }

    setupEventListeners() {
        this.dom.yearFilter.addEventListener('change', () => this.loadPurchases());
        this.dom.stockedFilter.addEventListener('change', () => this.loadPurchases());

        this.dom.form.addEventListener('submit', (e) => { e.preventDefault(); this.handleSave(); });
        document.getElementById('purchase-modal').addEventListener('show.bs.modal', (e) => {
            const button = e.relatedTarget;
            const purchaseId = button.dataset.id;
            this.handleModalOpen(purchaseId);
        });

        this.dom.planSelect.addEventListener('change', () => this.handlePlanChange());
        this.dom.categorySelect.addEventListener('change', () => this.handleCategoryChange());

        this.dom.tableBody.addEventListener('click', (e) => {
            if (e.target.matches('.stock-in-btn')) this.handleStockIn(e.target.dataset.id);
            if (e.target.matches('.delete-btn')) this.handleDelete(e.target.dataset.id);
        });
    }

    populateYearFilter() {
        const currentYear = new Date().getFullYear();
        for (let i = currentYear + 1; i >= currentYear - 5; i--) {
            const option = new Option(`${i}년`, i);
            if (i === currentYear) option.selected = true;
            this.dom.yearFilter.add(option);
        }
    }

    async loadInitialData() {
        await Promise.all([this.loadCategories(), this.loadPlansForSelect()]);
        await this.loadPurchases();
    }

    async loadPurchases() {
        this.showTablePlaceholder(true, '목록을 불러오는 중입니다...');
        const year = this.dom.yearFilter.value;
        const is_stocked = this.dom.stockedFilter.value;
        try {
            const response = await this.apiCall(`/item-purchases?year=${year}&is_stocked=${is_stocked}`);
            this.state.purchases = response.data;
            this.renderTable();
        } catch (error) {
            Toast.error('구매 목록을 불러오는 데 실패했습니다.');
            this.showTablePlaceholder(true, `오류: ${this.sanitizeHTML(error.message)}`);
        }
    }

    async loadCategories() {
        try {
            const response = await this.apiCall('/item-categories');
            this.state.categories = response.data;
            this.renderCategorySelect();
        } catch (error) { Toast.error('분류 목록 로딩 실패'); }
    }

    async loadPlansForSelect() {
        const year = this.dom.yearFilter.value;
        try {
            const response = await this.apiCall(`/item-plans?year=${year}`);
            this.state.plans = response.data;
            let optionsHtml = '<option value="">선택 안함</option>';
            this.state.plans.forEach(plan => {
                optionsHtml += `<option value="${plan.id}">${this.sanitizeHTML(plan.item_name)} (계획)</option>`;
            });
            this.dom.planSelect.innerHTML = optionsHtml;
        } catch (error) { Toast.error('계획 목록 로딩 실패'); }
    }

    renderTable() {
        if (!this.state.purchases || this.state.purchases.length === 0) {
            this.showTablePlaceholder(true, '해당 조건에 맞는 구매 내역이 없습니다.');
            this.dom.tableBody.innerHTML = '';
            return;
        }

        this.showTablePlaceholder(false);
        this.dom.tableBody.innerHTML = this.state.purchases.map(p => `
            <tr>
                <td>${p.purchase_date}</td>
                <td>${this.sanitizeHTML(p.category_name)}</td>
                <td>${this.sanitizeHTML(p.item_name)}</td>
                <td>${Number(p.quantity).toLocaleString()}</td>
                <td>${Number(p.unit_price).toLocaleString()}</td>
                <td>${this.sanitizeHTML(p.supplier || '')}</td>
                <td>${p.is_stocked == 1 ? `<span class="badge bg-success">입고완료</span><br><small>${p.stocked_at.substring(0,10)}<br>${this.sanitizeHTML(p.stocker_name)}</small>` : '<span class="badge bg-warning">미입고</span>'}</td>
                <td>
                    ${p.is_stocked == 0 ? `
                    <button class="btn btn-sm btn-outline-primary stock-in-btn" data-id="${p.id}">입고확정</button>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#purchase-modal" data-id="${p.id}">수정</button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${p.id}">삭제</button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
    }

    renderCategorySelect() {
        let optionsHtml = '<option value="">분류 선택</option>';
        this.state.categories.forEach(cat => optionsHtml += `<option value="${cat.id}">${this.sanitizeHTML(cat.name)}</option>`);
        this.dom.categorySelect.innerHTML = optionsHtml;
    }

    async handleCategoryChange() {
        const categoryId = this.dom.categorySelect.value;
        this.dom.itemSelect.innerHTML = '<option value="">불러오는 중...</option>';
        if (!categoryId) {
            this.dom.itemSelect.innerHTML = '<option value="">분류를 먼저 선택하세요</option>';
            return;
        }
        try {
            const response = await this.apiCall(`/items?category_id=${categoryId}`);
            let itemsHtml = '<option value="">품목 선택</option>';
            response.data.forEach(item => itemsHtml += `<option value="${item.id}">${this.sanitizeHTML(item.name)}</option>`);
            this.dom.itemSelect.innerHTML = itemsHtml;
        } catch (error) {
             Toast.error('품목 로딩 실패');
             this.dom.itemSelect.innerHTML = '<option value="">오류</option>';
        }
    }

    handlePlanChange() {
        const planId = this.dom.planSelect.value;
        if (!planId) {
            this.resetFormFields();
            return;
        }
        const plan = this.state.plans.find(p => p.id == planId);
        if (plan) {
            this.dom.unitPriceInput.value = plan.unit_price;
            this.dom.quantityInput.value = plan.quantity;
            this.dom.categorySelect.value = plan.category_id;
            this.handleCategoryChange().then(() => {
                this.dom.itemSelect.value = plan.item_id;
            });
            this.disableFormFields(true);
        }
    }

    async handleModalOpen(purchaseId) {
        this.resetFormFields();
        this.disableFormFields(false);
        await this.loadPlansForSelect(); // Ensure plans are up-to-date for the current year filter

        if (purchaseId) {
            document.getElementById('purchase-modal-label').textContent = '구입 내역 수정';
            this.state.selectedPurchase = this.state.purchases.find(p => p.id == purchaseId);
            const p = this.state.selectedPurchase;
            this.dom.purchaseIdInput.value = p.id;
            this.dom.purchaseDateInput.value = p.purchase_date;
            this.dom.planSelect.value = p.plan_id || '';
            this.dom.unitPriceInput.value = p.unit_price;
            this.dom.quantityInput.value = p.quantity;
            this.dom.supplierInput.value = p.supplier;

            const itemResponse = await this.apiCall(`/items?category_id=${p.category_id}`);
            const items = itemResponse.data;
            this.dom.categorySelect.value = items.length > 0 ? items[0].category_id : '';
            await this.handleCategoryChange();
            this.dom.itemSelect.value = p.item_id;

        } else {
            document.getElementById('purchase-modal-label').textContent = '신규 구입 등록';
            this.dom.purchaseIdInput.value = '';
            this.dom.purchaseDateInput.value = new Date().toISOString().slice(0, 10);
        }
    }

    async handleSave() {
        const id = this.dom.purchaseIdInput.value;
        const url = id ? `/item-purchases/${id}` : '/item-purchases';
        const method = id ? 'PUT' : 'POST';

        const data = {
            purchase_date: this.dom.purchaseDateInput.value,
            plan_id: this.dom.planSelect.value || null,
            item_id: this.dom.itemSelect.value,
            unit_price: this.dom.unitPriceInput.value,
            quantity: this.dom.quantityInput.value,
            supplier: this.dom.supplierInput.value.trim(),
        };

        try {
            const response = await this.apiCall(url, { method, body: JSON.stringify(data) });
            Toast.success(response.message);
            this.dom.modal.hide();
            await this.loadPurchases();
        } catch (error) { Toast.error(`저장 실패: ${this.sanitizeHTML(error.message)}`); }
    }

    async handleStockIn(id) {
        const confirmed = await Swal.fire({ title: '입고 처리 하시겠습니까?', text: "재고가 즉시 증가하며, 이 작업은 되돌릴 수 없습니다.", icon: 'info', showCancelButton: true, confirmButtonText: '확인', cancelButtonText: '취소' });
        if (confirmed.isConfirmed) {
            try {
                const response = await this.apiCall(`/item-purchases/${id}/stock-in`, { method: 'POST' });
                Toast.success(response.message);
                await this.loadPurchases();
            } catch (error) { Toast.error(`처리 실패: ${this.sanitizeHTML(error.message)}`); }
        }
    }

    async handleDelete(id) {
        const confirmed = await Swal.fire({ title: '정말 삭제하시겠습니까?', text: "입고되지 않은 내역만 삭제할 수 있습니다.", icon: 'warning', showCancelButton: true, confirmButtonText: '삭제', cancelButtonText: '취소' });
        if (confirmed.isConfirmed) {
            try {
                const response = await this.apiCall(`/item-purchases/${id}`, { method: 'DELETE' });
                Toast.success(response.message);
                await this.loadPurchases();
            } catch (error) { Toast.error(`삭제 실패: ${this.sanitizeHTML(error.message)}`); }
        }
    }

    resetFormFields() {
        this.dom.form.reset();
        this.dom.itemSelect.innerHTML = '<option value="">분류를 먼저 선택하세요</option>';
    }

    disableFormFields(disabled) {
        this.dom.categorySelect.disabled = disabled;
        this.dom.itemSelect.disabled = disabled;
        this.dom.unitPriceInput.disabled = disabled;
        this.dom.quantityInput.disabled = disabled;
    }

    showTablePlaceholder(show, message = '') {
        this.dom.tablePlaceholder.style.display = show ? 'block' : 'none';
        if (show) this.dom.tablePlaceholder.innerHTML = `<p class="text-muted">${this.sanitizeHTML(message)}</p>`;
    }
}

document.addEventListener('DOMContentLoaded', () => { new InventoryPurchasesPage(); });
