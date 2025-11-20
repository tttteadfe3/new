/**
 * Supply Plans Import JavaScript
 */

class SupplyPlansImportPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/plans'
        });
    }

    setupEventListeners() {
        const importForm = document.getElementById('importForm');
        importForm?.addEventListener('submit', (e) => this.handleImportSubmit(e));

        const downloadTemplateBtn = document.getElementById('download-template-btn');
        downloadTemplateBtn?.addEventListener('click', () => this.downloadTemplate());
    }

    async handleImportSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const uploadBtn = document.getElementById('upload-btn');

        this.setButtonLoading(uploadBtn, '업로드 중...');
        Toast.info('파일 업로드를 시작합니다...');

        try {
            const response = await this.apiCall(`${this.config.API_URL}/import`, {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                Toast.success('파일이 성공적으로 업로드되었습니다.');
                this.displayUploadResult(response.data);
            } else {
                Toast.error(response.message || '파일 업로드에 실패했습니다.');
            }
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading(uploadBtn, '업로드 및 가져오기');
        }
    }

    displayUploadResult(data) {
        const resultContainer = document.getElementById('upload-result');
        const summaryContainer = document.getElementById('result-summary');
        const detailsContainer = document.getElementById('result-details');

        if (!resultContainer || !summaryContainer || !detailsContainer) return;

        summaryContainer.innerHTML = `
            <div class="alert alert-success">
                <strong>성공: ${data.success_count}건</strong> |
                <strong>실패: ${data.error_count}건</strong> |
                <strong>중복: ${data.duplicate_count}건</strong>
            </div>
        `;

        if (data.errors && data.errors.length > 0) {
            const errorList = data.errors.map(err =>
                `<li><strong>${err.line}행:</strong> ${this.escapeHtml(err.error)}</li>`
            ).join('');
            detailsContainer.innerHTML = `
                <h6>오류 목록</h6>
                <ul class="list-unstyled text-danger">${errorList}</ul>
            `;
        } else {
            detailsContainer.innerHTML = '';
        }

        resultContainer.style.display = 'block';
    }

    downloadTemplate() {
        const year = document.querySelector('input[name="year"]').value || new Date().getFullYear();
        const headers = 'year,item_code,planned_quantity,unit_price,notes';
        const example = `${year},ITEM001,100,5000,비고`;
        const csvContent = `data:text/csv;charset=utf-8,${headers}\n${example}`;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `supply_plan_template_${year}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        Toast.success('템플릿 파일이 다운로드되었습니다.');
    }
}

new SupplyPlansImportPage();
