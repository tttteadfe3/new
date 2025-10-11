class ProfilePage extends BasePage {
    constructor() {
        super({
            API_URL: '/profile'
        });
        this.elements = {};
        this.state = {
            ...this.state,
            profileData: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.container) return;
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            container: document.getElementById('profile-container')
        };
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall(this.config.API_URL);
            this.state.profileData = response.data;
            this.renderProfile();
        } catch (error) {
            this.elements.container.innerHTML = `<div class="alert alert-danger">프로필 정보를 불러오는 데 실패했습니다: ${error.message}</div>`;
        }
    }

    renderProfile(isEditMode = false) {
        if (!this.state.profileData) return;

        const { user, employee } = this.state.profileData;
        const isPending = employee?.profile_update_status === 'pending';
        const isRejected = employee?.profile_update_status === 'rejected';

        let statusMessage = '';
        if (isPending) {
            statusMessage = `<div class="alert alert-warning">프로필 변경사항이 관리자 승인을 기다리고 있습니다. 승인 전까지는 재수정할 수 없습니다.</div>`;
        } else if (isRejected) {
            statusMessage = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">프로필 수정 요청이 반려되었습니다.</h5>
                    <p><strong>반려 사유:</strong> ${this._sanitizeHTML(employee.profile_update_rejection_reason)}</p>
                    <hr><p class="mb-0">아래 내용을 수정하여 다시 요청해주세요.</p>
                </div>`;
        }

        const profileImageUrl = user.profile_image_url ? this._sanitizeHTML(user.profile_image_url) : 'https://via.placeholder.com/100?text=No+Image';

        const userCard = `
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">계정 정보 (카카오 연동)</h6></div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><img src="${profileImageUrl}" alt="프로필 사진" class="rounded-circle" width="100" height="100"></div>
                        <div class="col"><h4 class="mb-1">${this._sanitizeHTML(user.nickname)}</h4><p class="text-muted mb-0">${this._sanitizeHTML(user.email)}</p></div>
                    </div>
                </div>
            </div>`;

        let employeeCard = '';
        if (employee) {
            const fields = [
                { label: '사번', key: 'employee_number', readonly: true }, { label: '입사일', key: 'hire_date', readonly: true },
                { label: '연락처', key: 'phone_number' }, { label: '주소', key: 'address' },
                { label: '비상연락처', key: 'emergency_contact_name' }, { label: '관계', key: 'emergency_contact_relation' },
                { label: '상의 사이즈', key: 'clothing_top_size' }, { label: '하의 사이즈', key: 'clothing_bottom_size' },
                { label: '신발 사이즈', key: 'shoe_size' },
            ];
            const employeeContent = isEditMode
                ? `<form id="profile-form">${fields.map(f => `
                       <div class="col-md-6 mb-3">
                           <label for="${f.key}" class="form-label">${f.label}</label>
                           <input type="text" class="form-control" id="${f.key}" name="${f.key}" value="${this._sanitizeHTML(employee[f.key])}" ${f.readonly ? 'readonly' : ''}>
                       </div>`).join('')}</form>`
                : fields.map(f => `<div class="col-md-6 mb-3"><strong>${f.label}:</strong> ${this._sanitizeHTML(employee[f.key]) || '<i>-</i>'}</div>`).join('');
            
            employeeCard = `
                <div class="card shadow mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">직원 정보</h6></div>
                    <div class="card-body"><div class="row">${employeeContent}</div></div>
                </div>`;
        } else {
            employeeCard = `<div class="alert alert-secondary">연결된 직원 정보가 없습니다.</div>`;
        }

        this.elements.container.innerHTML = `
            ${statusMessage}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">내 프로필</h1>
                <div id="action-buttons"></div>
            </div>
            ${employeeCard}
            ${userCard}`;

        this.renderButtons(isEditMode, isPending, !!employee);
    }

    renderButtons(isEditMode, isPending, isEmployee) {
        const buttonsContainer = document.getElementById('action-buttons');
        if (!buttonsContainer) return;
        
        let buttonsHtml = '';
        if (isEmployee) {
            buttonsHtml = isEditMode
                ? `<button class="btn btn-secondary" id="cancel-btn">취소</button>
                   <button class="btn btn-primary" id="save-btn" form="profile-form">수정 요청</button>`
                : `<button class="btn btn-primary" id="edit-btn" ${isPending ? 'disabled' : ''}>정보 수정</button>`;
        }
        buttonsContainer.innerHTML = buttonsHtml;
        
        document.getElementById('edit-btn')?.addEventListener('click', () => this.renderProfile(true));
        document.getElementById('cancel-btn')?.addEventListener('click', () => this.renderProfile(false));
        document.getElementById('save-btn')?.addEventListener('click', (e) => this.handleProfileSave(e));
    }
    
    async handleProfileSave(e) {
        e.preventDefault();
        const form = document.getElementById('profile-form');
        const data = Object.fromEntries(new FormData(form).entries());
        try {
            const result = await this.apiCall(this.config.API_URL, { method: 'PUT', body: data });
            Toast.success(result.message);
            this.loadInitialData();
        } catch (error) {
            Toast.error('수정 요청 중 오류 발생: ' + error.message);
        }
    }

    _sanitizeHTML(str) {
        if (str === null || typeof str === 'undefined') return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }
}

new ProfilePage();