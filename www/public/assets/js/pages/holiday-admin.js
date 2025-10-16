class HolidayAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/holidays'
        });

        this.state = {
            ...this.state,
            holidayModal: null,
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.holidayModal = new bootstrap.Modal(this.elements.modal);
        flatpickr(this.elements.holidayDate, { dateFormat: "Y-m-d" });
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            modal: document.getElementById('holidayModal'),
            form: document.getElementById('holidayForm'),
            modalLabel: document.getElementById('holidayModalLabel'),
            addBtn: document.getElementById('add-holiday-btn'),
            saveBtn: document.getElementById('saveHolidayBtn'),
            tableBody: document.getElementById('holidays-table-body'),
            holidayId: document.getElementById('holidayId'),
            holidayName: document.getElementById('holidayName'),
            holidayDate: document.getElementById('holidayDate'),
            holidayType: document.getElementById('holidayType'),
            departmentId: document.getElementById('departmentId'),
            deductLeave: document.getElementById('deductLeave'),
        };
    }

    setupEventListeners() {
        this.elements.addBtn.addEventListener('click', () => this.openHolidayModal());
        this.elements.saveBtn.addEventListener('click', () => this.handleSave());
        this.elements.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall(this.config.API_URL);
            const { holidays, departments } = response.data;
            this.renderTable(holidays);
            this.populateDepartmentDropdown(departments);
        } catch (error) {
            Toast.error('데이터 로딩에 실패했습니다.');
            console.error(error);
        }
    }

    renderTable(holidays) {
        this.elements.tableBody.innerHTML = '';
        if (!holidays || holidays.length === 0) {
            this.elements.tableBody.innerHTML = '<tr><td colspan="6" class="text-center">등록된 휴일이 없습니다.</td></tr>';
            return;
        }
        const rowsHtml = holidays.map(h => `
            <tr>
                <td>${this._sanitizeHTML(h.name)}</td>
                <td>${h.date}</td>
                <td>${h.type === 'holiday' ? '휴일' : '특정 근무일'}</td>
                <td>${h.department_name ? this._sanitizeHTML(h.department_name) : '전체 부서'}</td>
                <td>${h.deduct_leave == 1 ? '차감' : '미차감'}</td>
                <td>
                    <button class="btn btn-sm btn-soft-info edit-btn" data-id="${h.id}">수정</button>
                    <button class="btn btn-sm btn-soft-danger delete-btn" data-id="${h.id}">삭제</button>
                </td>
            </tr>
        `).join('');
        this.elements.tableBody.innerHTML = rowsHtml;
    }

    populateDepartmentDropdown(departments) {
        const select = this.elements.departmentId;
        while (select.options.length > 1) {
            select.remove(1);
        }
        departments.forEach(d => {
            const option = new Option(this._sanitizeHTML(d.name), d.id);
            select.add(option);
        });
    }

    openHolidayModal(holiday = null) {
        this.elements.form.reset();
        if (holiday) {
            this.elements.modalLabel.textContent = '휴일/근무일 수정';
            this.elements.holidayId.value = holiday.id;
            this.elements.holidayName.value = holiday.name;
            this.elements.holidayDate.value = holiday.date;
            this.elements.holidayType.value = holiday.type;
            this.elements.departmentId.value = holiday.department_id || '';
            this.elements.deductLeave.checked = holiday.deduct_leave == 1;
        } else {
            this.elements.modalLabel.textContent = '휴일/근무일 등록';
            this.elements.holidayId.value = '';
        }
        this.state.holidayModal.show();
    }

    async handleTableClick(event) {
        const target = event.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const response = await this.apiCall(`${this.config.API_URL}/${id}`);
                this.openHolidayModal(response.data);
            } catch (error) {
                Toast.error('정보를 불러오는 데 실패했습니다.');
            }
        } else if (target.classList.contains('delete-btn')) {
            this.deleteHoliday(id);
        }
    }

    async deleteHoliday(id) {
        const result = await Confirm.fire('삭제 확인', '정말 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');
        if (result.isConfirmed) {
            try {
                const response = await this.apiCall(`${this.config.API_URL}/${id}`, { method: 'DELETE' });
                Toast.success(response.message);
                this.loadInitialData();
            } catch (error) {
                Toast.error(error.message || '삭제에 실패했습니다.');
            }
        }
    }

    async handleSave() {
        const id = this.elements.holidayId.value;
        const holidayData = {
            name: this.elements.holidayName.value,
            date: this.elements.holidayDate.value,
            type: this.elements.holidayType.value,
            department_id: this.elements.departmentId.value ? parseInt(this.elements.departmentId.value) : null,
            deduct_leave: this.elements.deductLeave.checked
        };

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const response = await this.apiCall(url, { method, body: holidayData });
            this.state.holidayModal.hide();
            Toast.success(response.message);
            this.loadInitialData();
        } catch (error) {
             // Handle validation errors specifically if the server returns them
            if (error.data && error.data.errors) {
                const errorMessages = Object.values(error.data.errors).join('\n');
                Toast.error(errorMessages);
            } else {
                Toast.error(error.message || '저장에 실패했습니다.');
            }
        }
    }

    _sanitizeHTML(text) {
        if (text === null || typeof text === 'undefined') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
}

new HolidayAdminPage();