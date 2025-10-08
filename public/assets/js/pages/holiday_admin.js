document.addEventListener('DOMContentLoaded', function () {
    const holidayModalEl = document.getElementById('holidayModal');
    const holidayModal = new bootstrap.Modal(holidayModalEl);
    const API_URL = '/api/holidays';

    flatpickr("#holidayDate", { dateFormat: "Y-m-d" });

    const addHolidayBtn = document.getElementById('add-holiday-btn');
    const saveHolidayBtn = document.getElementById('saveHolidayBtn');
    const tableBody = document.getElementById('holidays-table-body');

    const holidayForm = document.getElementById('holidayForm');
    const holidayIdInput = document.getElementById('holidayId');
    const holidayNameInput = document.getElementById('holidayName');
    const holidayDateInput = document.getElementById('holidayDate');
    const holidayTypeInput = document.getElementById('holidayType');
    const departmentIdInput = document.getElementById('departmentId');
    const deductLeaveInput = document.getElementById('deductLeave');
    const holidayModalLabel = document.getElementById('holidayModalLabel');

    function escapeHtml(text) {
        if (text === null || typeof text === 'undefined') return '';
        const map = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    function populateTable(holidays) {
        tableBody.innerHTML = '';
        if (!holidays || holidays.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">등록된 휴일이 없습니다.</td></tr>';
            return;
        }
        holidays.forEach(h => {
            const row = `
                <tr>
                    <td>${escapeHtml(h.name)}</td>
                    <td>${h.date}</td>
                    <td>${h.type === 'holiday' ? '휴일' : '특정 근무일'}</td>
                    <td>${h.department_name ? escapeHtml(h.department_name) : '전체 부서'}</td>
                    <td>${h.deduct_leave == 1 ? '차감' : '미차감'}</td>
                    <td>
                        <button class="btn btn-sm btn-soft-info edit-btn" data-id="${h.id}">수정</button>
                        <button class="btn btn-sm btn-soft-danger delete-btn" data-id="${h.id}">삭제</button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    function populateDepartmentSelect(departments) {
        while (departmentIdInput.options.length > 1) {
            departmentIdInput.remove(1);
        }
        departments.forEach(d => {
            const option = new Option(escapeHtml(d.name), d.id);
            departmentIdInput.add(option);
        });
    }

    async function loadData() {
        try {
            const response = await fetch(API_URL, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            populateTable(result.data.holidays);
            populateDepartmentSelect(result.data.departments);
        } catch (error) {
            Toast.error('데이터 로딩에 실패했습니다.');
            console.error(error);
        }
    }

    addHolidayBtn.addEventListener('click', () => {
        holidayForm.reset();
        holidayIdInput.value = '';
        holidayModalLabel.textContent = '휴일/근무일 등록';
        holidayModal.show();
    });

    tableBody.addEventListener('click', async (event) => {
        const target = event.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const response = await fetch(`${API_URL}/${id}`, fetchOptions());
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                const holiday = result.data;
                holidayIdInput.value = holiday.id;
                holidayNameInput.value = holiday.name;
                holidayDateInput.value = holiday.date;
                holidayTypeInput.value = holiday.type;
                departmentIdInput.value = holiday.department_id || '';
                deductLeaveInput.checked = holiday.deduct_leave == 1;
                holidayModalLabel.textContent = '휴일/근무일 수정';
                holidayModal.show();
            } catch (error) {
                Toast.error('정보를 불러오는 데 실패했습니다.');
                console.error(error);
            }
        } else if (target.classList.contains('delete-btn')) {
            const result = await Confirm.fire('삭제 확인', '정말 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`${API_URL}/${id}`, fetchOptions({ method: 'DELETE' }));
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    Toast.success(result.message);
                    loadData();
                } catch (error) {
                    Toast.error(error.message || '삭제에 실패했습니다.');
                }
            }
        }
    });

    saveHolidayBtn.addEventListener('click', async () => {
        const id = holidayIdInput.value;
        const holidayData = {
            name: holidayNameInput.value,
            date: holidayDateInput.value,
            type: holidayTypeInput.value,
            department_id: departmentIdInput.value ? parseInt(departmentIdInput.value) : null,
            deduct_leave: deductLeaveInput.checked
        };

        const url = id ? `${API_URL}/${id}` : API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, fetchOptions({
                method: method,
                body: JSON.stringify(holidayData)
            }));
            const result = await response.json();
            if (!result.success) {
                // Handle validation errors
                if (result.errors) {
                    const errorMessages = Object.values(result.errors).join('\n');
                    throw new Error(errorMessages);
                }
                throw new Error(result.message);
            }
            holidayModal.hide();
            Toast.success(result.message);
            loadData();
        } catch (error) {
            Toast.error(error.message || '저장에 실패했습니다.');
        }
    });

    loadData();
});