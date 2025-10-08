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

        const filteredMenus = menus.filter(menu => menu.parent_id == parentId);

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
            const response = await fetch('../api/menus.php');
            if (!response.ok) throw new Error('메뉴를 불러오는데 실패했습니다.');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'API에서 데이터를 가져오는데 실패했습니다.');
            }

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
        const itemEl = evt.item;
        const toList = evt.to;
        const fromList = evt.from;

        const menuId = itemEl.dataset.id;
        const newParentId = toList.closest('.list-group-item')?.dataset.id || null;

        const children = Array.from(toList.children);
        const newOrder = children.indexOf(itemEl);

        const updates = [];

        // Update the dropped item
        updates.push({ id: menuId, parent_id: newParentId, display_order: newOrder });

        // Update siblings in the new list
        children.forEach((child, index) => {
            if (child.dataset.id !== menuId) {
                updates.push({ id: child.dataset.id, display_order: index });
            }
        });

        // Update siblings in the old list if it's different
        if (fromList !== toList) {
            Array.from(fromList.children).forEach((child, index) => {
                updates.push({ id: child.dataset.id, display_order: index });
            });
        }

        // Batch update
        try {
            const menuToUpdate = findMenuById(menuId);
            if (!menuToUpdate) return;

            menuToUpdate.parent_id = newParentId;
            menuToUpdate.display_order = newOrder;

            const response = await fetch('../api/menus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(menuToUpdate)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || '순서 변경에 실패했습니다.');
            }

            // Re-render to ensure consistency
            await fetchAndRenderMenus();

        } catch(error) {
            alert(error.message);
            // Re-render to revert optimistic update
            await fetchAndRenderMenus();
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

        // Ensure numeric fields are numbers
        data.id = data.id ? parseInt(data.id) : null;
        data.parent_id = data.parent_id ? parseInt(data.parent_id) : null;

        try {
            const response = await fetch('../api/menus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || '저장에 실패했습니다.');
            }

            menuModal.hide();
            await fetchAndRenderMenus();

        } catch (error) {
            alert(error.message);
        }
    });

    deleteMenuBtn.addEventListener('click', async function() {
        const menuId = document.getElementById('menu-id').value;
        if (!menuId || !confirm('정말로 이 메뉴를 삭제하시겠습니까? 하위 메뉴가 있으면 삭제할 수 없습니다.')) {
            return;
        }

        try {
            const response = await fetch(`/api/menus.php?id=${menuId}`, {
                method: 'DELETE'
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || '삭제에 실패했습니다.');
            }

            menuModal.hide();
            await fetchAndRenderMenus();

        } catch (error) {
            alert(error.message);
        }
    });

    fetchAndRenderMenus();
});
