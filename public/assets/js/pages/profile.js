// js/profile.js
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('profile-container');
    if (!container) return;

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


    let profileData = null;

    /** 프로필 데이터를 API에서 불러옴 */
    const loadProfile = async () => {
        try {
            const response = await fetch('../api/v1/profile', fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            profileData = result.data;
            renderProfile(false); // 초기에는 조회 모드로 렌더링
        } catch (error) {
            container.innerHTML = `<div class="alert alert-danger">프로필 정보를 불러오는 데 실패했습니다: ${error.message}</div>`;
        }
    };

    /** 조회/수정 모드에 따라 화면을 렌더링 (userCard 부분 수정) */
    const renderProfile = (isEditMode = false) => {
        if (!profileData) return;

        const { user, employee } = profileData;
        const isPending = employee?.profile_update_status === 'pending';
        const isRejected = employee?.profile_update_status === 'rejected';

        let statusMessage = '';
        if (isPending) {
            statusMessage = `<div class="alert alert-warning">프로필 변경사항이 관리자 승인을 기다리고 있습니다. 승인 전까지는 재수정할 수 없습니다.</div>`;
        }
        if (isRejected) {
            statusMessage = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">프로필 수정 요청이 반려되었습니다.</h5>
                    <p><strong>반려 사유:</strong> ${sanitizeHTML(employee.profile_update_rejection_reason)}</p>
                    <hr>
                    <p class="mb-0">아래 내용을 수정하여 다시 요청해주세요.</p>
                </div>`;
        }

        const profileImageUrl = user.profile_image_url 
            ? sanitizeHTML(user.profile_image_url) 
            : 'https://via.placeholder.com/100?text=No+Image'; // 프로필 사진이 없을 경우 기본 이미지

        const userCard = `
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">계정 정보 (카카오 연동)</h6></div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img src="${profileImageUrl}" alt="프로필 사진" class="rounded-circle" width="100" height="100">
                        </div>
                        <div class="col">
                            <h4 class="mb-1">${sanitizeHTML(user.nickname)}</h4>
                            <p class="text-muted mb-0">${sanitizeHTML(user.email)}</p>
                        </div>
                    </div>
                </div>
            </div>`;
        let employeeCard = '';
        if (employee) {
            const fields = [
                { label: '사번', key: 'employee_number', readonly: true },
                { label: '입사일', key: 'hire_date', readonly: true },
                { label: '연락처', key: 'phone_number' },
                { label: '주소', key: 'address' },
                { label: '비상연락처', key: 'emergency_contact_name' },
                { label: '관계', key: 'emergency_contact_relation' },
                { label: '상의 사이즈', key: 'clothing_top_size' },
                { label: '하의 사이즈', key: 'clothing_bottom_size' },
                { label: '신발 사이즈', key: 'shoe_size' },
            ];

            const employeeContent = isEditMode
                ? `<form id="profile-form">
                       ${fields.map(f => `
                           <div class="col-md-6 mb-3">
                               <label for="${f.key}" class="form-label">${f.label}</label>
                               <input type="${f.key === 'hire_date' ? 'date' : 'text'}" class="form-control" id="${f.key}" name="${f.key}" value="${sanitizeHTML(employee[f.key])}" ${f.readonly ? 'readonly' : ''}>
                           </div>
                       `).join('')}
                   </form>`
                : fields.map(f => `<div class="col-md-6 mb-3"><strong>${f.label}:</strong> ${sanitizeHTML(employee[f.key]) || '<i>-</i>'}</div>`).join('');
            
            employeeCard = `
                <div class="card shadow mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">직원 정보</h6></div>
                    <div class="card-body"><div class="row">${employeeContent}</div></div>
                </div>`;
        } else {
            employeeCard = `<div class="alert alert-secondary">연결된 직원 정보가 없습니다. 관리자를 통해 직원으로 등록할 수 있습니다.</div>`;
        }

        // 최종 HTML 렌더링 (순서 변경: 계정 정보 -> 직원 정보)
        container.innerHTML = `
            ${statusMessage}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">내 프로필</h1>
                <div id="action-buttons"></div>
            </div>
            ${employeeCard}
            ${userCard}
        `;

        renderButtons(isEditMode, isPending, !!employee);
    };


    /** 상태에 따라 버튼들을 렌더링 */
    const renderButtons = (isEditMode, isPending, isEmployee) => {
        const buttonsContainer = document.getElementById('action-buttons');
        if (!buttonsContainer) return;
        
        let buttonsHtml = '';
        if (isEmployee) { // 직원일 경우에만 수정 관련 버튼 표시
            if (isEditMode) {
                buttonsHtml = `
                    <button class="btn btn-secondary" id="cancel-btn">취소</button>
                    <button class="btn btn-primary" id="save-btn" form="profile-form">수정 요청</button>`;
            } else {
                buttonsHtml = `<button class="btn btn-primary" id="edit-btn" ${isPending ? 'disabled' : ''}>정보 수정</button>`;
            }
        }
        buttonsContainer.innerHTML = buttonsHtml;
        
        // 동적으로 생성된 버튼에 이벤트 리스너 추가
        document.getElementById('edit-btn')?.addEventListener('click', () => renderProfile(true));
        document.getElementById('cancel-btn')?.addEventListener('click', () => renderProfile(false));
        document.getElementById('save-btn')?.addEventListener('click', handleProfileSave);
    };
    
    /** 프로필 저장 처리 */
    const handleProfileSave = async (e) => {
        e.preventDefault();
        const form = document.getElementById('profile-form');
        const data = Object.fromEntries(new FormData(form).entries());
        try {
            const response = await fetch('../api/v1/profile/update', fetchOptions({
                method: 'POST', body: JSON.stringify(data)
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            alert(result.message);
            loadProfile(); // 저장 후 조회 모드로 다시 렌더링
        } catch (error) {
            alert('수정 요청 중 오류 발생: ' + error.message);
        }
    };

    // 초기 프로필 로드
    loadProfile();
});