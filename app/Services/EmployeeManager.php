<?php
// app/Services/EmployeeManager.php
namespace App\Services;
use App\Repositories\EmployeeRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Core\SessionManager;

class EmployeeManager {
    /**
     * 데이터 변경 내역을 찾아 employee_change_logs 테이블에 기록하는 헬퍼 함수
     */
    private function logChanges(int $employeeId, array $oldData, array $newData, int $changerId) {
        $fields = [
            'name' => '이름', 'employee_number' => '사번', 'hire_date' => '입사일',
            'phone_number' => '연락처', 'address' => '주소',
            'emergency_contact_name' => '비상연락처', 'emergency_contact_relation' => '관계',
            'clothing_top_size' => '상의', 'clothing_bottom_size' => '하의', 'shoe_size' => '신발',
            'department_id' => '부서', 'position_id' => '직급'
        ];

        foreach ($fields as $key => $label) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;
            if (isset($newData[$key]) && (string)$oldValue !== (string)$newValue) {

                if ($key === 'department_id') {
                    $oldValue = $oldData['department_name'] ?? $oldValue;
                    $newValue = \App\Repositories\DepartmentRepository::findById($newValue)['name'] ?? $newValue;
                } elseif ($key === 'position_id') {
                    $oldValue = $oldData['position_name'] ?? $oldValue;
                    $newValue = \App\Repositories\PositionRepository::findById($newValue)['name'] ?? $newValue;
                }

                EmployeeChangeLogRepository::insert(
                    $employeeId, $changerId, $label, $oldValue, $newValue
                );
            }
        }
    }

    /**
     * 사용자의 프로필 변경 요청을 승인하고, 변경된 내용을 상세 로그로 기록 (수정된 핵심 로직)
     */
    public function approveProfileUpdate(int $employeeId): bool {
        // 1. **FIRST:** DB를 업데이트하기 전에, 현재 상태(변경 전 값)를 스냅샷으로 찍어 변수에 저장합니다.
        $oldData = EmployeeRepository::findById($employeeId);
        if (!$oldData || $oldData['profile_update_status'] !== 'pending' || empty($oldData['pending_profile_data'])) {
            return false;
        }
        
        // 2. 사용자가 요청한 변경 데이터를 pending 컬럼에서 가져옵니다.
        $newDataFromRequest = json_decode($oldData['pending_profile_data'], true);
        
        // 3. DB 업데이트를 위해 현재 데이터와 요청 데이터를 합쳐 완전한 새 데이터를 만듭니다.
        $fullNewData = array_merge($oldData, $newDataFromRequest);

        // 4. **THEN:** 이제 DB에 실제 변경사항을 적용합니다.
        $success = EmployeeRepository::applyProfileUpdate($employeeId, $fullNewData);
        
        // 5. **FINALLY:** 성공했다면, 미리 찍어둔 스냅샷($oldData)과 요청 데이터($newDataFromRequest)를 비교하여 로그를 기록합니다.
        if ($success) {
            $adminUser = SessionManager::get('user');
            $this->logChanges($employeeId, $oldData, $newDataFromRequest, $adminUser['id']);
            
            \App\Repositories\LogRepository::insert([
                ':user_id' => $adminUser['id'],
                ':user_name' => $adminUser['nickname'],
                ':action' => '프로필 변경 승인',
                ':details' => "직원 '{$oldData['name']}'(id:{$employeeId})의 프로필 변경 요청을 승인했습니다.",
                ':ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        }
        return $success;
    }

    /**
     * 관리자가 직접 직원 정보를 저장/수정하고 변경 이력을 기록
     */
    public function save(array $data): ?string {
        $employeeId = $data['id'] ?? null;
        $oldData = $employeeId ? EmployeeRepository::findById($employeeId) : [];
        
        $savedId = EmployeeRepository::save($data);
        $newEmployeeId = $employeeId ?: $savedId;

        if ($newEmployeeId && $oldData) { // 수정일 경우에만 로그 기록
            $adminUser = SessionManager::get('user');
            $this->logChanges((int)$newEmployeeId, $oldData, $data, $adminUser['id']);
        }
        return $savedId;
    }

    public function remove(int $id): bool {
        return EmployeeRepository::delete($id);
    }
}