<?php
// app/Views/pages/hr/history.php
\App\Core\View::getInstance()->startSection('content');
?>
<div class="container-fluid">
    <h1>인사 발령 기록 (직원 ID: <?= htmlspecialchars($employee_id) ?>)</h1>
    <p>이곳에 해당 직원의 인사 발령 기록이 표시됩니다.</p>

    <table class="table">
        <thead>
            <tr>
                <th>발령일</th>
                <th>변경 항목</th>
                <th>변경 전</th>
                <th>변경 후</th>
                <th>처리자</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)): ?>
                <tr>
                    <td colspan="5">인사 발령 기록이 없습니다.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($history as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($log['changed_at']))) ?></td>
                        <td><?= htmlspecialchars($log['field_name']) ?></td>
                        <td><?= htmlspecialchars($log['old_value']) ?></td>
                        <td><?= htmlspecialchars($log['new_value']) ?></td>
                        <td><?= htmlspecialchars($log['changer_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php \App.Core\View::getInstance()->endSection(); ?>
