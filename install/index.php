<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>애플리케이션 설치</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="installer-container">
        <h1>애플리케이션 설치 프로그램</h1>
        <div id="progress-container">
            <!-- Progress updates will be inserted here by JavaScript -->
        </div>
        <button id="start-install-btn">설치 시작</button>
    </div>
    <script>
        document.getElementById('start-install-btn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = '설치 중...';
            const progressContainer = document.getElementById('progress-container');
            progressContainer.innerHTML = ''; // Clear previous logs

            const eventSource = new EventSource('run.php');

            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                const p = document.createElement('p');
                p.textContent = data.message;
                p.className = data.type; // success, error, info
                progressContainer.appendChild(p);
                progressContainer.scrollTop = progressContainer.scrollHeight;

                if (data.completed) {
                    eventSource.close();
                    document.getElementById('start-install-btn').textContent = '설치 완료';
                }
            };

            eventSource.onerror = function() {
                const p = document.createElement('p');
                p.textContent = '설치 중 오류가 발생했습니다. 서버 로그를 확인하세요.';
                p.className = 'error';
                progressContainer.appendChild(p);
                eventSource.close();
                document.getElementById('start-install-btn').textContent = '오류 발생';
            };
        });
    </script>
</body>
</html>
