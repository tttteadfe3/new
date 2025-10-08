// js/roles.js
document.addEventListener('DOMContentLoaded', () => {
    // DOM 요소 캐싱
    const rolesListContainer = document.getElementById('roles-list-container');
    const roleDetailsContainer = document.getElementById('role-details-container');
    const roleModal = new bootstrap.Modal(document.getElementById('role-modal'));
    const roleForm = document.getElementById('role-form');
    let currentRoleId = null;

    // 공통 fetch 옵션
    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    // HTML 인코딩 함수
    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * 역할 목록을 API에서 불러와 좌측 패널에 렌더링
     * @param {number|null} selectRoleId - 로드 후 자동으로 선택할 역할 ID
     */
    const loadRolesList = async (selectRoleId = null) => {
        try {
            const response = await fetch('../api/roles.php?action=list_roles', fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            rolesListContainer.innerHTML = '';
            result.data.forEach(role => {
                const userCountBadge = `<span class="badge bg-secondary rounded-pill ms-auto">${role.user_count}</span>`;
                const item = `
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-id="${role.id}">
                        ${sanitizeHTML(role.name)}
                        ${userCountBadge}
                    </a>`;
                rolesListContainer.insertAdjacentHTML('beforeend', item);
            });

            const roleIdToLoad = selectRoleId || (result.data[0]?.id || null);
            if (roleIdToLoad) {
                const roleElement = document.querySelector(`.list-group-item[data-id='${roleIdToLoad}']`);
                if(roleElement) roleElement.classList.add('active');
                loadRoleDetails(roleIdToLoad);
            } else {
                roleDetailsContainer.innerHTML = '<div class="alert alert-info">표시할 역할이 없습니다. 새 역할을 추가해주세요.</div>';
            }
        } catch (error) {
            console.error('Error loading roles list:', error);
            rolesListContainer.innerHTML = `<div class="list-group-item text-danger">역할 목록 로딩 실패</div>`;
        }
    };

    /**
     * 특정 역할의 상세 정보(권한, 할당된 사용자)를 불러와 우측 패널에 렌더링
     * @param {number} roleId - 상세 정보를 조회할 역할 ID
     */
    const loadRoleDetails = async (roleId) => {
        currentRoleId = roleId;
        try {
            const response = await fetch(`../api/roles.php?action=get_details&role_id=${roleId}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            const { role, all_permissions, assigned_permission_ids, assigned_users } = result.data;

            let permissionsHtml = all_permissions.map(p => {
                const isChecked = assigned_permission_ids.includes(p.id);
                return `
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="${p.id}" id="perm_${p.id}" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label" for="perm_${p.id}">
                                <strong>${sanitizeHTML(p.key)}</strong><small class="text-muted d-block">${sanitizeHTML(p.description)}</small>
                            </label>
                        </div>
                    </div>`;
            }).join('');

            let usersHtml = assigned_users.length > 0 
                ? assigned_users.map(user => `<li class="list-group-item">${sanitizeHTML(user.nickname)} (ID: ${user.id})</li>`).join('')
                : '<li class="list-group-item text-muted">이 역할을 가진 사용자가 없습니다.</li>';

            const detailsHtml = `
                <div class="card shadow mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0">"${sanitizeHTML(role.name)}" 설정</h5>
                        <div>
                            <button class="btn btn-secondary btn-sm edit-role-btn">정보 수정</button>
                            <button class="btn btn-danger btn-sm delete-role-btn">역할 삭제</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>설명:</strong> ${sanitizeHTML(role.description) || '<i>없음</i>'}</p><hr>
                        <h6><strong>할당된 퍼미션</strong></h6>
                        <form id="permissions-form"><div class="row">${permissionsHtml}</div><hr><button type="submit" class="btn btn-primary">퍼미션 저장</button></form>
                    </div>
                </div>
                <div class="card shadow">
                    <div class="card-header"><h6 class="m-0">할당된 사용자 목록 (${assigned_users.length}명)</h6></div>
                    <ul class="list-group list-group-flush">${usersHtml}</ul>
                </div>`;
            roleDetailsContainer.innerHTML = detailsHtml;

            document.getElementById('permissions-form').addEventListener('submit', handlePermissionSave);
            document.querySelector('.edit-role-btn').addEventListener('click', () => handleRoleEdit(role));
            document.querySelector('.delete-role-btn').addEventListener('click', handleRoleDelete);
        } catch (error) {
            console.error('Error loading role details:', error);
            roleDetailsContainer.innerHTML = `<div class="alert alert-danger">상세 정보 로딩 실패</div>`;
        }
    };
    
    // 이벤트 핸들러들
    rolesListContainer.addEventListener('click', (e) => {
        e.preventDefault();
        const target = e.target.closest('.list-group-item');
        if (target && !target.classList.contains('active')) {
            document.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
            target.classList.add('active');
            loadRoleDetails(target.dataset.id);
        }
    });

    document.getElementById('add-role-btn').addEventListener('click', () => {
        roleForm.reset();
        document.getElementById('role-id').value = '';
        document.getElementById('role-modal-title').textContent = '새 역할 추가';
        roleModal.show();
    });

    roleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(roleForm).entries());
        try {
            const response = await fetch('../api/roles.php?action=save_role', fetchOptions({ method: 'POST', body: JSON.stringify(data) }));
            const result = await response.json();
            
            if (result.success) {
                Toast.success(result.message);
                roleModal.hide();
                const newSelectedId = result.data?.new_role_id || data.id;
                loadRolesList(newSelectedId);
            } else {
                Toast.error(result.message);
            }
        } catch (error) {
            console.error('Error saving role:', error);
            Toast.error('작업 처리 중 오류가 발생했습니다.');
        }
    });

    const handlePermissionSave = async (e) => {
        e.preventDefault();
        const data = {
            role_id: currentRoleId,
            permissions: Array.from(new FormData(e.target).getAll('permissions[]')).map(Number)
        };
        try {
            const response = await fetch('../api/roles.php?action=save_permissions', fetchOptions({ method: 'POST', body: JSON.stringify(data) }));
            const result = await response.json();
            if (result.success) {
                Toast.success(result.message);
            } else {
                Toast.error(result.message);
            }
        } catch (error) {
            console.error('Error saving permissions:', error);
            Toast.error('권한 저장 중 오류가 발생했습니다.');
        }
    };

    const handleRoleEdit = (role) => {
        roleForm.reset();
        document.getElementById('role-id').value = role.id;
        document.getElementById('role-name').value = role.name;
        document.getElementById('role-description').value = role.description;
        document.getElementById('role-modal-title').textContent = '역할 정보 수정';
        roleModal.show();
    };

    const handleRoleDelete = async () => {
        const roleName = document.querySelector(`#roles-list-container [data-id='${currentRoleId}']`).textContent.trim().split('\n')[0];
        const confirmResult = await Confirm.fire('역할 삭제', `'${roleName}' 역할을 정말 삭제하시겠습니까?`);
        if (!confirmResult.isConfirmed) return;

        try {
            const response = await fetch('../api/roles.php?action=delete_role', fetchOptions({ method: 'POST', body: JSON.stringify({ id: currentRoleId }) }));
            const result = await response.json();
            
            if (result.success) {
                Toast.success(result.message);
                roleDetailsContainer.innerHTML = '<div class="alert alert-info">역할을 선택해주세요.</div>';
                loadRolesList();
            } else {
                Toast.error(result.message);
            }
        } catch(error) {
            console.error('Error deleting role:', error);
            Toast.error('역할 삭제 중 오류가 발생했습니다.');
        }
    };

    // 초기 역할 목록 로드
    loadRolesList();
});