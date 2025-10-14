class OrganizationChartPage extends BasePage {
    constructor() {
        super();
    }
    
    loadInitialData() {
        this.initializeChart();
    }
    
    async initializeChart() {
        try {
            await this.loadGoogleCharts();
            const chartData = await this.fetchChartData();
            this.drawChart(chartData);
        } catch (error) {
            console.error('Error initializing chart:', error);
            this.showError(`차트를 불러오는 중 오류가 발생했습니다: ${error.message}`);
        }
    }
    
    loadGoogleCharts() {
        return new Promise((resolve, reject) => {
            if (document.querySelector('script[src="https://www.gstatic.com/charts/loader.js"]')) {
                google.charts.load('current', { packages: ['orgchart'] });
                google.charts.setOnLoadCallback(resolve);
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://www.gstatic.com/charts/loader.js';
            script.onload = () => {
                google.charts.load('current', { packages: ['orgchart'] });
                google.charts.setOnLoadCallback(resolve);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    async fetchChartData() {
        const response = await this.apiService.request('/organization/chart');
        if (!response?.success || !Array.isArray(response?.data)) {
            throw new Error(response?.message || 'Invalid chart data received from server.');
        }
        return response.data;
    }
    
    drawChart(treeData) {
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Name');
        data.addColumn('string', 'Manager');
        data.addColumn('string', 'ToolTip');
        
        if (treeData.length === 0) {
            document.getElementById('chart_div').innerHTML = '<p class="text-center">조직도 데이터가 없습니다.</p>';
            return;
        }
        
        const rows = this.buildRows(treeData);
        data.addRows(rows);
        
        const chart = new google.visualization.OrgChart(document.getElementById('chart_div'));
        chart.draw(data, { allowHtml: true });
    }
    
    buildRows(nodes, parentId = '') {
        let rows = [];
        for (const node of nodes) {
            const nodeId = `dept_${node.id}`;
            const managerName = node.manager_name ? `<br/><b style='color: #28a745;'>부서장: ${node.manager_name}</b>` : '';
            const employees = node.employees.map(e => `<li>${e.name} (${e.position || '직책없음'})</li>`).join('');
            const nodeHtml = `
                <div class="org-chart-node">
                    <div class="node-name">${node.name}</div>
                    ${managerName}
                    <ul class="employee-list">${employees}</ul>
                </div>
            `;
            rows.push([{ v: nodeId, f: nodeHtml }, parentId, node.name]);
            if (node.children && node.children.length > 0) {
                rows = rows.concat(this.buildRows(node.children, nodeId));
            }
        }
        return rows;
    }
    
    showError(message) {
        const chartDiv = document.getElementById('chart_div');
        if (chartDiv) {
            chartDiv.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
        }
    }
}

const page = new OrganizationChartPage();