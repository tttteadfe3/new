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

    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const loadList = async (type, container) => {
        try {
            const result = await ApiService.request(`/organization?type=${type}`);
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

    addDepartmentBtn.addEventListener('click', () => openModal('department'));
    addPositionBtn.addEventListener('click', () => openModal('position'));

    orgForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = orgIdInput.value;
        const type = orgTypeInput.value;
        const name = orgNameInput.value;
        const payload = { name, type };

        try {
            let result;
            if (id) { // Update
                result = await ApiService.request(`/organization/${id}`, { method: 'PUT', body: payload });
            } else { // Create
                result = await ApiService.request('/organization', { method: 'POST', body: payload });
            }
            Toast.success(result.message);
            orgModal.hide();
            if (type === 'department') {
                loadList('department', departmentsListContainer);
            } else {
                loadList('position', positionsListContainer);
            }
        } catch (error) {
            console.error(`Error saving ${type}:`, error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    });

    const handleActionClick = async (e) => {
        const target = e.target;
        const id = target.dataset.id;
        const name = target.dataset.name;
        const type = target.dataset.type;

        if (!type || !id) return;

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
            const result = await ApiService.request(`/organization/${id}`, { method: 'DELETE', body: { type } });
            Toast.success(result.message);
            if (type === 'department') {
                loadList('department', departmentsListContainer);
            } else {
                loadList('position', positionsListContainer);
            }
        } catch(error) {
            console.error(`Error deleting ${type}:`, error);
            Toast.error(`${type === 'department' ? '부서' : '직급'} 삭제 중 오류가 발생했습니다: ${error.message}`);
        }
    };

    // Initial Load
    loadList('department', departmentsListContainer);
    loadList('position', positionsListContainer);
});
