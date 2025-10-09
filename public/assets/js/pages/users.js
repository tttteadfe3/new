// js/users.js
document.addEventListener('DOMContentLoaded', () => {
    // Modal and form elements
    const userTableBody = document.getElementById('user-table-body');
    const userModal = new bootstrap.Modal(document.getElementById('user-modal'));
    const userForm = document.getElementById('user-form');
    const userModalTitle = document.getElementById('user-modal-title');
    const mappingModal = new bootstrap.Modal(document.getElementById('mapping-modal'));
    const mappingForm = document.getElementById('mapping-form');
    const mappingModalTitle = document.getElementById('mapping-modal-title');

    // Filter elements
    const statusFilter = document.getElementById('status-filter');
    const nicknameFilter = document.getElementById('nickname-filter');
    const staffFilter = document.getElementById('staff-filter');
    const roleFilter = document.getElementById('role-filter');
    const searchBtn = document.getElementById('filter-search-btn');
    const resetBtn = document.getElementById('filter-reset-btn');

    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const loadUserList = async (filters = {}) => {
        userTableBody.innerHTML = '<tr><td colspan="6" class="text-center">사용자 목록을 불러오는 중...</td></tr>';
        
        const queryParams = new URLSearchParams(filters);

        try {
            const result = await ApiService.request(`/users?${queryParams.toString()}`);

            userTableBody.innerHTML = '';
            if (result.data.length === 0) {
                userTableBody.innerHTML = '<tr><td colspan="6" class="text-center">검색 결과가 없습니다.</td></tr>';
                return;
            }

            result.data.forEach(user => {
                const statusBadge = { 
                    'active': 'bg-success',
                    'pending': 'bg-warning',
                    'blocked': 'bg-danger'
                 }[user.status] || 'bg-secondary';
                
                let mappingInfo, mappingButton;
                if (user.employee_id) {
                    mappingInfo = `<span class="badge bg-info">${sanitizeHTML(user.employee_name)}</span>`;
                    mappingButton = `<button class="btn btn-warning btn-sm unlink-btn" data-id="${user.id}">연결 해제</button>`;
                } else {
                    mappingInfo = `<span class="text-muted"><i>연결 안됨</i></span>`;
                    mappingButton = `<button class="btn btn-success btn-sm link-btn" data-id="${user.id}">직원 연결</button>`;
                }

                const row = `
                    <tr>
                        <td>${user.id}</td>
                        <td>${sanitizeHTML(user.nickname)}</td>
                        <td>${mappingInfo}</td>
                        <td>${user.roles || '<i>역할 없음</i>'}</td>
                        <td><span class="badge ${statusBadge}">${sanitizeHTML(user.status)}</span></td>
                        <td class="text-nowrap">${mappingButton}<button class="btn btn-secondary btn-sm edit-btn ms-1" data-id="${user.id}">역할/상태 수정</button></td>
                    </tr>`;
                userTableBody.insertAdjacentHTML('beforeend', row);
            });
        } catch (error) {
            console.error('Error loading user list:', error);
            userTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    };

    const loadRoles = async () => {
        try {
            const result = await ApiService.request('/roles');
            
            roleFilter.innerHTML = '<option value="">-- 전체 --</option>';
            result.data.forEach(role => {
                const option = document.createElement('option');
                option.value = role.id;
                option.textContent = sanitizeHTML(role.name);
                roleFilter.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading roles:', error);
        }
    };

    searchBtn.addEventListener('click', () => {
        const filters = {
            status: statusFilter.value,
            nickname: nicknameFilter.value,
            staff: staffFilter.value,
            role_id: roleFilter.value
        };
        loadUserList(filters);
    });

    resetBtn.addEventListener('click', () => {
        statusFilter.value = '';
        nicknameFilter.value = '';
        staffFilter.value = '';
        roleFilter.value = '';
        loadUserList();
    });

    const departmentFilter = document.getElementById('department_filter');

    const loadUnlinkedEmployees = async (departmentId = '') => {
        const employeeSelect = document.getElementById('employee_id_select');
        employeeSelect.innerHTML = '<option value="">불러오는 중...</option>';
        try {
            const query = departmentId ? `?department_id=${departmentId}` : '';
            const result = await ApiService.request(`/employees/unlinked${query}`);
            
            employeeSelect.innerHTML = '<option value="">-- 연결할 직원을 선택하세요 --</option>';
            result.data.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.textContent = `${sanitizeHTML(emp.name)} (${sanitizeHTML(emp.employee_number) || '사번 없음'})`;
                employeeSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error fetching unlinked employees:', error);
            employeeSelect.innerHTML = '<option value="">목록 로딩 실패</option>';
        }
    };

    const loadDepartments = async () => {
        try {
            const result = await ApiService.request('/organization?type=department');

            departmentFilter.innerHTML = '<option value="">-- 전체 --</option>';
            result.data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = sanitizeHTML(dept.name);
                departmentFilter.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    };

    departmentFilter.addEventListener('change', () => {
        loadUnlinkedEmployees(departmentFilter.value);
    });

    userTableBody.addEventListener('click', async (e) => {
        const target = e.target;
        const userId = target.dataset.id;
        if (!userId) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const [userResult, rolesResult] = await Promise.all([
                    ApiService.request(`/users/${userId}`),
                    ApiService.request('/roles')
                ]);

                const user = userResult.data;
                userModalTitle.textContent = `'${sanitizeHTML(user.nickname)}' 정보 수정`;
                userForm.user_id.value = user.id;
                userForm.status.value = user.status;
                
                const rolesContainer = document.getElementById('roles-container');
                rolesContainer.innerHTML = '';
                rolesResult.data.forEach(role => {
                    const isChecked = user.assigned_roles.includes(role.id);
                    const checkbox = `<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="roles[]" value="${role.id}" id="role_${role.id}" ${isChecked ? 'checked' : ''}><label class="form-check-label" for="role_${role.id}">${sanitizeHTML(role.name)}</label></div>`;
                    rolesContainer.insertAdjacentHTML('beforeend', checkbox);
                });
                userModal.show();
            } catch (error) {
                console.error('Error fetching user details:', error);
                Toast.error('사용자 정보를 불러오는 데 실패했습니다.');
            }
        }

        if (target.classList.contains('link-btn')) {
            mappingForm.user_id.value = userId;
            mappingModalTitle.textContent = `'${sanitizeHTML(target.closest('tr').children[1].textContent)}' 사용자에게 직원 연결`;
            
            loadDepartments();
            loadUnlinkedEmployees();

            mappingModal.show();
        }

        if (target.classList.contains('unlink-btn')) {
            const confirmResult = await Confirm.fire('연결 해제', '정말로 이 사용자의 직원 연결을 해제하시겠습니까?');
            if (!confirmResult.isConfirmed) return;

            try {
                const result = await ApiService.request(`/users/${userId}/unlink`, { method: 'POST' });
                Toast.success(result.message);
                loadUserList();
            } catch (error) {
                console.error('Error unlinking employee:', error);
                Toast.error('연결 해제 중 오류가 발생했습니다.');
            }
        }
    });

    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = userForm.user_id.value;
        const data = {
            status: userForm.status.value,
            roles: Array.from(userForm.querySelectorAll('input[name="roles[]"]:checked')).map(el => Number(el.value))
        };
        try {
            const result = await ApiService.request(`/users/${userId}`, { method: 'PUT', body: data });
            userModal.hide();
            loadUserList();
            Toast.success(result.message);
        } catch (error) {
            console.error('Error saving user:', error);
            Toast.error('저장 중 오류가 발생했습니다.');
        }
    });
    
    mappingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = mappingForm.user_id.value;
        const employeeId = mappingForm.employee_id.value;
        if (!employeeId) {
            Toast.error('연결할 직원을 선택해주세요.'); 
            return; 
        }
        try {
            const result = await ApiService.request(`/users/${userId}/link`, { method: 'POST', body: { employee_id: employeeId } });
            mappingModal.hide();
            loadUserList();
            Toast.success(result.message);
        } catch (error) {
            console.error('Error linking employee:', error);
            Toast.error('직원 연결 중 오류가 발생했습니다.');
        }
    });

    // Initial data load
    loadUserList();
    loadRoles();
});