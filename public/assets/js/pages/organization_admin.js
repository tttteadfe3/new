document.addEventListener('DOMContentLoaded', () => {
    // Common DOM Elements
    const orgModal = new bootstrap.Modal(document.getElementById('org-modal'));
    const orgForm = document.getElementById('org-form');
    const modalTitle = document.getElementById('org-modal-title');
    const orgIdInput = document.getElementById('org-id');
    const orgTypeInput = document.getElementById('org-type');
    const orgNameInput = document.getElementById('org-name');
    const orgNameLabel = document.getElementById('org-name-label');

    // Department-specific DOM Elements
    const departmentsListContainer = document.getElementById('departments-list-container');
    const addDepartmentBtn = document.getElementById('add-department-btn');

    // Position-specific DOM Elements
    const positionsListContainer = document.getElementById('positions-list-container');
    const addPositionBtn = document.getElementById('add-position-btn');

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * Generic function to load and render a list of items (departments or positions)
     * @param {string} type - 'department' or 'position'
     * @param {HTMLElement} container - The container element to render the list into
     */
    const loadList = async (type, container) => {
        try {
            const response = await fetch(`../api/organization.php?action=list&type=${type}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            container.innerHTML = '';
            const entityName = type === 'department' ? '부서' : '직급';

            if (result.data.length === 0) {
                container.innerHTML = `<div class="list-group-item">${entityName}(이)가 없습니다.</div>`;
                return;
            }

            result.data.forEach(item => {
                const row = `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        ${sanitizeHTML(item.name)}
                        <div>
                            <button class="btn btn-secondary btn-sm edit-btn" data-id="${item.id}" data-name="${sanitizeHTML(item.name)}" data-type="${type}">수정</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}" data-name="${sanitizeHTML(item.name)}" data-type="${type}">삭제</button>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', row);
            });
        } catch (error) {
            console.error(`Error loading ${type}s:`, error);
            container.innerHTML = `<div class="list-group-item text-danger">${type === 'department' ? '부서' : '직급'} 목록 로딩 실패</div>`;
        }
    };

    /**
     * Generic function to open the modal for adding or editing an item
     * @param {string} type - 'department' or 'position'
     * @param {object|null} data - The item data for editing, or null for adding
     */
    const openModal = (type, data = null) => {
        orgForm.reset();
        const entityName = type === 'department' ? '부서' : '직급';

        orgTypeInput.value = type;
        orgNameLabel.textContent = `${entityName} 이름`;

        if (data) { // Editing
            modalTitle.textContent = `${entityName} 정보 수정`;
            orgIdInput.value = data.id;
            orgNameInput.value = data.name;
        } else { // Adding
            modalTitle.textContent = `새 ${entityName} 추가`;
            orgIdInput.value = '';
        }
        orgModal.show();
    };

    // Event Listeners
    addDepartmentBtn.addEventListener('click', () => openModal('department'));
    addPositionBtn.addEventListener('click', () => openModal('position'));

    orgForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(orgForm).entries());
        const type = data.type;

        try {
            const response = await fetch(`../api/organization.php?action=save&type=${type}`, fetchOptions({ method: 'POST', body: JSON.stringify(data) }));
            const result = await response.json();

            if (result.success) {
                Toast.success(result.message);
                orgModal.hide();
                if (type === 'department') {
                    loadList('department', departmentsListContainer);
                } else {
                    loadList('position', positionsListContainer);
                }
            } else {
                Toast.error(result.message);
            }
        } catch (error) {
            console.error(`Error saving ${type}:`, error);
            Toast.error('작업 처리 중 오류가 발생했습니다.');
        }
    });

    const handleActionClick = async (e) => {
        const target = e.target;
        const id = target.dataset.id;
        const name = target.dataset.name;
        const type = target.dataset.type;

        if (!type) return;

        if (target.classList.contains('edit-btn')) {
            openModal(type, { id, name });
        }

        if (target.classList.contains('delete-btn')) {
            const entityName = type === 'department' ? '부서' : '직급';
            const result = await Confirm.fire('삭제 확인', `'${name}' ${entityName}을(를) 정말 삭제하시겠습니까?`);
            if (result.isConfirmed) {
                deleteItem(type, id);
            }
        }
    };

    departmentsListContainer.addEventListener('click', handleActionClick);
    positionsListContainer.addEventListener('click', handleActionClick);

    const deleteItem = async (type, id) => {
        try {
            const response = await fetch(`../api/organization.php?action=delete&type=${type}`, fetchOptions({ method: 'POST', body: JSON.stringify({ id }) }));
            const result = await response.json();

            if (result.success) {
                Toast.success(result.message);
                if (type === 'department') {
                    loadList('department', departmentsListContainer);
                } else {
                    loadList('position', positionsListContainer);
                }
            } else {
                Toast.error(result.message);
            }
        } catch(error) {
            console.error(`Error deleting ${type}:`, error);
            Toast.error(`${type === 'department' ? '부서' : '직급'} 삭제 중 오류가 발생했습니다.`);
        }
    };

    // Initial Load
    loadList('department', departmentsListContainer);
    loadList('position', positionsListContainer);
});
