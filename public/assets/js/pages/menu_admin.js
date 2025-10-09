document.addEventListener('DOMContentLoaded', function () {
    const menuTreeContainer = document.getElementById('menu-tree-container');
    const menuModal = new bootstrap.Modal(document.getElementById('menu-modal'));
    const menuForm = document.getElementById('menu-form');
    const menuModalTitle = document.getElementById('menu-modal-title');
    const deleteMenuBtn = document.getElementById('delete-menu-btn');

    let allMenus = [];
    function renderMenuTree(menus, parentId = null) {
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

            const childUl = renderMenuTree(menus, menu.id);
            if (childUl.hasChildNodes()) {
                childrenContainer.appendChild(childUl);
            }
        });
        return ul;
    }

    async function fetchAndRenderMenus() {
        try {
            const result = await ApiService.request('/menus');
            allMenus = result.data || [];
            menuTreeContainer.innerHTML = '';
            const menuTree = renderMenuTree(allMenus, null);
            menuTreeContainer.appendChild(menuTree);
            initializeSortable(menuTreeContainer.querySelectorAll('ul'));
        } catch (error) {
            console.error(error);
            menuTreeContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    function initializeSortable(elements) {
        elements.forEach(el => {
            new Sortable(el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onEnd: handleDrop
            });
        });
    }

    async function handleDrop(evt) {
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

        // Remove duplicates by converting to a Map and back
        const uniqueUpdates = [...new Map(updates.map(item => [item.id, item])).values()];

        try {
            await ApiService.request('/menus/order', { method: 'PUT', body: { updates: uniqueUpdates } });
            Toast.success('메뉴 순서가 저장되었습니다.');
            await fetchAndRenderMenus();
        } catch(error) {
            Toast.error(`순서 변경 실패: ${error.message}`);
            await fetchAndRenderMenus(); // Revert on failure
        }
    }

    function findMenuById(id) {
        return allMenus.find(m => m.id == id);
    }

    function openMenuModal(menu = {}, parentId = null) {
        menuForm.reset();
        menuModalTitle.textContent = menu.id ? '메뉴 수정' : '새 메뉴 추가';
        deleteMenuBtn.style.display = menu.id ? 'block' : 'none';

        document.getElementById('menu-id').value = menu.id || '';
        document.getElementById('parent-id').value = parentId !== null ? parentId : (menu.parent_id || '');
        document.getElementById('menu-name').value = menu.name || '';
        document.getElementById('menu-url').value = menu.url || '';
        document.getElementById('menu-icon').value = menu.icon || '';
        document.getElementById('menu-permission').value = menu.permission_key || '';

        menuModal.show();
    }

    document.getElementById('add-root-menu-btn').addEventListener('click', () => {
        openMenuModal();
    });

    menuTreeContainer.addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-btn');
        const addChildBtn = e.target.closest('.add-child-btn');

        if (editBtn) {
            const menuItemEl = e.target.closest('.list-group-item');
            const menuId = menuItemEl.dataset.id;
            const menu = findMenuById(menuId);
            if (menu) openMenuModal(menu);
        }

        if (addChildBtn) {
            const menuItemEl = e.target.closest('.list-group-item');
            const parentId = menuItemEl.dataset.id;
            openMenuModal({}, parentId);
        }
    });

    menuForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(menuForm);
        const data = Object.fromEntries(formData.entries());
        const id = data.id ? parseInt(data.id) : null;

        data.parent_id = data.parent_id ? parseInt(data.parent_id) : null;
        // Remove empty id for creation
        if (!id) delete data.id;

        try {
            const result = id
                ? await ApiService.request(`/menus/${id}`, { method: 'PUT', body: data })
                : await ApiService.request('/menus', { method: 'POST', body: data });

            Toast.success(result.message);
            menuModal.hide();
            await fetchAndRenderMenus();

        } catch (error) {
            Toast.error(`저장 실패: ${error.message}`);
        }
    });

    deleteMenuBtn.addEventListener('click', async function() {
        const menuId = document.getElementById('menu-id').value;
        if (!menuId || !confirm('정말로 이 메뉴를 삭제하시겠습니까? 하위 메뉴가 있으면 삭제할 수 없습니다.')) {
            return;
        }

        try {
            const result = await ApiService.request(`/menus/${menuId}`, { method: 'DELETE' });
            Toast.success(result.message);
            menuModal.hide();
            await fetchAndRenderMenus();

        } catch (error) {
            Toast.error(`삭제 실패: ${error.message}`);
        }
    });

    fetchAndRenderMenus();
});
