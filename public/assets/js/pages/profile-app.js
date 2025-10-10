class ProfileApp extends BaseApp {
    constructor() {
        super();
    }

    initializeApp() {
        this.loadInitialData();
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall('/profile');
            this.renderProfile(response.data);
        } catch (error) {
            this.renderError(error.message);
        }
    }

    renderProfile(data) {
        const container = document.getElementById('profile-container');
        if (!container) return;

        const employeeInfo = data.employee ? `
            <p class="text-muted"><strong>부서:</strong> ${data.employee.department_name || 'N/A'}</p>
            <p class="text-muted"><strong>직위:</strong> ${data.employee.position_name || 'N/A'}</p>
            <p class="text-muted"><strong>입사일:</strong> ${data.employee.hire_date || 'N/A'}</p>
        ` : '<p class="text-muted">연결된 직원 정보가 없습니다.</p>';

        container.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="${data.user.profile_image_url || '/assets/images/users/avatar.png'}" alt="Profile Image" class="rounded-circle avatar-lg img-thumbnail me-4">
                <div>
                    <h4 class="mb-1">${data.user.nickname}</h4>
                    <p class="text-muted mb-1"><strong>사용자명:</strong> ${data.user.username}</p>
                    <p class="text-muted mb-0"><strong>이메일:</strong> ${data.user.email || 'N/A'}</p>
                </div>
            </div>
            <hr>
            <h5>직원 정보</h5>
            ${employeeInfo}
        `;
    }

    renderError(errorMessage) {
        const container = document.getElementById('profile-container');
        if (!container) return;
        container.innerHTML = `<div class="alert alert-danger">프로필 정보를 불러오는 데 실패했습니다: ${errorMessage}</div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ProfileApp();
});