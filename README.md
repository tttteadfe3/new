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
| DB | MariaDB 10.6+ | PDO 사용 |
| 인증 | 카카오 OAuth2 | 단일 로그인 |
| 권한 | Role + Rule | 세부 행위 단위 권한 관리 |
| API | RESTful | `/api/v1/...` |
| 환경 | dotenv | 환경 변수 관리 |
| 로깅 | DB 기록 | user_logs 테이블 |

## 3. 디렉토리 구조
\`\`\`
project_root/
├─ public/            # index.php, assets
├─ app/
│   ├─ Controllers/
│   ├─ Models/
│   ├─ Views/
│   ├─ Core/          # Router, DB, AuthManager, PermissionManager, ExceptionHandler
│   ├─ Services/      # 카카오 API, Rule/Permission 관리
│   └─ Helpers/
├─ config/            # config.php, routes.php
├─ storage/           # 로그, 캐시
├─ vendor/
└─ .env
\`\`\`

## 4. MVC 구조
| 구성요소 | 설명 |
|-----------|------|
| Controller | 요청 처리, Model 호출, View/JSON 반환 |
| Model | DB 처리, 비즈니스 로직 |
| View | HTML/PHP 템플릿 |
| Core | Router, DB, AuthManager, PermissionManager, ExceptionHandler, Logger |
| Service | 카카오 로그인, Rule/Permission 관리, 공용 로직 |

## 5. 인증 및 권한

### 5-1. 인증
- **카카오 OAuth2 기반 단일 로그인**  
- 세션에 사용자 정보 저장  
- 로그인/로그아웃 기록 `user_logs`에 저장

### 5-2. 권한
- **Role 기반**: `admin`, `staff`  
- **Rule 기반 퍼미션**: 세부 행위 단위 권한  
  - 예: `waste_create`, `waste_delete`, `user_manage`  

```php
$perm = new PermissionManager(AuthManager::user());
if (!$perm->can('waste_delete')) {
    header("HTTP/1.1 403 Forbidden");
    exit('권한이 없습니다.');
}
