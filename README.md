# 시스템 개발 기초 문서

## 1. 프로젝트 개요
- **목적**: 클린박스/부적정 배출 관리 및 직원 관리 시스템  
- **개발자**: 1인  
- **환경**:
  - Web Server: Nginx
  - Backend: PHP 8.2+
  - Database: MariaDB 10.6+
  - 구조: MVC
  - URL: 짧은 주소 지원
  - 인증: 카카오 로그인 단일 인증
  - 권한: Role + Rule 기반 퍼미션
  - 로깅: 사용자 행위 기록

## 2. 기술 스택
| 구분 | 기술 | 설명 |
|------|------|------|
| 서버 | Nginx | PHP-FPM 연동 |
| 언어 | PHP 8.2+ | MVC 기반 |
| DB | MariaDB 10.6+ | PDO 사용, Singleton 패턴으로 관리 |
| 인증 | 카카오 OAuth2 | 단일 로그인 |
| 권한 | Role + Rule | 세부 행위 단위 권한 관리 |
| API | RESTful | `/api/v1/...` |
| 환경 | dotenv | 환경 변수 관리 |
| 로깅 | DB 기록 | sys_activity_logs 테이블 |

## 3. 디렉토리 구조
\`\`\`
project_root/
├─ public/            # index.php, assets
├─ app/
│   ├─ Controllers/
│   ├─ Repositories/  # 데이터베이스 로직
│   ├─ Views/
│   ├─ Core/          # Router, DB(Singleton), SessionManager 등 핵심 클래스
│   ├─ Services/      # 비즈니스 로직 (인증, 권한, 메뉴 관리 등)
│   └─ Middleware/
├─ config/            # config.php, routes.php
├─ storage/           # 로그, 캐시
├─ vendor/
└─ .env
\`\`\`

## 4. 아키텍처
| 구성요소 | 설명 |
|-----------|------|
| Controller | 요청 처리, Service 호출, View/JSON 반환 |
| Service | 비즈니스 로직, Repository를 통해 데이터 처리 |
| Repository | 데이터베이스 상호작용 로직 캡슐화 |
| View | HTML/PHP 템플릿 |
| Core | Router, DB(Singleton), SessionManager 등 핵심 기능 |

## 5. 인증 및 권한

### 5-1. 인증
- **카카오 OAuth2 기반 단일 로그인**  
- `AuthService`를 통해 모든 인증 로직 처리
- 세션에 사용자 정보 저장  
- 로그인/로그아웃 기록 `sys_activity_logs`에 저장

### 5-2. 권한
- **Role 기반**: `admin`, `staff` 등 `sys_roles` 테이블에서 관리
- **Permission 기반**: 세부 행위 단위 권한 (`sys_permissions`)
  - 예: `waste_create`, `waste_delete`, `user_manage`  
- 모든 권한 확인은 `AuthService`의 `check()` 메서드를 통해 수행

```php
// 컨트롤러 또는 미들웨어 내에서
$authService = new \App\Services\AuthService();
if (!$authService->check('waste_delete')) {
    // 권한 없음 처리
    http_response_code(403);
    exit('권한이 없습니다.');
}
