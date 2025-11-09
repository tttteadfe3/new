class InventoryPlansPage extends BasePage {
    constructor() {
        super();
        this.state = {
            plans: [],
            categories: [],
            items: [],
            selectedPlan: null
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
            addPlanBtn: document.getElementById('add-plan-btn'),
            tableBody: document.getElementById('plans-table-body'),
            tablePlaceholder: document.getElementById('table-placeholder'),
            exportBtn: document.getElementById('export-btn'),
            modal: new bootstrap.Modal(document.getElementById('plan-modal')),
            form: document.getElementById('plan-form'),
            planIdInput: document.getElementById('plan-id'),
            planYearInput: document.getElementById('plan-year'),
            categorySelect: document.getElementById('plan-category'),
            itemSelect: document.getElementById('plan-item'),
            unitPriceInput: document.getElementById('plan-unit-price'),
            quantityInput: document.getElementById('plan-quantity'),
            budgetInput: document.getElementById('plan-budget'),
            noteTextarea: document.getElementById('plan-note'),
        };
    }

    setupEventListeners() {
        this.dom.yearFilter.addEventListener('change', () => this.loadPlans());
        this.dom.exportBtn.addEventListener('click', () => this.handleExport());

        this.dom.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSave();
        });

        document.getElementById('plan-modal').addEventListener('show.bs.modal', (e) => {
            const button = e.relatedTarget;
            const planId = button.dataset.id;
            if (planId) {
                this.handleEdit(parseInt(planId));
            } else {
                this.handleAddNew();
            }
        });

        this.dom.categorySelect.addEventListener('change', () => this.handleCategoryChange());

        this.dom.unitPriceInput.addEventListener('input', () => this.calculateBudget());
        this.dom.quantityInput.addEventListener('input', () => this.calculateBudget());

        // Event delegation for delete buttons
        this.dom.tableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const id = e.target.dataset.id;
                this.handleDelete(id);
            }
        });
    }

    populateYearFilter() {
        const currentYear = new Date().getFullYear();
        for (let i = currentYear + 1; i >= currentYear - 5; i--) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i}년`;
            if (i === currentYear) {
                option.selected = true;
            }
            this.dom.yearFilter.appendChild(option);
        }
    }

    async loadInitialData() {
        await this.loadCategories();
        await this.loadPlans();
    }

    async loadPlans() {
        this.showTablePlaceholder(true, '계획을 불러오는 중입니다...');
        const year = this.dom.yearFilter.value;
        try {
            const response = await this.apiCall(`/item-plans?year=${year}`);
            this.state.plans = response.data;
            this.renderTable();
        } catch (error) {
            Toast.error('계획 목록을 불러오는 데 실패했습니다.');
            this.showTablePlaceholder(true, `오류 발생: ${this.sanitizeHTML(error.message)}`);
        }
    }

    async loadCategories() {
        try {
            const response = await this.apiCall('/item-categories');
            this.state.categories = response.data;
            this.renderCategorySelect();
        } catch (error) {
            Toast.error('분류 목록을 불러오는 데 실패했습니다.');
        }
    }

    renderTable() {
        if (this.state.plans.length === 0) {
            this.showTablePlaceholder(true, '해당 연도에 등록된 계획이 없습니다.');
            this.dom.tableBody.innerHTML = '';
            return;
        }

        this.showTablePlaceholder(false);
        let html = '';
        this.state.plans.forEach(plan => {
            html += `
                <tr>
                    <td>${this.sanitizeHTML(plan.category_name)}</td>
                    <td>${this.sanitizeHTML(plan.item_name)}</td>
                    <td>${Number(plan.unit_price).toLocaleString()}</td>
                    <td>${Number(plan.quantity).toLocaleString()}</td>
                    <td>${Number(plan.budget).toLocaleString()}</td>
                    <td>${this.sanitizeHTML(plan.note || '')}</td>
                    <td>${this.sanitizeHTML(plan.creator_name || 'N/A')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#plan-modal" data-id="${plan.id}">수정</button>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${plan.id}">삭제</button>
                    </td>
                </tr>
            `;
        });
        this.dom.tableBody.innerHTML = html;
    }

    renderCategorySelect() {
        let optionsHtml = '<option value="">대분류 선택</option>';
        const buildOptions = (categories) => {
            categories.forEach(category => {
                optionsHtml += `<option value="${category.id}">${this.sanitizeHTML(category.name)}</option>`;
                // Note: This UI assumes a 2-level hierarchy (Category -> Item) for simplicity in the form.
            });
        };
        buildOptions(this.state.categories); // Assumes categories are top-level
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
            this.state.items = response.data;

            let itemsHtml = '<option value="">품목 선택</option>';
            this.state.items.forEach(item => {
                itemsHtml += `<option value="${item.id}">${this.sanitizeHTML(item.name)}</option>`;
            });
            this.dom.itemSelect.innerHTML = itemsHtml;

        } catch (error) {
             Toast.error('품목을 불러오는 데 실패했습니다.');
             this.dom.itemSelect.innerHTML = '<option value="">오류 발생</option>';
        }
    }

    handleAddNew() {
        this.state.selectedPlan = null;
        this.dom.form.reset();
        document.getElementById('plan-modal-label').textContent = '신규 계획 등록';
        this.dom.planIdInput.value = '';
        this.dom.planYearInput.value = this.dom.yearFilter.value;
        this.calculateBudget();
    }

    async handleEdit(id) {
        this.state.selectedPlan = this.state.plans.find(p => p.id === id);
        if (!this.state.selectedPlan) {
            Toast.error('계획 정보를 찾을 수 없습니다.');
            return;
        }
        document.getElementById('plan-modal-label').textContent = '지급 계획 수정';
        const plan = this.state.selectedPlan;

        this.dom.form.reset();
        this.dom.planIdInput.value = plan.id;
        this.dom.planYearInput.value = plan.year;
        this.dom.unitPriceInput.value = plan.unit_price;
        this.dom.quantityInput.value = plan.quantity;
        this.dom.noteTextarea.value = plan.note;

        // Load categories and items
        await this.loadCategories();
        this.dom.categorySelect.value = plan.category_id;
        await this.handleCategoryChange();
        this.dom.itemSelect.value = plan.item_id;

        this.calculateBudget();
    }

    async handleSave() {
        const id = this.dom.planIdInput.value;
        const isNew = !id;
        const url = isNew ? '/item-plans' : `/item-plans/${id}`;
        const method = isNew ? 'POST' : 'PUT';

        const data = {
            year: this.dom.planYearInput.value,
            item_id: this.dom.itemSelect.value,
            unit_price: this.dom.unitPriceInput.value,
            quantity: this.dom.quantityInput.value,
            note: this.dom.noteTextarea.value.trim(),
        };

        try {
            const response = await this.apiCall(url, { method, body: JSON.stringify(data) });
            Toast.success(response.message);
            this.dom.modal.hide();
            await this.loadPlans();
        } catch (error) {
            Toast.error(`저장 실패: ${this.sanitizeHTML(error.message)}`);
        }
    }

    async handleDelete(id) {
        const confirmed = await Swal.fire({
            title: '정말 삭제하시겠습니까?',
            text: "이 작업은 되돌릴 수 없습니다.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        });

        if (confirmed.isConfirmed) {
            try {
                const response = await this.apiCall(`/item-plans/${id}`, { method: 'DELETE' });
                Toast.success(response.message);
                await this.loadPlans();
            } catch (error) {
                Toast.error(`삭제 실패: ${this.sanitizeHTML(error.message)}`);
            }
        }
    }

    calculateBudget() {
        const price = parseFloat(this.dom.unitPriceInput.value) || 0;
        const qty = parseInt(this.dom.quantityInput.value, 10) || 0;
        this.dom.budgetInput.value = (price * qty).toLocaleString();
    }

    showTablePlaceholder(show, message = '') {
        if (show) {
            this.dom.tablePlaceholder.innerHTML = `<p class="text-muted">${this.sanitizeHTML(message)}</p>`;
            this.dom.tablePlaceholder.style.display = 'block';
        } else {
            this.dom.tablePlaceholder.style.display = 'none';
        }
    }

    handleExport() {
        const year = this.dom.yearFilter.value;
        const url = `/api/item-plans/export?year=${year}`;
        window.open(url, '_blank');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new InventoryPlansPage();
});
