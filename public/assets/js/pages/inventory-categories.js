class InventoryCategoriesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            categories: [],
            selectedCategory: null
        };
        this.initializeApp();
    }

    async initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        await this.loadInitialData();
    }

    cacheDOMElements() {
        this.dom = {
            treeContainer: document.getElementById('category-tree-container'),
            form: document.getElementById('category-form'),
            formTitle: document.getElementById('category-form-title'),
            formPlaceholder: document.getElementById('form-placeholder'),
            categoryIdInput: document.getElementById('category-id'),
            parentIdSelect: document.getElementById('parent-id'),
            categoryNameInput: document.getElementById('category-name'),
            isActiveSwitch: document.getElementById('is-active'),
            addBtn: document.getElementById('add-category-btn'),
            saveBtn: document.getElementById('save-btn'),
            cancelBtn: document.getElementById('cancel-btn'),
            deleteBtn: document.getElementById('delete-btn'),
        };
    }

    setupEventListeners() {
        this.dom.addBtn.addEventListener('click', () => this.handleAddNew());
        this.dom.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSave();
        });
        this.dom.cancelBtn.addEventListener('click', () => this.resetForm());
        this.dom.deleteBtn.addEventListener('click', () => this.handleDelete());

        // Use event delegation for dynamically created tree items
        this.dom.treeContainer.addEventListener('click', (e) => {
            const link = e.target.closest('.category-link');
            if (link) {
                e.preventDefault();
                const id = parseInt(link.dataset.id, 10);
                this.handleSelectCategory(id);
            }
        });
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall('/item-categories');
            this.state.categories = response.data;
            this.renderTree();
            this.updateParentIdSelect();
        } catch (error) {
            Toast.error('분류 목록을 불러오는 데 실패했습니다.');
            this.dom.treeContainer.innerHTML = `<p class="text-danger">오류 발생: ${this.sanitizeHTML(error.message)}</p>`;
        }
    }

    renderTree() {
        if (this.state.categories.length === 0) {
            this.dom.treeContainer.innerHTML = '<p>등록된 분류가 없습니다.</p>';
            return;
        }

        const buildTreeHtml = (categories, level = 0) => {
            let html = '<ul class="list-unstyled mb-0">';
            categories.forEach(category => {
                const padding = level * 20;
                html += `
                    <li style="padding-left: ${padding}px;">
                        <a href="#" class="d-block p-2 rounded category-link ${this.state.selectedCategory?.id === category.id ? 'bg-light' : ''}" data-id="${category.id}">
                            <i class="ri-folder-2-line me-2"></i> ${this.sanitizeHTML(category.name)}
                            ${category.is_active == 0 ? '<span class="badge bg-danger ms-2">미사용</span>' : ''}
                        </a>
                    </li>
                `;
                if (category.children && category.children.length > 0) {
                    html += buildTreeHtml(category.children, level + 1);
                }
            });
            html += '</ul>';
            return html;
        };

        this.dom.treeContainer.innerHTML = buildTreeHtml(this.state.categories);
    }

    updateParentIdSelect(currentCategoryId = null) {
        let optionsHtml = '<option value="">최상위 분류</option>';
        const buildOptions = (categories, level = 0) => {
            categories.forEach(category => {
                // 자기 자신과 그 하위 카테고리는 상위 카테고리 후보에서 제외
                if (currentCategoryId !== null && (category.id === currentCategoryId || this.isDescendant(this.findCategoryById(currentCategoryId), category.id))) {
                    return;
                }
                const prefix = '&nbsp;'.repeat(level * 4);
                optionsHtml += `<option value="${category.id}">${prefix}${this.sanitizeHTML(category.name)}</option>`;
                if (category.children && category.children.length > 0) {
                    buildOptions(category.children, level + 1);
                }
            });
        };
        buildOptions(this.state.categories);
        this.dom.parentIdSelect.innerHTML = optionsHtml;
    }

    handleAddNew() {
        this.state.selectedCategory = null;
        this.showForm('신규 분류 추가');
        this.dom.form.reset();
        this.dom.categoryIdInput.value = '';
        this.dom.isActiveSwitch.checked = true;
        this.dom.deleteBtn.style.display = 'none';
        this.updateParentIdSelect();
        this.renderTree(); // Deselect any highlighted item
    }

    handleSelectCategory(id) {
        this.state.selectedCategory = this.findCategoryById(id, this.state.categories);
        if (this.state.selectedCategory) {
            this.showForm('분류 정보 수정');
            this.populateForm(this.state.selectedCategory);
            this.renderTree(); // Highlight the selected item
        }
    }

    async handleSave() {
        const id = this.dom.categoryIdInput.value;
        const isNew = !id;
        const url = isNew ? '/item-categories' : `/item-categories/${id}`;
        const method = isNew ? 'POST' : 'PUT';

        const data = {
            parent_id: this.dom.parentIdSelect.value || null,
            name: this.dom.categoryNameInput.value.trim(),
            is_active: this.dom.isActiveSwitch.checked ? 1 : 0,
        };

        if (!data.name) {
            Toast.error('분류명을 입력해주세요.');
            return;
        }

        this.setButtonLoading(this.dom.saveBtn, '저장 중...');

        try {
            const response = await this.apiCall(url, { method, body: JSON.stringify(data) });
            Toast.success(response.message);
            this.resetForm();
            await this.loadInitialData();
        } catch (error) {
            Toast.error(`저장 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading(this.dom.saveBtn, '저장');
        }
    }

    async handleDelete() {
        if (!this.state.selectedCategory) return;

        const confirmed = await Swal.fire({
            title: '정말 삭제하시겠습니까?',
            text: "하위 분류나 품목이 연결된 경우 삭제할 수 없습니다.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        });

        if (confirmed.isConfirmed) {
            this.setButtonLoading(this.dom.deleteBtn, '삭제 중...');
            try {
                const response = await this.apiCall(`/item-categories/${this.state.selectedCategory.id}`, { method: 'DELETE' });
                Toast.success(response.message);
                this.resetForm();
                await this.loadInitialData();
            } catch (error) {
                Toast.error(`삭제 실패: ${error.message}`);
            } finally {
                 this.resetButtonLoading(this.dom.deleteBtn, '삭제');
            }
        }
    }

    populateForm(category) {
        this.dom.categoryIdInput.value = category.id;
        this.dom.categoryNameInput.value = category.name;
        this.dom.isActiveSwitch.checked = category.is_active == 1;
        this.dom.deleteBtn.style.display = 'inline-block';

        this.updateParentIdSelect(category.id);
        this.dom.parentIdSelect.value = category.parent_id || '';
    }

    resetForm() {
        this.state.selectedCategory = null;
        this.dom.form.classList.add('d-none');
        this.dom.formPlaceholder.classList.remove('d-none');
        this.dom.form.reset();
        this.renderTree();
    }

    showForm(title) {
        this.dom.formTitle.textContent = title;
        this.dom.form.classList.remove('d-none');
        this.dom.formPlaceholder.classList.add('d-none');
    }

    findCategoryById(id, categories = this.state.categories) {
        for (const category of categories) {
            if (category.id === id) return category;
            if (category.children) {
                const found = this.findCategoryById(id, category.children);
                if (found) return found;
            }
        }
        return null;
    }

    isDescendant(parent, childId) {
        if (!parent || !parent.children) {
            return false;
        }
        for (const child of parent.children) {
            if (child.id === childId) {
                return true;
            }
            if (this.isDescendant(child, childId)) {
                return true;
            }
        }
        return false;
    }
}

// Ensure the script runs after the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new InventoryCategoriesPage();
});
