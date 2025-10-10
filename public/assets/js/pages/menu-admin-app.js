class MenuAdminApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/menus'
        });

        this.state = {
            ...this.state,
            allMenus: [],
            menuModal: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.menuModal = new bootstrap.Modal(this.elements.menuModal);
        this.setupEventListeners();
        this.loadAndRenderMenus();
    }

    cacheDOMElements() {
        this.elements = {
            menuTreeContainer: document.getElementById('menu-tree-container'),
            menuModal: document.getElementById('menu-modal'),
            menuForm: document.getElementById('menu-form'),
            menuModalTitle: document.getElementById('menu-modal-title'),
            deleteMenuBtn: document.getElementById('delete-menu-btn'),
            addRootMenuBtn: document.getElementById('add-root-menu-btn')
        };
    }

    setupEventListeners() {
        this.elements.addRootMenuBtn.addEventListener('click', () => this.openMenuModal());
        this.elements.menuTreeContainer.addEventListener('click', e => this.handleMenuActionClick(e));
        this.elements.menuForm.addEventListener('submit', e => this.handleFormSubmit(e));
        this.elements.deleteMenuBtn.addEventListener('click', () => this.handleDelete());
    }

    async loadAndRenderMenus() {
        try {
            const result = await this.apiCall(this.config.API_URL);
            this.state.allMenus = result.data || [];
            this.elements.menuTreeContainer.innerHTML = '';
            const menuTree = this.renderMenuTree(this.state.allMenus, null);
            this.elements.menuTreeContainer.appendChild(menuTree);
            this.initializeSortable(this.elements.menuTreeContainer.querySelectorAll('ul'));
        } catch (error) {
            console.error(error);
            this.elements.menuTreeContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    renderMenuTree(menus, parentId = null) {
        const ul = document.createElement('ul');
        if (parentId === null) {
            ul.id = 'root-menu-list';
            ul.classList.add('list-group');
        } else {
            ul.classList.add('list-group', 'mt-2');
        }

        const filteredMenus = menus
            .filter(menu => menu.parent_id == parentId)
            .sort((a, b) => a.display_order - b.display_order);

        filteredMenus.forEach(menu => {
            const li = document.createElement('li');
            li.className = 'list-group-item menu-item';
            li.dataset.id = menu.id;

            li.innerHTML = `
                <div>
                    <i class="${menu.icon || 'ri-menu-line'} me-2"></i>
                    <span>${menu.name}</span>
                    <small class="text-muted">${menu.url || ''}</small>
                </div>
                <div class="menu-actions">
                    <button class="btn btn-sm btn-outline-secondary add-child-btn" title="하위 메뉴 추가"><i class="ri-add-line"></i></button>
                    <button class="btn btn-sm btn-outline-primary edit-btn" title="수정"><i class="ri-pencil-line"></i></button>
                </div>
            `;

            ul.appendChild(li);

            const childrenContainer = document.createElement('div');
            childrenContainer.classList.add('ms-4');
            li.appendChild(childrenContainer);

            const childUl = this.renderMenuTree(menus, menu.id);
            if (childUl.hasChildNodes()) {
                childrenContainer.appendChild(childUl);
            }
        });
        return ul;
    }

    initializeSortable(elements) {
        elements.forEach(el => {
            new Sortable(el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onEnd: (evt) => this.handleDrop(evt)
            });
        });
    }

    async handleDrop(evt) {
        const toList = evt.to;
        const fromList = evt.from;
        let updates = [];

        const processList = (list) => {
            const parentId = list.closest('.list-group-item')?.dataset.id || null;
            Array.from(list.children).forEach((child, index) => {
                updates.push({
                    id: parseInt(child.dataset.id),
                    parent_id: parentId ? parseInt(parentId) : null,
                    display_order: index
                });
            });
        };

        processList(toList);
        if (fromList !== toList) {
            processList(fromList);
        }

        const uniqueUpdates = [...new Map(updates.map(item => [item.id, item])).values()];

        try {
            await this.apiCall('/menus/order', { method: 'PUT', body: { updates: uniqueUpdates } });
            Toast.success('메뉴 순서가 저장되었습니다.');
        } catch(error) {
            Toast.error(`순서 변경 실패: ${error.message}`);
        } finally {
            await this.loadAndRenderMenus(); // Always revert to server state
        }
    }

    openMenuModal(menu = {}, parentId = null) {
        this.elements.menuForm.reset();
        this.elements.menuModalTitle.textContent = menu.id ? '메뉴 수정' : '새 메뉴 추가';
        this.elements.deleteMenuBtn.style.display = menu.id ? 'block' : 'none';

        document.getElementById('menu-id').value = menu.id || '';
        document.getElementById('parent-id').value = parentId !== null ? parentId : (menu.parent_id || '');
        document.getElementById('menu-name').value = menu.name || '';
        document.getElementById('menu-url').value = menu.url || '';
        document.getElementById('menu-icon').value = menu.icon || '';
        document.getElementById('menu-permission').value = menu.permission_key || '';

        this.state.menuModal.show();
    }

    handleMenuActionClick(e) {
        const editBtn = e.target.closest('.edit-btn');
        const addChildBtn = e.target.closest('.add-child-btn');
        const menuItemEl = e.target.closest('.list-group-item');

        if (!menuItemEl) return;
        const menuId = menuItemEl.dataset.id;

        if (editBtn) {
            const menu = this.state.allMenus.find(m => m.id == menuId);
            if (menu) this.openMenuModal(menu);
        } else if (addChildBtn) {
            this.openMenuModal({}, menuId);
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.menuForm);
        const data = Object.fromEntries(formData.entries());
        const id = data.id ? parseInt(data.id) : null;

        data.parent_id = data.parent_id ? parseInt(data.parent_id) : null;
        if (!id) delete data.id;

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: data });
            Toast.success(result.message);
            this.state.menuModal.hide();
            await this.loadAndRenderMenus();
        } catch (error) {
            Toast.error(`저장 실패: ${error.message}`);
        }
    }

    async handleDelete() {
        const menuId = document.getElementById('menu-id').value;
        if (!menuId) return;

        const confirmResult = await Confirm.fire('삭제 확인', '정말로 이 메뉴를 삭제하시겠습니까? 하위 메뉴가 있으면 삭제할 수 없습니다.');
        if (!confirmResult.isConfirmed) return;

        try {
            const result = await this.apiCall(`${this.config.API_URL}/${menuId}`, { method: 'DELETE' });
            Toast.success(result.message);
            this.state.menuModal.hide();
            await this.loadAndRenderMenus();
        } catch (error) {
            Toast.error(`삭제 실패: ${error.message}`);
        }
    }
}

new MenuAdminApp();