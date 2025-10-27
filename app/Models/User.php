<?php

namespace App\Models;

/**
 * 사용자 모델 클래스
 *
 * @property int $id
 * @property int $kakao_id
 * @property string $nickname
 * @property string $email
 * @property string $status
 * @property int|null $employee_id
 * @property string $created_at
 * @property string $updated_at
 */
class User extends BaseModel
{
    protected array $fillable = [
        'id',
        'kakao_id',
        'nickname',
        'email',
        'status',
        'employee_id',
        'created_at',
        'updated_at'
    ];

    protected array $rules = [
        'nickname' => 'required|string|max:255',
        'email' => 'email|max:255',
        'status' => 'required|in:대기,활성,비활성,삭제,차단'
    ];

    // 직접적인 DB 쿼리를 포함했던 findOrCreateFromKakao 메서드는
    // 관심사를 적절하게 분리하기 위해 UserRepository로 이동되었습니다.
    // 이 모델은 더 이상 직접적인 데이터베이스 종속성을 갖지 않습니다.
}
