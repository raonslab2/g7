-- G7 로컬 운영 DB 구조 스냅샷 (데이터 없음)
-- 추출일: 2026-10-04 / 원본: MySQL 8.0.46 / DB 접두사: g7_
-- 원본 소스 커밋: c9e93660
-- 테이블 111개. 빈 DB에만 적용. deploy/database/README.md 참조.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '활동 로그 ID',
  `log_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '로그 유형 (admin: 관리자, user: 사용자, system: 시스템)',
  `loggable_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loggable_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT '사용자 ID',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '액션 유형 (created, updated, deleted, login, export 등)',
  `description_key` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '다국어 번역 키 (예: activity_log.description.user_create)',
  `description_params` text COLLATE utf8mb4_unicode_ci COMMENT '다국어 번역 파라미터 (예: {"user_id": "abc-123"})',
  `properties` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '변경 상세 데이터 (old/new 값)',
  `changes` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '구조화된 변경 이력 (필드별 label_key, old, new, type)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP 주소 (IPv6 대응)',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '접속 브라우저 정보 (User Agent)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`id`),
  KEY `g7_activity_logs_loggable_type_loggable_id_index` (`loggable_type`,`loggable_id`),
  KEY `g7_activity_logs_log_type_index` (`log_type`),
  KEY `g7_activity_logs_user_id_index` (`user_id`),
  KEY `g7_activity_logs_action_index` (`action`),
  KEY `idx_activity_logs_loggable` (`loggable_type`,`loggable_id`,`created_at`),
  KEY `idx_activity_logs_type_action_date` (`log_type`,`action`,`created_at`),
  KEY `idx_activity_logs_description_key` (`description_key`),
  KEY `idx_activity_logs_created_id` (`created_at`,`id`),
  CONSTRAINT `g7_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '첨부파일 ID',
  `attachmentable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '첨부 대상 모델 타입 (예: App\\Models\\Post)',
  `attachmentable_id` bigint unsigned DEFAULT NULL COMMENT '첨부 대상 모델 ID',
  `source_type` enum('core','module','plugin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '소스 타입: core(코어), module(모듈), plugin(플러그인)',
  `source_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '모듈/플러그인 식별자 (예: sirsoft-board)',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '스토리지 디스크 (local, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, application/pdf)',
  `size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default' COMMENT '첨부파일 컬렉션/그룹명',
  `order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `meta` text COLLATE utf8mb4_unicode_ci COMMENT '추가 메타데이터 JSON',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_attachments_hash_unique` (`hash`),
  KEY `g7_attachments_created_by_foreign` (`created_by`),
  KEY `idx_attachmentable` (`attachmentable_type`,`attachmentable_id`),
  KEY `idx_source` (`source_type`,`source_identifier`),
  KEY `idx_collection` (`collection`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `g7_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='첨부파일 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '첨부파일 ID',
  `board_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '게시판 ID (파티션 키, 0: 임시 업로드)',
  `post_id` bigint unsigned DEFAULT NULL COMMENT '게시글 ID (임시 업로드 시 null)',
  `temp_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '임시 업로드 키 (게시글 저장 후 null)',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '스토리지 디스크 (local, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, application/pdf)',
  `size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default' COMMENT '첨부파일 컬렉션/그룹명',
  `order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `meta` text COLLATE utf8mb4_unicode_ci COMMENT '추가 메타데이터 JSON (썸네일 경로, 이미지 크기 등)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '업로더 ID',
  `trigger_type` enum('report','admin','system','auto_hide','user','cascade') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '삭제 조치 주체 (admin: 관리자, user: 사용자 직접 삭제, cascade: 게시글 삭제 연쇄)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_board_hash` (`board_id`,`hash`),
  KEY `idx_board_post` (`board_id`,`post_id`),
  KEY `idx_board_temp_key` (`board_id`,`temp_key`),
  KEY `idx_collection` (`collection`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_hash` (`hash`),
  KEY `idx_board_attachments_post_id` (`post_id`),
  KEY `idx_board_post_trigger` (`board_id`,`post_id`,`trigger_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '댓글 ID',
  `board_id` bigint unsigned NOT NULL COMMENT '게시판 ID (파티션 키)',
  `post_id` bigint unsigned NOT NULL COMMENT '게시글 ID',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '작성자 ID (회원)',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '부모 댓글 ID (답글용)',
  `author_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '작성자명 (비회원용)',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비밀번호 (비회원용, 해시 저장)',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '댓글 내용',
  `is_secret` tinyint(1) NOT NULL DEFAULT '0' COMMENT '비밀 댓글 여부 (1: 비밀, 0: 공개)',
  `status` enum('published','blinded','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published' COMMENT '댓글 상태 (published: 게시됨, blinded: 블라인드 처리됨, deleted: 삭제됨)',
  `replies_count` int unsigned NOT NULL DEFAULT '0' COMMENT '대댓글 수',
  `trigger_type` enum('report','admin','system','auto_hide','user','cascade') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '조치 주체 (report: 신고, admin: 관리자, system: 시스템, auto_hide: 자동 블라인드, user: 사용자 직접 삭제, cascade: 게시글 삭제 연쇄)',
  `action_logs` text COLLATE utf8mb4_unicode_ci COMMENT '작업 이력 배열 (JSON)',
  `depth` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '댓글 깊이 (0: 댓글, 1: 답글)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '작성자 IP',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_board_comments_deleted_at_index` (`deleted_at`),
  KEY `idx_user_post` (`user_id`,`post_id`),
  KEY `idx_user_created_at` (`user_id`,`created_at`),
  KEY `g7_board_comments_parent_id_index` (`parent_id`),
  KEY `idx_board_comments_board_parent` (`board_id`,`parent_id`),
  KEY `idx_board_comments_post_created` (`board_id`,`post_id`,`created_at`),
  KEY `idx_board_comments_user_board` (`user_id`,`board_id`),
  KEY `idx_board_comments_post_deleted_created` (`board_id`,`post_id`,`deleted_at`,`created_at`),
  KEY `idx_board_comments_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '게시글 ID',
  `board_id` bigint unsigned NOT NULL COMMENT '게시판 ID (파티션 키)',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '분류',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '제목',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '내용',
  `content_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '콘텐츠 모드: text(텍스트), html(HTML)',
  `content_thumbnail_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '본문 첫 내부 이미지 URL 캐시 — 이미지 첨부가 없을 때 목록 썸네일 폴백',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '회원 ID',
  `author_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '작성자명 (비회원)',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비밀번호 (비회원, bcrypt)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP 주소',
  `is_notice` tinyint(1) NOT NULL DEFAULT '0' COMMENT '공지사항 여부 (1: 공지, 0: 일반)',
  `is_secret` tinyint(1) NOT NULL DEFAULT '0' COMMENT '비밀글 여부 (1: 비밀, 0: 공개)',
  `status` enum('published','blinded','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published' COMMENT '게시글 상태 (published: 게시됨, blinded: 블라인드 처리됨, deleted: 삭제됨)',
  `trigger_type` enum('report','admin','system','auto_hide','user','cascade') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '조치 주체 (report: 신고, admin: 관리자, system: 시스템, auto_hide: 자동 블라인드, user: 사용자 직접 삭제, cascade: 원글 삭제 연쇄)',
  `action_logs` text COLLATE utf8mb4_unicode_ci COMMENT '작업 이력 배열 (JSON)',
  `view_count` int unsigned NOT NULL DEFAULT '0' COMMENT '조회수',
  `replies_count` int unsigned NOT NULL DEFAULT '0' COMMENT '답글 수',
  `comments_count` int unsigned NOT NULL DEFAULT '0' COMMENT '댓글 수',
  `attachments_count` int unsigned NOT NULL DEFAULT '0' COMMENT '첨부파일 수',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '부모 게시글 ID (답글용)',
  `depth` int unsigned NOT NULL DEFAULT '0' COMMENT '답글 깊이 (0: 원글, 1+: 답글)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_board_posts_deleted_at_index` (`deleted_at`),
  KEY `idx_board_created` (`board_id`,`created_at`),
  KEY `idx_board_posts_board_author` (`board_id`,`author_name`),
  KEY `idx_board_posts_board_status_created` (`board_id`,`status`,`created_at`),
  KEY `idx_board_posts_board_parent` (`board_id`,`parent_id`),
  KEY `idx_board_posts_board_category` (`board_id`,`category`,`created_at`),
  KEY `idx_board_posts_board_view_count` (`board_id`,`view_count`),
  KEY `idx_board_posts_user_activity` (`user_id`,`deleted_at`,`board_id`,`created_at`),
  KEY `idx_board_posts_user_created` (`user_id`,`deleted_at`,`created_at`),
  KEY `idx_board_posts_user_board_stats` (`user_id`,`board_id`,`comments_count`,`view_count`),
  KEY `idx_board_posts_adjacent` (`board_id`,`status`,`is_notice`,`parent_id`,`deleted_at`,`created_at`,`id`),
  KEY `idx_board_posts_user_status` (`user_id`,`status`),
  KEY `idx_board_posts_recent_across_boards` (`deleted_at`,`parent_id`,`created_at`),
  KEY `idx_board_posts_list_count_id` (`board_id`,`is_notice`,`parent_id`,`deleted_at`,`created_at`,`id`),
  KEY `idx_board_posts_list_views` (`board_id`,`is_notice`,`parent_id`,`deleted_at`,`view_count`,`id`),
  FULLTEXT KEY `ft_board_posts_title_content` (`title`,`content`) /*!50100 WITH PARSER `ngram` */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '집계 행 ID',
  `date` date NOT NULL COMMENT '집계 기준 날짜 (하루 1행, 멱등 upsert 키)',
  `post_count` int unsigned NOT NULL DEFAULT '0' COMMENT '해당 날짜 작성 게시글 수 (deleted_at IS NULL)',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '해당 날짜 작성 댓글 수 (deleted_at IS NULL)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_board_stats_date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '게시판 유형 ID',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '유형 식별자 (basic, card, gallery 등)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '유형명 (다국어: {"ko": "기본형", "en": "Basic List"})',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name"])',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_board_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_board_user_notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '알림 설정 ID',
  `user_id` bigint unsigned NOT NULL,
  `notify_post_complete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '게시글 완료 알림',
  `notify_post_reply` tinyint(1) NOT NULL DEFAULT '0' COMMENT '게시글 답변 알림',
  `notify_comment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '댓글 알림',
  `notify_reply_comment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '답글 알림',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_board_user_notification_settings_user_id_unique` (`user_id`),
  CONSTRAINT `g7_board_user_notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 사용자 알림 설정';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_boards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '게시판 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '게시판명 (다국어 JSON)',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '게시판 슬러그 (URL/테이블명)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '게시판 설명 (다국어 JSON)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '게시판 활성화 여부 (1: 활성화, 0: 비활성화)',
  `posts_count` int unsigned NOT NULL DEFAULT '0' COMMENT '게시글 수',
  `comments_count` int unsigned NOT NULL DEFAULT '0' COMMENT '댓글 수',
  `per_page` int unsigned NOT NULL DEFAULT '20' COMMENT '페이지당 게시글 수 (PC)',
  `per_page_mobile` int unsigned NOT NULL DEFAULT '15' COMMENT '페이지당 게시글 수 (Mobile)',
  `order_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created_at' COMMENT '정렬 기준 (created_at, view_count, title, author)',
  `order_direction` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DESC' COMMENT '정렬 방향 (ASC, DESC)',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic' COMMENT '게시판 타입 (basic, gallery, card 등)',
  `categories` text COLLATE utf8mb4_unicode_ci COMMENT '분류 목록 (배열)',
  `show_view_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '조회수 노출',
  `secret_mode` enum('disabled','enabled','always') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disabled' COMMENT '비밀글 설정 (disabled: 사용안함, enabled: 사용함, always: 고정)',
  `use_comment` tinyint(1) NOT NULL DEFAULT '1' COMMENT '댓글 기능 사용',
  `use_reply` tinyint(1) NOT NULL DEFAULT '1' COMMENT '게시글 답변 기능 사용 (댓글에 대한 답글 아님)',
  `max_reply_depth` smallint unsigned NOT NULL DEFAULT '5' COMMENT '답변글 최대 깊이 (1~10)',
  `reply_delete_policy` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cascade' COMMENT '답글 삭제 정책 (block: 답글 있으면 삭제 차단, cascade: 함께 소프트 삭제)',
  `use_report` tinyint(1) NOT NULL DEFAULT '0' COMMENT '게시글/댓글 신고 기능 사용',
  `new_display_hours` int unsigned NOT NULL DEFAULT '24' COMMENT '신규 게시글 표시 기간 (시간 단위)',
  `min_title_length` int unsigned NOT NULL DEFAULT '2' COMMENT '최소 제목 글자 수',
  `max_title_length` int unsigned NOT NULL DEFAULT '200' COMMENT '최대 제목 글자 수',
  `min_content_length` int unsigned NOT NULL DEFAULT '10' COMMENT '최소 게시글 글자 수',
  `max_content_length` int unsigned NOT NULL DEFAULT '10000' COMMENT '최대 게시글 글자 수',
  `min_comment_length` int unsigned NOT NULL DEFAULT '2' COMMENT '최소 댓글 글자 수',
  `max_comment_length` int unsigned NOT NULL DEFAULT '1000' COMMENT '최대 댓글 글자 수',
  `use_file_upload` tinyint(1) NOT NULL DEFAULT '0' COMMENT '파일 업로드 사용',
  `max_file_size` int unsigned DEFAULT '10' COMMENT '최대 파일 크기 (MB)',
  `max_file_count` int unsigned DEFAULT '5' COMMENT '최대 파일 개수',
  `allowed_extensions` text COLLATE utf8mb4_unicode_ci COMMENT '허용 확장자 배열',
  `comment_order` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ASC' COMMENT '댓글 정렬 순서 (ASC: 오름차순, DESC: 내림차순)',
  `max_comment_depth` smallint unsigned NOT NULL DEFAULT '10' COMMENT '대댓글 최대 깊이 (1~10)',
  `notify_author` tinyint(1) NOT NULL DEFAULT '1' COMMENT '작성자 이메일 알림 (댓글, 대댓글, 답변글, 관리자 처리 시)',
  `notify_admin_on_post` tinyint(1) NOT NULL DEFAULT '1' COMMENT '관리자 이메일 알림 (게시글 등록 시)',
  `blocked_keywords` text COLLATE utf8mb4_unicode_ci COMMENT '금지어 목록 (배열)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_boards_slug_unique` (`slug`),
  KEY `g7_boards_updated_by_foreign` (`updated_by`),
  KEY `g7_boards_type_index` (`type`),
  KEY `g7_boards_created_by_index` (`created_by`),
  FULLTEXT KEY `ft_boards_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_boards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_boards_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 설정 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_boards_report_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '신고 로그 ID',
  `report_id` bigint unsigned NOT NULL COMMENT '케이스 ID (boards_reports.id)',
  `reporter_id` bigint unsigned DEFAULT NULL COMMENT '신고자 ID (탈퇴 시 NULL)',
  `snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '신고 당시 게시물 스냅샷 (JSON: board_name, title, content, content_mode, author_name)',
  `reason_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '신고 사유 유형 (abuse, hate_speech, spam, copyright, privacy, misinformation, sexual, violence, other)',
  `reason_detail` text COLLATE utf8mb4_unicode_ci COMMENT '신고 상세 사유 (신고자 입력)',
  `metadata` text COLLATE utf8mb4_unicode_ci COMMENT '메타데이터 (IP, User Agent 등)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_log` (`report_id`,`reporter_id`),
  KEY `idx_report` (`report_id`),
  KEY `idx_reporter` (`reporter_id`),
  FULLTEXT KEY `ft_boards_report_logs_snapshot` (`snapshot`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_boards_report_logs_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `g7_boards_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_boards_report_logs_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 신고 로그 (신고자별 기록)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_boards_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '신고 케이스 ID',
  `board_id` bigint unsigned DEFAULT NULL COMMENT '게시판 ID (게시판 삭제 시 NULL)',
  `target_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '신고 대상 타입 (post, comment)',
  `target_id` bigint unsigned NOT NULL COMMENT '신고 대상 ID (동적 테이블의 ID)',
  `author_id` bigint unsigned DEFAULT NULL COMMENT '작성자 ID (작성자 삭제 시 NULL)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '신고 상태 (pending, review, rejected, suspended)',
  `processed_by` bigint unsigned DEFAULT NULL COMMENT '처리자 ID',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT '처리 일시',
  `process_histories` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '처리 이력 배열 [{type, admin_id, admin_name, reason, reporter_count, created_at}]',
  `metadata` text COLLATE utf8mb4_unicode_ci COMMENT '메타데이터 (IP, User Agent 등)',
  `last_reported_at` timestamp NULL DEFAULT NULL COMMENT '마지막 신고 일시 — 목록 정렬용',
  `last_activated_at` timestamp NULL DEFAULT NULL COMMENT '케이스 재활성 일시 — 자동 블라인드 카운트 기준 (재신고 시 갱신)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_case` (`board_id`,`target_type`,`target_id`),
  KEY `idx_board` (`board_id`),
  KEY `idx_target` (`board_id`,`target_type`,`target_id`),
  KEY `idx_author` (`author_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_reported` (`last_reported_at`),
  KEY `g7_boards_reports_processed_by_foreign` (`processed_by`),
  KEY `idx_boards_reports_deleted_created_id` (`deleted_at`,`created_at`,`id`),
  CONSTRAINT `g7_boards_reports_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_boards_reports_board_id_foreign` FOREIGN KEY (`board_id`) REFERENCES `g7_boards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_boards_reports_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 신고 케이스 (게시글/댓글당 1행)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '캐시 키',
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '캐시 값',
  `expiration` int NOT NULL COMMENT '만료 시간',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 캐시 데이터';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '락 키',
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '락 소유자',
  `expiration` int NOT NULL COMMENT '만료 시간',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='캐시 락 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ckeditor5_image_uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `file_path` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 파일 경로',
  `storage_disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public' COMMENT '스토리지 디스크',
  `file_size` bigint unsigned NOT NULL COMMENT '파일 크기(bytes)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입',
  `uploaded_by` bigint unsigned DEFAULT NULL COMMENT '업로드 사용자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ckeditor5_image_uploads_hash_unique` (`hash`),
  KEY `g7_ckeditor5_image_uploads_uploaded_by_index` (`uploaded_by`),
  KEY `ckeditor5_image_uploads_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CKEditor5 이미지 업로드 기록';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '브랜드 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '브랜드명 (다국어 JSON: {ko: "삼성", en: "Samsung"})',
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '슬러그 (URL 식별자)',
  `website` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '브랜드 웹사이트 URL',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 (true: 활성, false: 비활성)',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_brands_slug_unique` (`slug`),
  KEY `g7_ecommerce_brands_created_by_foreign` (`created_by`),
  KEY `g7_ecommerce_brands_updated_by_foreign` (`updated_by`),
  KEY `g7_ecommerce_brands_is_active_index` (`is_active`),
  KEY `g7_ecommerce_brands_sort_order_index` (`sort_order`),
  KEY `g7_ecommerce_brands_is_active_sort_order_index` (`is_active`,`sort_order`),
  FULLTEXT KEY `ft_ecommerce_brands_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_ecommerce_brands_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_brands_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='브랜드 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '장바구니 ID',
  `cart_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비회원 장바구니 키 (ck_ 접두사 + 32자)',
  `user_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_option_id` bigint unsigned NOT NULL,
  `additional_option_selections` text COLLATE utf8mb4_unicode_ci COMMENT '선택된 추가옵션 (JSON: [{additional_option_id, value_id}])',
  `quantity` int unsigned NOT NULL DEFAULT '1' COMMENT '수량',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_carts_cart_key_index` (`cart_key`),
  KEY `ecommerce_carts_user_id_index` (`user_id`),
  KEY `ecommerce_carts_product_id_index` (`product_id`),
  KEY `ecommerce_carts_product_option_id_index` (`product_option_id`),
  KEY `ecommerce_carts_user_option_index` (`user_id`,`product_option_id`),
  KEY `ecommerce_carts_cart_key_option_index` (`cart_key`,`product_option_id`),
  CONSTRAINT `g7_ecommerce_carts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_carts_product_option_id_foreign` FOREIGN KEY (`product_option_id`) REFERENCES `g7_ecommerce_product_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='장바구니';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '카테고리 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '카테고리명 (다국어 JSON: {ko: "...", en: "..."})',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '카테고리 설명 (다국어 JSON: {ko: "...", en: "..."})',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '부모 카테고리 ID',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Materialized Path: 1/5/23',
  `depth` int unsigned NOT NULL DEFAULT '0' COMMENT '계층 깊이',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부: true(활성), false(비활성)',
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL 슬러그',
  `meta_title` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 제목 (다국어 JSON)',
  `meta_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 설명 (다국어 JSON)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_categories_parent_id_index` (`parent_id`),
  KEY `g7_ecommerce_categories_path_index` (`path`),
  KEY `g7_ecommerce_categories_depth_index` (`depth`),
  KEY `g7_ecommerce_categories_sort_order_index` (`sort_order`),
  KEY `g7_ecommerce_categories_is_active_index` (`is_active`),
  KEY `g7_ecommerce_categories_slug_index` (`slug`),
  KEY `g7_ecommerce_categories_parent_id_sort_order_index` (`parent_id`,`sort_order`),
  FULLTEXT KEY `ft_ecommerce_categories_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ft_ecommerce_categories_description` (`description`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_ecommerce_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `g7_ecommerce_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 카테고리 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_category_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '이미지 ID',
  `category_id` bigint unsigned DEFAULT NULL COMMENT '카테고리 ID (null이면 임시 업로드)',
  `temp_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '임시 업로드 키 (카테고리 생성 전 업로드용)',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '스토리지 디스크 (local, public, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, image/webp)',
  `file_size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `width` int unsigned DEFAULT NULL COMMENT '이미지 너비 (px)',
  `height` int unsigned DEFAULT NULL COMMENT '이미지 높이 (px)',
  `alt_text` text COLLATE utf8mb4_unicode_ci COMMENT '대체 텍스트 (다국어 JSON: {ko: "...", en: "..."})',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main' COMMENT '이미지 컬렉션: main(메인)',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_category_images_hash_unique` (`hash`),
  KEY `g7_ecommerce_category_images_created_by_foreign` (`created_by`),
  KEY `g7_ecommerce_category_images_category_id_index` (`category_id`),
  KEY `g7_ecommerce_category_images_category_id_sort_order_index` (`category_id`,`sort_order`),
  KEY `g7_ecommerce_category_images_temp_key_index` (`temp_key`),
  KEY `g7_ecommerce_category_images_deleted_at_index` (`deleted_at`),
  CONSTRAINT `g7_ecommerce_category_images_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `g7_ecommerce_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_category_images_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카테고리 이미지 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_claim_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '클레임 사유 ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '사유 유형 (refund, exchange, return 등)',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 코드 (order_mistake 등)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 사유명 {"ko":"주문 실수","en":"Order Mistake"}',
  `fault_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '귀책 구분 (customer, seller, carrier)',
  `is_user_selectable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '고객 선택 가능 여부',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 여부',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "sort_order", "is_active"])',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_claim_reasons_type_code_unique` (`type`,`code`),
  KEY `ecommerce_claim_reasons_is_active_index` (`is_active`),
  KEY `ecommerce_claim_reasons_sort_order_index` (`sort_order`),
  KEY `ecommerce_claim_reasons_type_active_fault_index` (`type`,`is_active`,`fault_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='클레임 사유 템플릿';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_mileage_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '잔액 캐시 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '회원 ID',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '통화 코드 (통화별 행)',
  `available` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '사용 가능 잔액 (활성 lot SUM 스냅샷)',
  `pending` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '적립 예정 (미취소·earn ledger 부재 옵션 적립액 합)',
  `total_earned` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '누적 적립',
  `total_used` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '누적 사용',
  `expiring_soon` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'N일내 소멸 예정 (일배치 갱신)',
  `expiring_date` timestamp NULL DEFAULT NULL COMMENT '가장 임박한 소멸 예정일',
  `recalculated_at` timestamp NULL DEFAULT NULL COMMENT '마지막 전체 재계산 시각 (drift 감사)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_mileage_balances_user_currency_unique` (`user_id`,`currency`),
  CONSTRAINT `g7_ecommerce_mileage_balances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_mileage_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '거래 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '회원 ID',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '통화 코드 (주문 기준통화 스냅샷)',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '거래 유형 (MileageTransactionTypeEnum)',
  `amount` decimal(12,2) NOT NULL COMMENT '거래 금액 (양수=적립, 음수=차감)',
  `remaining_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '잔여 금액 (적립건만 양수, FIFO 차감용)',
  `balance_after` decimal(12,2) NOT NULL COMMENT '거래 후 잔액 (감사 스냅샷, 베스트에포트)',
  `order_id` bigint unsigned DEFAULT NULL COMMENT '관련 주문 ID',
  `order_option_id` bigint unsigned DEFAULT NULL COMMENT '관련 주문옵션 ID (병합 하드삭제 대응 — FK 미설정)',
  `order_cancel_id` bigint unsigned DEFAULT NULL COMMENT '관련 주문취소 ID (복원 멱등 기준)',
  `source_transaction_id` bigint unsigned DEFAULT NULL COMMENT '원본 적립건 ID (차감 시 FIFO 추적)',
  `granted_by` bigint unsigned DEFAULT NULL COMMENT '부여 주체 (NULL=시스템, user ID=관리자)',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '거래 설명',
  `memo` text COLLATE utf8mb4_unicode_ci COMMENT '관리자 메모',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '유효기간 만료 예정일 (적립건만)',
  `expired_at` timestamp NULL DEFAULT NULL COMMENT '실제 소멸 처리일',
  `metadata` json DEFAULT NULL COMMENT '추가 정보 (부족 회수액 등)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchase_earn_option_key` bigint unsigned GENERATED ALWAYS AS ((case when (`type` = _utf8mb4'purchase_earn') then `order_option_id` else NULL end)) VIRTUAL COMMENT '주문옵션당 구매적립 1건 강제용 파생 키 (purchase_earn 이 아니면 NULL)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_mileage_transactions_purchase_earn_option_unique` (`purchase_earn_option_key`),
  KEY `g7_ecommerce_mileage_transactions_order_id_foreign` (`order_id`),
  KEY `g7_ecommerce_mileage_transactions_source_transaction_id_foreign` (`source_transaction_id`),
  KEY `ecommerce_mileage_transactions_user_currency_expires_index` (`user_id`,`currency`,`expires_at`),
  KEY `ecommerce_mileage_transactions_user_currency_index` (`user_id`,`currency`),
  KEY `ecommerce_mileage_transactions_expires_index` (`expires_at`),
  KEY `ecommerce_mileage_transactions_type_created_index` (`type`,`created_at`),
  KEY `ecommerce_mileage_transactions_order_option_index` (`order_option_id`),
  KEY `ecommerce_mileage_transactions_order_cancel_index` (`order_cancel_id`),
  CONSTRAINT `g7_ecommerce_mileage_transactions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_mileage_transactions_source_transaction_id_foreign` FOREIGN KEY (`source_transaction_id`) REFERENCES `g7_ecommerce_mileage_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_mileage_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송지 ID',
  `order_id` bigint unsigned NOT NULL,
  `address_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '주소 유형 (shipping: 배송지, billing: 청구지)',
  `orderer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '주문자 이름',
  `orderer_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '주문자 휴대폰',
  `orderer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주문자 이메일',
  `orderer_locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주문자 선호 언어 (주문 시점 화면 언어 스냅샷, 비회원 알림 발송 언어 결정용)',
  `recipient_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수령인 이름',
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수령인 휴대폰',
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '수령인 이메일',
  `recipient_country_code` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '국가 코드 (ISO 3166-1 alpha-2: KR, US 등)',
  `recipient_province_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '시/도 코드 (행정구역 코드)',
  `recipient_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '도시 (국제 주소 호환)',
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '우편번호',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '기본 주소',
  `address_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '상세 주소',
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '해외 주소 1 (Street address)',
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '해외 주소 2 (Apt, Suite 등)',
  `intl_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '도시 (City) - 해외배송용',
  `intl_state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주/지역 (State/Province) - 해외배송용',
  `intl_postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '우편번호 (해외)',
  `address_type_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주소 유형 코드 (J: 지번, R: 도로명)',
  `delivery_memo` text COLLATE utf8mb4_unicode_ci COMMENT '배송 메모',
  `delivery_memo_label` text COLLATE utf8mb4_unicode_ci COMMENT '배송 메모 표시 라벨 (프리셋 키의 주문시점 스냅샷, custom은 원문)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_addresses_order_id_index` (`order_id`),
  KEY `ecommerce_order_addresses_orderer_name_index` (`orderer_name`),
  KEY `ecommerce_order_addresses_recipient_name_index` (`recipient_name`),
  KEY `ecommerce_order_addresses_recipient_country_index` (`recipient_country_code`),
  KEY `idx_ecommerce_order_addresses_orderer_phone` (`orderer_phone`),
  KEY `idx_ecommerce_order_addresses_recipient_phone` (`recipient_phone`),
  CONSTRAINT `g7_ecommerce_order_addresses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 배송지 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_cancel_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '취소 옵션 ID',
  `order_cancel_id` bigint unsigned NOT NULL COMMENT '취소 FK',
  `order_id` bigint unsigned NOT NULL COMMENT '주문 FK',
  `order_option_id` bigint unsigned NOT NULL COMMENT '주문옵션 FK',
  `option_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 취소 상태 (CancelOptionStatusEnum: requested, completed)',
  `cancel_quantity` int unsigned NOT NULL COMMENT '취소 수량',
  `original_quantity` int unsigned NOT NULL COMMENT '취소 전 원래 수량 (감사용)',
  `unit_price` decimal(12,2) NOT NULL COMMENT '취소 시점 단가',
  `subtotal_amount` decimal(12,2) NOT NULL COMMENT 'cancel_quantity × unit_price',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '완료 시각',
  `processed_by` bigint unsigned DEFAULT NULL COMMENT '처리 관리자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_order_cancel_options_processed_by_foreign` (`processed_by`),
  KEY `ecommerce_order_cancel_opts_cancel_id_index` (`order_cancel_id`),
  KEY `ecommerce_order_cancel_opts_order_id_index` (`order_id`),
  KEY `ecommerce_order_cancel_opts_option_id_index` (`order_option_id`),
  CONSTRAINT `g7_ecommerce_order_cancel_options_order_cancel_id_foreign` FOREIGN KEY (`order_cancel_id`) REFERENCES `g7_ecommerce_order_cancels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_cancel_options_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_cancel_options_order_option_id_foreign` FOREIGN KEY (`order_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_cancel_options_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 취소 옵션별 상세';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_cancels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '취소 ID',
  `order_id` bigint unsigned NOT NULL COMMENT '주문 FK',
  `cancel_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 취소번호',
  `cancel_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '취소 유형 (CancelTypeEnum: full, partial)',
  `cancel_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '취소 상태 (CancelStatusEnum: requested, completed)',
  `cancel_reason_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '취소 사유 코드 (ecommerce_claim_reasons.code 참조)',
  `cancel_reason` text COLLATE utf8mb4_unicode_ci COMMENT '상세 사유 (기타 선택 시)',
  `items_snapshot` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '취소 요청 시점 대상 옵션/수량 스냅샷',
  `shipping_snapshot` json DEFAULT NULL COMMENT '취소 시점 배송지(국가/우편번호) + 취소 대상 배송정책 스냅샷 (환불 정책 재판단 복원용)',
  `cancelled_by` bigint unsigned DEFAULT NULL COMMENT '취소 요청자',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT '취소 완료 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_order_cancels_cancel_number_unique` (`cancel_number`),
  KEY `g7_ecommerce_order_cancels_cancelled_by_foreign` (`cancelled_by`),
  KEY `ecommerce_order_cancels_order_id_index` (`order_id`),
  KEY `ecommerce_order_cancels_cancel_status_index` (`cancel_status`),
  KEY `ecommerce_order_cancels_cancel_type_index` (`cancel_type`),
  CONSTRAINT `g7_ecommerce_order_cancels_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_order_cancels_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 취소 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_cash_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '현금영수증 이력 ID',
  `order_id` bigint unsigned NOT NULL COMMENT '주문 ID',
  `order_payment_id` bigint unsigned DEFAULT NULL COMMENT '결제 ID',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급 프로바이더 (tosspayments, kginicis 등)',
  `receipt_key` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '프로바이더 영수증 키',
  `transaction_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '거래 유형 (issue: 발급, cancel: 취소)',
  `receipt_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '영수증 유형 (income: 소득공제, expense: 지출증빙)',
  `amount` decimal(12,2) NOT NULL COMMENT '발급/취소 금액',
  `tax_free_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '면세 금액',
  `identifier_masked` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '식별번호 (뒤 4자리 외 마스킹)',
  `receipt_url` text COLLATE utf8mb4_unicode_ci COMMENT '영수증 조회 URL',
  `issue_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발급번호',
  `issue_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급 상태 (IN_PROGRESS: 처리중, COMPLETED: 완료, FAILED: 실패)',
  `error_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '실패 시 프로바이더 에러코드',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '실패 시 에러 메시지',
  `issued_at` timestamp NULL DEFAULT NULL COMMENT '발급일시',
  `raw_response` json DEFAULT NULL COMMENT '프로바이더 원응답 (민감키 마스킹 후 저장)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_cash_receipts_order_type_index` (`order_id`,`transaction_type`),
  KEY `ecommerce_order_cash_receipts_receipt_key_index` (`receipt_key`),
  KEY `ecommerce_order_cash_receipts_payment_id_index` (`order_payment_id`),
  CONSTRAINT `g7_ecommerce_order_cash_receipts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '주문 옵션 ID',
  `order_id` bigint unsigned NOT NULL COMMENT '소속 주문 ID',
  `parent_option_id` bigint unsigned DEFAULT NULL COMMENT '추가 옵션/구성품의 부모 옵션 ID',
  `product_id` bigint unsigned NOT NULL COMMENT '주문 시점 상품 ID',
  `product_option_id` bigint unsigned NOT NULL COMMENT '주문 시점 상품 옵션 ID',
  `option_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 상태 (OrderStatusEnum)',
  `confirmed_at` timestamp NULL DEFAULT NULL COMMENT '구매확정 일시',
  `delivered_at` timestamp NULL DEFAULT NULL COMMENT '배송완료 시점',
  `is_stock_deducted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '재고 차감 여부',
  `source_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'order' COMMENT '생성 원인 (order/exchange)',
  `source_option_id` bigint unsigned DEFAULT NULL COMMENT '교환 원본 옵션 ID',
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SKU (주문 시점, 검색용)',
  `product_name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '상품명 (다국어, 주문 시점)',
  `product_option_name` text COLLATE utf8mb4_unicode_ci COMMENT '옵션 조합명 (다국어, 예: {"ko": "빨강/XL", "en": "Red/XL"})',
  `option_name` text COLLATE utf8mb4_unicode_ci COMMENT '옵션명 (다국어, 예: {"ko": "빨강/XL", "en": "Red/XL"})',
  `option_value` text COLLATE utf8mb4_unicode_ci COMMENT '옵션값 요약 (다국어, 예: {"ko": "색상: 빨강, 사이즈: L", "en": "Color: Red, Size: L"})',
  `quantity` int NOT NULL COMMENT '주문수량',
  `cancelled_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '취소된 수량 (누적)',
  `cancel_reason` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '취소 사유 코드 (ecommerce_claim_reasons.code 참조)',
  `unit_weight` decimal(10,3) DEFAULT NULL COMMENT '단위 무게 (g, 주문 시점)',
  `unit_volume` decimal(10,3) DEFAULT NULL COMMENT '단위 부피 (cm³, 주문 시점)',
  `subtotal_weight` decimal(10,3) DEFAULT NULL COMMENT '무게 소계 (g, unit_weight × quantity)',
  `subtotal_volume` decimal(10,3) DEFAULT NULL COMMENT '부피 소계 (cm³, unit_volume × quantity)',
  `unit_price` decimal(12,2) NOT NULL COMMENT '단가 (주문 시점 가격)',
  `additional_options_total` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '옵션 1단위당 추가옵션 합계 (KRW 기준)',
  `subtotal_price` decimal(12,2) NOT NULL COMMENT '소계 (unit_price × quantity)',
  `subtotal_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '할인 소계 (아래 할인 합계)',
  `coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '쿠폰 할인',
  `product_coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '상품 쿠폰 할인 (상품/카테고리 쿠폰)',
  `order_coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '주문 쿠폰 할인 안분액',
  `code_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '할인코드 할인',
  `subtotal_points_used_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '포인트 사용 소계 (옵션별 배분)',
  `subtotal_deposit_used_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '예치금 사용 소계 (옵션별 배분)',
  `subtotal_paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '실결제 소계 (옵션별 배분)',
  `subtotal_tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '과세 소계',
  `subtotal_tax_free_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '면세 소계',
  `subtotal_earned_points_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '적립 예정 포인트 소계',
  `mc_unit_price` text COLLATE utf8mb4_unicode_ci COMMENT '단가 다중 통화',
  `mc_additional_options_total` text COLLATE utf8mb4_unicode_ci COMMENT '다중통화 추가옵션 단위 합계 (통화코드별 JSON)',
  `mc_subtotal_price` text COLLATE utf8mb4_unicode_ci COMMENT '소계 다중 통화',
  `mc_product_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '상품 쿠폰 할인 다중 통화',
  `mc_order_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '주문 쿠폰 안분 다중 통화',
  `mc_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '쿠폰 할인 합계 다중 통화',
  `mc_code_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '할인코드 할인 다중 통화',
  `mc_subtotal_points_used_amount` text COLLATE utf8mb4_unicode_ci COMMENT '포인트 사용 다중 통화',
  `mc_subtotal_earned_points_amount` text COLLATE utf8mb4_unicode_ci COMMENT '적립 예정 포인트 다중 통화',
  `mc_subtotal_deposit_used_amount` text COLLATE utf8mb4_unicode_ci COMMENT '예치금 사용 다중 통화',
  `mc_subtotal_tax_amount` text COLLATE utf8mb4_unicode_ci COMMENT '과세 소계 다중 통화',
  `mc_subtotal_tax_free_amount` text COLLATE utf8mb4_unicode_ci COMMENT '면세 소계 다중 통화',
  `mc_final_amount` text COLLATE utf8mb4_unicode_ci COMMENT '최종금액 다중 통화',
  `product_snapshot` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '상품 스냅샷 (상품 변경/삭제 대응)',
  `option_snapshot` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 스냅샷 (옵션 변경/삭제 대응)',
  `additional_options_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '추가옵션 스냅샷 (주문 시점 동결 JSON)',
  `promotions_applied_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '적용 프로모션 (개별 할인 재계산)',
  `external_option_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '외부 옵션 ID (네이버페이 등)',
  `external_meta` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '외부 연동 메타 정보',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subtotal_cash_equivalent_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '옵션별 현금성 금액 안분액',
  `mc_subtotal_cash_equivalent_amount` text COLLATE utf8mb4_unicode_ci COMMENT '옵션별 현금성 금액 다중 통화',
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_options_order_id_index` (`order_id`),
  KEY `ecommerce_order_options_product_id_index` (`product_id`),
  KEY `ecommerce_order_options_option_status_index` (`option_status`),
  KEY `ecommerce_order_options_sku_index` (`sku`),
  KEY `g7_ecommerce_order_options_parent_option_id_foreign` (`parent_option_id`),
  KEY `g7_ecommerce_order_options_product_option_id_foreign` (`product_option_id`),
  KEY `g7_ecommerce_order_options_source_option_id_foreign` (`source_option_id`),
  KEY `ecommerce_order_options_delivered_at_index` (`delivered_at`),
  KEY `idx_order_options_product_created_qty` (`product_id`,`created_at`,`quantity`),
  CONSTRAINT `g7_ecommerce_order_options_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_options_parent_option_id_foreign` FOREIGN KEY (`parent_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_order_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `g7_ecommerce_order_options_product_option_id_foreign` FOREIGN KEY (`product_option_id`) REFERENCES `g7_ecommerce_product_options` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `g7_ecommerce_order_options_source_option_id_foreign` FOREIGN KEY (`source_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 옵션 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '결제 ID',
  `order_id` bigint unsigned NOT NULL,
  `payment_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '결제 상태 (PaymentStatusEnum)',
  `pg_provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PG사 (tosspayments, inicis 등)',
  `embedded_pg_provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '간편결제 PG (kakaopay, naverpay 등)',
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PG 거래 고유ID',
  `merchant_order_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '가맹점 기준 주문번호',
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '결제수단 (PaymentMethodEnum)',
  `payment_device` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '결제 디바이스 (pc/mobile/app)',
  `paid_amount_local` decimal(12,2) NOT NULL COMMENT '결제금액 (현지 통화, PG 실제 결제액)',
  `paid_amount_base` decimal(12,2) NOT NULL COMMENT '결제금액 (기준 통화 KRW 환산)',
  `vat_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '부가세',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '결제 통화',
  `currency_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '주문 시점 통화 스냅샷',
  `mc_paid_amount` text COLLATE utf8mb4_unicode_ci COMMENT '결제금액 다중 통화 {"KRW": 10000, "USD": 7.5}',
  `mc_cancelled_amount` text COLLATE utf8mb4_unicode_ci COMMENT '취소금액 다중 통화',
  `card_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '카드사명',
  `card_number_masked` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마스킹된 카드번호 (1234-****-****-5678)',
  `card_approval_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '카드 승인번호',
  `card_installment_months` int DEFAULT NULL COMMENT '할부개월 (0: 일시불)',
  `is_interest_free` tinyint(1) NOT NULL DEFAULT '0' COMMENT '무이자 여부 (1: 무이자, 0: 유이자)',
  `vbank_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '은행코드',
  `vbank_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '은행명',
  `vbank_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '계좌번호',
  `vbank_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '예금주',
  `vbank_due_at` timestamp NULL DEFAULT NULL COMMENT '입금기한',
  `vbank_issued_at` timestamp NULL DEFAULT NULL COMMENT '가상계좌 발급일시',
  `dbank_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 은행코드',
  `dbank_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 은행명',
  `dbank_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 계좌번호',
  `dbank_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 예금주',
  `depositor_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '입금자명',
  `deposit_due_at` timestamp NULL DEFAULT NULL COMMENT '입금기한',
  `is_escrow` tinyint(1) NOT NULL DEFAULT '0' COMMENT '에스크로 여부 (1: 에스크로, 0: 일반)',
  `buyer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '구매자명',
  `buyer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '구매자 이메일',
  `buyer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '구매자 전화번호',
  `is_cash_receipt_requested` tinyint(1) NOT NULL DEFAULT '0' COMMENT '현금영수증 요청여부 (1: 요청, 0: 미요청)',
  `is_cash_receipt_issued` tinyint(1) NOT NULL DEFAULT '0' COMMENT '현금영수증 발급여부 (1: 발급, 0: 미발급)',
  `cash_receipt_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '현금영수증 유형 (income: 소득공제, expense: 지출증빙)',
  `cash_receipt_identifier_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '현금영수증 식별번호 종류 (phone: 휴대폰번호, card: 현금영수증카드번호, business: 사업자등록번호)',
  `cash_receipt_identifier` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '현금영수증 식별번호 (휴대폰/사업자번호)',
  `cash_receipt_identifier_encrypted` text COLLATE utf8mb4_unicode_ci COMMENT '현금영수증 식별번호 암호문 (재발급용, 구매확정 시 폐기)',
  `cash_receipt_issued_at` timestamp NULL DEFAULT NULL COMMENT '현금영수증 발급일시',
  `cancelled_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '취소금액',
  `cancelled_vat_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '취소 부가세',
  `cancel_reason` text COLLATE utf8mb4_unicode_ci COMMENT '취소사유',
  `cancel_history` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '취소 이력 (부분취소 이력)',
  `refund_bank_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '환불 은행코드',
  `refund_bank_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '환불 은행명',
  `refund_bank_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '환불 계좌번호',
  `refund_bank_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '환불 예금주',
  `receipt_url` text COLLATE utf8mb4_unicode_ci COMMENT '영수증 URL',
  `payment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '결제명 (상품명 요약)',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT '브라우저 정보',
  `payment_meta` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '기타 메타정보 (PG별 추가 정보)',
  `payment_started_at` timestamp NULL DEFAULT NULL COMMENT '결제 시작일시',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '결제 완료일시',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT '취소 완료일시',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_order_payments_merchant_order_id_unique` (`merchant_order_id`),
  UNIQUE KEY `ecommerce_order_payments_transaction_id_unique` (`transaction_id`),
  KEY `ecommerce_order_payments_order_id_index` (`order_id`),
  KEY `ecommerce_order_payments_payment_status_index` (`payment_status`),
  KEY `ecommerce_order_payments_payment_method_index` (`payment_method`),
  KEY `ecommerce_order_payments_paid_at_index` (`paid_at`),
  CONSTRAINT `g7_ecommerce_order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 결제 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_refund_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '환불 옵션 ID',
  `order_refund_id` bigint unsigned NOT NULL COMMENT '환불 FK',
  `order_id` bigint unsigned NOT NULL COMMENT '주문 FK',
  `order_option_id` bigint unsigned NOT NULL COMMENT '주문옵션 FK',
  `option_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 환불 상태 (RefundOptionStatusEnum: requested, approved, processing, on_hold, completed, rejected)',
  `quantity` int unsigned NOT NULL COMMENT '환불 수량',
  `unit_price` decimal(12,2) NOT NULL COMMENT '환불 시점 단가',
  `subtotal_amount` decimal(12,2) NOT NULL COMMENT '수량 × 단가',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '할인 차감분',
  `shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '해당 옵션 배송비 차액',
  `refund_amount` decimal(12,2) NOT NULL COMMENT '해당 옵션 최종 환불액',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '완료 시각',
  `processed_by` bigint unsigned DEFAULT NULL COMMENT '처리 관리자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_order_refund_options_processed_by_foreign` (`processed_by`),
  KEY `ecommerce_order_refund_opts_refund_id_index` (`order_refund_id`),
  KEY `ecommerce_order_refund_opts_order_id_index` (`order_id`),
  KEY `ecommerce_order_refund_opts_option_id_index` (`order_option_id`),
  CONSTRAINT `g7_ecommerce_order_refund_options_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_refund_options_order_option_id_foreign` FOREIGN KEY (`order_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_refund_options_order_refund_id_foreign` FOREIGN KEY (`order_refund_id`) REFERENCES `g7_ecommerce_order_refunds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_refund_options_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 환불 옵션별 상세';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '환불 ID',
  `order_id` bigint unsigned NOT NULL COMMENT '주문 FK',
  `order_cancel_id` bigint unsigned DEFAULT NULL COMMENT '취소 FK (추후 polymorphic 전환 가능)',
  `refund_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 환불번호',
  `refund_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '환불 상태 (RefundStatusEnum: requested, approved, processing, on_hold, completed, rejected)',
  `refund_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '환불 수단 (RefundMethodEnum: pg, bank, points)',
  `refund_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'PG/계좌 환불 금액',
  `refund_points_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '마일리지 환불액',
  `refund_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '배송비 환불/추가 차액',
  `mc_refund_amount` text COLLATE utf8mb4_unicode_ci COMMENT '다통화 PG 환불금액 (통화코드 → 금액)',
  `mc_refund_points_amount` text COLLATE utf8mb4_unicode_ci COMMENT '다통화 마일리지 환불금액 (통화코드 → 금액)',
  `mc_refund_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '다통화 배송비 환불금액 (통화코드 → 금액)',
  `original_calculation_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '재계산 전 주문 금액 스냅샷',
  `recalculated_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '재계산 후 주문 금액 스냅샷',
  `additional_payment_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '추가결제 필요 금액 (무료배송 미달 등)',
  `additional_payment_method` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '추가결제 수단 (pg, bank)',
  `is_additional_payment_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '추가결제 완료 여부',
  `additional_paid_at` timestamp NULL DEFAULT NULL COMMENT '추가결제 완료 시각',
  `refund_bank_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 환불 예금주',
  `refund_bank_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 환불 은행코드',
  `refund_bank_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '무통장 환불 계좌번호',
  `pg_transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PG 환불 거래 ID',
  `pg_error_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PG 오류 코드',
  `pg_error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'PG 오류 메시지',
  `refunded_at` timestamp NULL DEFAULT NULL COMMENT '환불 완료 시각',
  `processed_by` bigint unsigned DEFAULT NULL COMMENT '처리 관리자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_order_refunds_refund_number_unique` (`refund_number`),
  KEY `g7_ecommerce_order_refunds_processed_by_foreign` (`processed_by`),
  KEY `ecommerce_order_refunds_order_id_index` (`order_id`),
  KEY `ecommerce_order_refunds_cancel_id_index` (`order_cancel_id`),
  KEY `ecommerce_order_refunds_refund_status_index` (`refund_status`),
  CONSTRAINT `g7_ecommerce_order_refunds_order_cancel_id_foreign` FOREIGN KEY (`order_cancel_id`) REFERENCES `g7_ecommerce_order_cancels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_order_refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_refunds_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 환불 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_shippings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송 ID',
  `order_id` bigint unsigned NOT NULL,
  `order_option_id` bigint unsigned NOT NULL,
  `shipping_policy_id` bigint unsigned DEFAULT NULL COMMENT '배송정책 ID',
  `shipping_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송 상태 (ShippingStatusEnum)',
  `shipping_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송유형 (ShippingTypeEnum, 국내/해외 구분 포함)',
  `base_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '배송비',
  `extra_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '추가 배송비 (도서산간)',
  `total_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 배송비 (기본 + 추가)',
  `shipping_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '배송비 할인금액',
  `is_remote_area` tinyint(1) NOT NULL DEFAULT '0' COMMENT '도서산간 여부',
  `carrier_id` bigint unsigned DEFAULT NULL COMMENT '택배사 ID (국내/해외 공용)',
  `tracking_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '운송장 번호',
  `return_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '반품 배송비',
  `return_carrier_id` bigint unsigned DEFAULT NULL COMMENT '반품 택배사 ID',
  `return_tracking_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '반품 운송장 번호',
  `exchange_carrier_id` bigint unsigned DEFAULT NULL COMMENT '교환 택배사 ID',
  `exchange_tracking_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '교환 운송장 번호',
  `package_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '합포장 번호 (동일 번호 = 합포장)',
  `visit_date` date DEFAULT NULL COMMENT '방문수령일',
  `visit_time_slot` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '방문시간대',
  `actual_weight` decimal(10,3) DEFAULT NULL COMMENT '실측 무게 (kg)',
  `delivery_policy_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '배송정책 스냅샷 (주문시점 조건)',
  `currency_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '배송비 계산 시점 통화 스냅샷',
  `mc_base_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '배송비 다중 통화',
  `mc_extra_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '추가 배송비 다중 통화',
  `mc_total_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '총 배송비 다중 통화',
  `mc_shipping_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '배송비 할인 다중 통화',
  `mc_return_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '반품 배송비 다중 통화',
  `shipped_at` timestamp NULL DEFAULT NULL COMMENT '발송일시',
  `estimated_arrival_at` timestamp NULL DEFAULT NULL COMMENT '예상 도착일시',
  `delivered_at` timestamp NULL DEFAULT NULL COMMENT '배송완료일시',
  `confirmed_at` timestamp NULL DEFAULT NULL COMMENT '구매확정일시',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_shippings_order_id_index` (`order_id`),
  KEY `ecommerce_order_shippings_order_option_id_index` (`order_option_id`),
  KEY `ecommerce_order_shippings_shipping_status_index` (`shipping_status`),
  KEY `ecommerce_order_shippings_shipping_type_index` (`shipping_type`),
  KEY `ecommerce_order_shippings_tracking_number_index` (`tracking_number`),
  KEY `ecommerce_order_shippings_order_id_shipped_at_index` (`order_id`,`shipped_at`),
  CONSTRAINT `g7_ecommerce_order_shippings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_shippings_order_option_id_foreign` FOREIGN KEY (`order_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 배송 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_order_tax_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '세금계산서 ID',
  `order_id` bigint unsigned NOT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `invoice_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급 상태 (TaxInvoiceStatusEnum)',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '사업자 상호',
  `company_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '사업자번호 (000-00-00000)',
  `ceo_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '대표자명',
  `business_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '업종',
  `business_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '업태',
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '우편번호',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주소',
  `address_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '상세주소',
  `manager_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '담당자명',
  `manager_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '세금계산서 이메일',
  `manager_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '담당자 전화번호',
  `supply_amount` decimal(12,2) NOT NULL COMMENT '공급가액',
  `tax_amount` decimal(12,2) NOT NULL COMMENT '세액',
  `total_amount` decimal(12,2) NOT NULL COMMENT '합계금액',
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '세금계산서 번호 (발급 후 기록)',
  `invoice_url` text COLLATE utf8mb4_unicode_ci COMMENT '세금계산서 URL',
  `requested_at` timestamp NOT NULL COMMENT '요청일시',
  `issued_at` timestamp NULL DEFAULT NULL COMMENT '발급일시',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_tax_invoices_order_id_index` (`order_id`),
  KEY `ecommerce_order_tax_invoices_payment_id_index` (`payment_id`),
  KEY `ecommerce_order_tax_invoices_invoice_status_index` (`invoice_status`),
  KEY `ecommerce_order_tax_invoices_company_number_index` (`company_number`),
  CONSTRAINT `g7_ecommerce_order_tax_invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_order_tax_invoices_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `g7_ecommerce_order_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 세금계산서 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '주문 ID',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '주문자 회원 ID (비회원 주문은 null)',
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '주문번호',
  `guest_lookup_password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비회원 주문 조회 비밀번호 해시 (회원 주문은 null)',
  `order_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '주문상태 (OrderStatusEnum)',
  `order_device` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주문 디바이스 (pc/mobile/app)',
  `is_first_order` tinyint(1) NOT NULL DEFAULT '0' COMMENT '첫구매 여부 (1: 첫구매, 0: 재구매)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주문자 IP (IPv6 대응)',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '결제 통화 (KRW, USD, EUR 등)',
  `currency_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '주문 시점 통화 스냅샷 (모든 통화 환율)',
  `subtotal_amount` decimal(12,2) NOT NULL COMMENT '상품 합계 (할인 전, 상품가×수량 합계)',
  `total_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 할인금액 (모든 할인 합계)',
  `total_coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 쿠폰 할인금액',
  `total_product_coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '상품 쿠폰 할인 합계',
  `total_order_coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '주문 쿠폰 할인 합계',
  `total_code_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 할인코드 할인금액',
  `total_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 배송비',
  `base_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '기본 배송비',
  `extra_shipping_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '추가 배송비 (도서산간)',
  `shipping_discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '배송비 할인금액',
  `total_amount` decimal(12,2) NOT NULL COMMENT '최종 주문금액 (subtotal - discount + shipping)',
  `total_tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 과세금액',
  `total_vat_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 부가세금액',
  `total_tax_free_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 면세금액',
  `total_points_used_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 포인트 사용액',
  `is_mileage_deducted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '마일리지 실차감 여부 (1: 차감됨, 0: 미차감 — 복원 가드 기준)',
  `total_deposit_used_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 예치금 사용액',
  `total_paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 실제 결제금액 (PG 결제액)',
  `total_due_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 결제예정금액 (무통장 등)',
  `total_cash_equivalent_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '주문 전체 현금성 결제 금액 (현금영수증 발급 대상)',
  `total_cancelled_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 취소금액',
  `total_refunded_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 환불금액',
  `total_refunded_points_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 환불 포인트',
  `total_earned_points_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '총 적립 예정 포인트',
  `cancellation_count` int unsigned NOT NULL DEFAULT '0' COMMENT '취소 횟수',
  `item_count` int NOT NULL COMMENT '총 주문수량 (상품 수량 합계)',
  `total_weight` decimal(10,3) DEFAULT NULL COMMENT '총 무게 (g)',
  `total_volume` decimal(10,3) DEFAULT NULL COMMENT '총 부피 (cm³)',
  `ordered_at` timestamp NOT NULL COMMENT '주문일시',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '결제완료일시',
  `payment_due_at` timestamp NULL DEFAULT NULL COMMENT '결제마감일시 (무통장 입금기한)',
  `confirmed_at` timestamp NULL DEFAULT NULL COMMENT '구매확정일시',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT '취소일시 (전체/부분취소 최초 발생 시각)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '삭제일시 (Soft Delete)',
  `admin_memo` text COLLATE utf8mb4_unicode_ci COMMENT '관리자 메모 (내부 관리용)',
  `promotions_applied_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '적용된 프로모션 스냅샷 (재계산용)',
  `shipping_policy_applied_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '적용된 배송정책 스냅샷 (재계산용)',
  `mileage_policy_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '주문 시점 마일리지 사용 정책 스냅샷 (최소 사용액/사용 단위/한도 재현용)',
  `promotions_available_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '사용가능 프로모션 스냅샷 (감사용)',
  `order_meta` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '기타 메타정보 (확장성)',
  `mc_subtotal_amount` text COLLATE utf8mb4_unicode_ci COMMENT '상품합계 다중 통화',
  `mc_total_product_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '상품 쿠폰 할인 다중 통화',
  `mc_total_order_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '주문 쿠폰 할인 다중 통화',
  `mc_total_coupon_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '쿠폰 할인 합계 다중 통화',
  `mc_total_code_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '할인코드 할인 다중 통화',
  `mc_total_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '총 할인 다중 통화',
  `mc_base_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '기본 배송비 다중 통화',
  `mc_extra_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '추가 배송비 다중 통화',
  `mc_total_shipping_amount` text COLLATE utf8mb4_unicode_ci COMMENT '총 배송비 다중 통화',
  `mc_shipping_discount_amount` text COLLATE utf8mb4_unicode_ci COMMENT '배송비 할인 다중 통화',
  `mc_total_tax_amount` text COLLATE utf8mb4_unicode_ci COMMENT '과세금액 다중 통화',
  `mc_total_tax_free_amount` text COLLATE utf8mb4_unicode_ci COMMENT '면세금액 다중 통화',
  `mc_total_points_used_amount` text COLLATE utf8mb4_unicode_ci COMMENT '포인트 사용 다중 통화',
  `mc_total_deposit_used_amount` text COLLATE utf8mb4_unicode_ci COMMENT '예치금 사용 다중 통화',
  `mc_total_amount` text COLLATE utf8mb4_unicode_ci COMMENT '최종금액 다중 통화 (payment_amount)',
  `mc_total_paid_amount` text COLLATE utf8mb4_unicode_ci COMMENT '실결제금액 다중 통화',
  `mc_total_cash_equivalent_amount` text COLLATE utf8mb4_unicode_ci COMMENT '현금성 결제 금액 다중 통화',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_orders_order_number_unique` (`order_number`),
  KEY `ecommerce_orders_user_id_index` (`user_id`),
  KEY `ecommerce_orders_order_status_index` (`order_status`),
  KEY `ecommerce_orders_ordered_at_index` (`ordered_at`),
  KEY `ecommerce_orders_paid_at_index` (`paid_at`),
  KEY `ecommerce_orders_status_ordered_at_index` (`order_status`,`ordered_at`),
  KEY `idx_ecommerce_orders_total_amount` (`total_amount`),
  KEY `idx_ecommerce_orders_order_device` (`order_device`),
  KEY `idx_ecommerce_orders_confirmed_at` (`confirmed_at`),
  KEY `idx_ecommerce_orders_user_ordered` (`user_id`,`ordered_at`),
  KEY `idx_orders_deleted_ordered_id` (`deleted_at`,`ordered_at`,`id`),
  CONSTRAINT `g7_ecommerce_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_additional_option_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `additional_option_id` bigint unsigned NOT NULL COMMENT '추가옵션 그룹 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '선택지명 (다국어 JSON)',
  `price_adjustment` bigint NOT NULL DEFAULT '0' COMMENT '추가금 (KRW 기준, 0 이상)',
  `mc_price_adjustment` text COLLATE utf8mb4_unicode_ci COMMENT '다중통화 추가금 (통화코드별 JSON)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 선택지 여부 (1: 기본, 0: 일반)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부 (1: 활성, 0: 비활성)',
  `allow_custom_text` tinyint(1) NOT NULL DEFAULT '0' COMMENT '직접입력 허용 여부 (1: 허용 — 유저가 자유 텍스트 입력 필수, 0: 미허용)',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_add_opt_val_aoid_sort_idx` (`additional_option_id`,`sort_order`),
  CONSTRAINT `ec_prod_add_opt_val_aoid_fk` FOREIGN KEY (`additional_option_id`) REFERENCES `g7_ecommerce_product_additional_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 추가옵션 선택지';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_additional_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션명 (다국어 JSON)',
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '필수 여부',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_add_opts_pid_sort_idx` (`product_id`,`sort_order`),
  CONSTRAINT `g7_ecommerce_product_additional_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 추가옵션 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_categories` (
  `product_id` bigint unsigned NOT NULL COMMENT '상품 ID',
  `category_id` bigint unsigned NOT NULL COMMENT '카테고리 ID',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0' COMMENT '대표 카테고리 여부',
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `g7_ecommerce_product_categories_category_id_index` (`category_id`),
  KEY `g7_ecommerce_product_categories_is_primary_index` (`is_primary`),
  CONSTRAINT `g7_ecommerce_product_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `g7_ecommerce_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_categories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품-카테고리 연결';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_common_infos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '공통정보 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '공통정보명 (다국어 JSON: {ko: "", en: ""})',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '안내 내용 (다국어 JSON: {ko: "", en: ""})',
  `content_mode` enum('text','html') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '내용 모드 (text: 텍스트, html: HTML)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 설정 여부 (1: 기본, 0: 일반)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '사용 여부 (1: 사용, 0: 미사용)',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_common_info_active_sort_idx` (`is_active`,`sort_order`),
  KEY `ec_prod_common_info_default_idx` (`is_default`),
  FULLTEXT KEY `ft_ecommerce_product_common_infos_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ft_ecommerce_product_common_infos_content` (`content`) /*!50100 WITH PARSER `ngram` */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 공통정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '이미지 ID',
  `product_id` bigint unsigned DEFAULT NULL COMMENT '상품 ID (임시 업로드 시 null)',
  `temp_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '임시 업로드 키 (신규 상품 생성 전 이미지 그룹화)',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public' COMMENT '스토리지 디스크 (local, public, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, image/webp)',
  `file_size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `width` int unsigned DEFAULT NULL COMMENT '이미지 너비 (px)',
  `height` int unsigned DEFAULT NULL COMMENT '이미지 높이 (px)',
  `alt_text` text COLLATE utf8mb4_unicode_ci COMMENT '대체 텍스트 (다국어 JSON: {ko: "...", en: "..."})',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main' COMMENT '이미지 컬렉션: main(메인), detail(상세), additional(추가)',
  `is_thumbnail` tinyint(1) NOT NULL DEFAULT '0' COMMENT '대표 이미지 여부',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_product_images_hash_unique` (`hash`),
  KEY `g7_ecommerce_product_images_created_by_foreign` (`created_by`),
  KEY `g7_ecommerce_product_images_product_id_index` (`product_id`),
  KEY `g7_ecommerce_product_images_product_id_is_thumbnail_index` (`product_id`,`is_thumbnail`),
  KEY `g7_ecommerce_product_images_product_id_collection_index` (`product_id`,`collection`),
  KEY `g7_ecommerce_product_images_product_id_sort_order_index` (`product_id`,`sort_order`),
  KEY `g7_ecommerce_product_images_deleted_at_index` (`deleted_at`),
  KEY `g7_ecommerce_product_images_temp_key_index` (`temp_key`),
  KEY `g7_ecommerce_product_images_temp_key_collection_index` (`temp_key`,`collection`),
  CONSTRAINT `g7_ecommerce_product_images_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 이미지 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상품 문의 피벗 ID',
  `product_id` bigint unsigned NOT NULL COMMENT '상품 ID (ecommerce_products.id 참조)',
  `inquirable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '문의 원본 모델 클래스명 (예: Modules\\Sirsoft\\Board\\Models\\Post)',
  `inquirable_id` bigint unsigned NOT NULL COMMENT '문의 원본 레코드 ID (board_posts.id 등)',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '작성자 회원 ID (비회원: null)',
  `is_answered` tinyint(1) NOT NULL DEFAULT '0' COMMENT '답변 완료 여부 (false: 대기, true: 완료)',
  `answered_at` timestamp NULL DEFAULT NULL COMMENT '답변 완료 일시',
  `product_name_snapshot` text COLLATE utf8mb4_unicode_ci COMMENT '작성 시점 상품명 스냅샷 ({"ko": "상품명", "en": "Product Name"})',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  KEY `idx_inquirable` (`inquirable_type`,`inquirable_id`),
  KEY `g7_ecommerce_product_inquiries_product_id_index` (`product_id`),
  KEY `g7_ecommerce_product_inquiries_user_id_index` (`user_id`),
  KEY `idx_inquiries_created_id` (`created_at`,`id`),
  CONSTRAINT `g7_ecommerce_product_inquiries_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_inquiries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 1:1 문의 피벗 테이블 (게시판 모듈 연동)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_label_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `label_id` bigint unsigned NOT NULL,
  `custom_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '상품별 커스텀 색상 (null이면 시스템 라벨 색상 사용)',
  `custom_name` text COLLATE utf8mb4_unicode_ci COMMENT '상품별 커스텀 다국어 라벨명 (null이면 시스템 라벨명 사용)',
  `start_date` date DEFAULT NULL COMMENT '시작일',
  `end_date` date DEFAULT NULL COMMENT '종료일',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_label_assign_pid_lid_idx` (`product_id`,`label_id`),
  KEY `ec_prod_label_assign_dates_idx` (`start_date`,`end_date`),
  KEY `g7_ecommerce_product_label_assignments_label_id_foreign` (`label_id`),
  CONSTRAINT `g7_ecommerce_product_label_assignments_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `g7_ecommerce_product_labels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_label_assignments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품-라벨 연결 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_labels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '라벨명 (다국어 JSON)',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '색상 코드 (예: #FF5733)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 여부',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_labels_active_sort_idx` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 라벨 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_notice_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '템플릿명 (다국어)',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '품목 카테고리',
  `fields` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '필드 정의 JSON',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 여부',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ec_prod_notice_tpl_active_sort_idx` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품정보제공고시 템플릿';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `values` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '고시항목 값 JSON',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_product_notices_product_id_unique` (`product_id`),
  CONSTRAINT `g7_ecommerce_product_notices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품정보제공고시';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '옵션 ID',
  `product_id` bigint unsigned NOT NULL COMMENT '상품 ID',
  `option_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 코드 (조합 해시)',
  `option_values` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '옵션 값 조합: {색상: "빨강", 사이즈: "L"}',
  `option_name` text COLLATE utf8mb4_unicode_ci COMMENT '옵션 조합명 (다국어): {"ko": "빨강/L", "en": "Red/L"}',
  `price_adjustment` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '가격 조정액 (+/-)',
  `list_price` decimal(15,2) DEFAULT NULL COMMENT '정가 (null이면 상품 정가 사용)',
  `selling_price` decimal(15,2) DEFAULT NULL COMMENT '판매가 (null이면 상품 판매가 사용)',
  `currency_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '통화 코드 (저장 시 기본통화 기준)',
  `stock_quantity` int NOT NULL DEFAULT '0' COMMENT '옵션별 재고',
  `safe_stock_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '안전재고',
  `weight` decimal(10,2) DEFAULT NULL COMMENT '무게 (g)',
  `volume` decimal(10,2) DEFAULT NULL COMMENT '부피 (cm³)',
  `mileage_value` decimal(10,2) DEFAULT NULL COMMENT '마일리지 값',
  `mileage_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마일리지 타입: fixed(정액), percent(정률)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 옵션 여부',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부',
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '옵션별 SKU',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_product_options_product_id_option_code_unique` (`product_id`,`option_code`),
  CONSTRAINT `g7_ecommerce_product_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 옵션 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_review_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '리뷰 이미지 ID',
  `review_id` bigint unsigned NOT NULL COMMENT '리뷰 ID',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public' COMMENT '스토리지 디스크 (local, public, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, image/webp)',
  `file_size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `width` int unsigned DEFAULT NULL COMMENT '이미지 너비 (px)',
  `height` int unsigned DEFAULT NULL COMMENT '이미지 높이 (px)',
  `alt_text` text COLLATE utf8mb4_unicode_ci COMMENT '대체 텍스트 (다국어 JSON: {ko: "...", en: "..."})',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'review' COMMENT '이미지 컬렉션: review(리뷰)',
  `is_thumbnail` tinyint(1) NOT NULL DEFAULT '0' COMMENT '대표 이미지 여부',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '업로더 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_product_review_images_hash_unique` (`hash`),
  KEY `g7_ecommerce_product_review_images_created_by_foreign` (`created_by`),
  KEY `g7_ecommerce_product_review_images_review_id_index` (`review_id`),
  KEY `g7_ecommerce_product_review_images_deleted_at_index` (`deleted_at`),
  CONSTRAINT `g7_ecommerce_product_review_images_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_product_review_images_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `g7_ecommerce_product_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 리뷰 이미지 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '리뷰 ID',
  `product_id` bigint unsigned NOT NULL COMMENT '상품 ID',
  `order_option_id` bigint unsigned NOT NULL COMMENT '주문 옵션 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '작성자 ID',
  `rating` tinyint unsigned NOT NULL COMMENT '별점 (1~5)',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '리뷰 내용',
  `content_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '콘텐츠 모드: text / html',
  `option_snapshot` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '주문 시점 옵션 스냅샷 (옵션명 보존용)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible' COMMENT '리뷰 상태: visible / hidden',
  `reply_content` text COLLATE utf8mb4_unicode_ci COMMENT '판매자 답변 내용',
  `reply_content_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '답변 콘텐츠 모드: text / html',
  `reply_admin_id` bigint unsigned DEFAULT NULL COMMENT '답변 등록 관리자 ID',
  `replied_at` timestamp NULL DEFAULT NULL COMMENT '답변 등록일시',
  `reply_updated_at` timestamp NULL DEFAULT NULL COMMENT '답변 수정일시',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_product_reviews_reply_admin_id_foreign` (`reply_admin_id`),
  KEY `g7_ecommerce_product_reviews_product_id_index` (`product_id`),
  KEY `g7_ecommerce_product_reviews_user_id_index` (`user_id`),
  KEY `g7_ecommerce_product_reviews_order_option_id_index` (`order_option_id`),
  KEY `g7_ecommerce_product_reviews_product_id_status_index` (`product_id`,`status`),
  KEY `g7_ecommerce_product_reviews_user_id_product_id_index` (`user_id`,`product_id`),
  KEY `g7_ecommerce_product_reviews_deleted_at_index` (`deleted_at`),
  KEY `idx_reviews_replied_at_deleted_at` (`replied_at`,`deleted_at`),
  KEY `idx_reviews_product_status_deleted_created` (`product_id`,`status`,`deleted_at`,`created_at`),
  KEY `idx_reviews_deleted_created_id` (`deleted_at`,`created_at`,`id`),
  CONSTRAINT `g7_ecommerce_product_reviews_order_option_id_foreign` FOREIGN KEY (`order_option_id`) REFERENCES `g7_ecommerce_order_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_reviews_reply_admin_id_foreign` FOREIGN KEY (`reply_admin_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_product_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 리뷰 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_product_wishlists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '찜 ID',
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_product_wishlists_user_product_unique` (`user_id`,`product_id`),
  KEY `ecommerce_product_wishlists_product_id_index` (`product_id`),
  CONSTRAINT `g7_ecommerce_product_wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_product_wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상품 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '상품명 (다국어 JSON: {ko: "...", en: "..."})',
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '상품코드',
  `sales_product_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '판매자 상품코드 (사용자 입력용)',
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SKU',
  `brand_id` bigint unsigned DEFAULT NULL COMMENT '브랜드 ID',
  `list_price` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '정가 (기본통화 기준)',
  `selling_price` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '판매가 (기본통화 기준)',
  `currency_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '통화 코드 (저장 시 기본통화 기준)',
  `stock_quantity` int NOT NULL DEFAULT '0' COMMENT '재고 수량 (옵션 있으면 옵션 합계)',
  `safe_stock_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '안전재고 수량',
  `sales_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_sale' COMMENT '판매상태: on_sale(판매중), suspended(판매중지), sold_out(품절), coming_soon(출시예정)',
  `display_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible' COMMENT '전시상태: visible(전시), hidden(숨김)',
  `tax_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'taxable' COMMENT '과세여부: taxable(과세), tax_free(면세)',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '세율 (%)',
  `shipping_policy_id` bigint unsigned DEFAULT NULL COMMENT '배송정책 ID',
  `common_info_id` bigint unsigned DEFAULT NULL COMMENT '공통정보 템플릿 ID',
  `min_purchase_qty` int unsigned NOT NULL DEFAULT '1' COMMENT '최소 구매 수량',
  `max_purchase_qty` int unsigned NOT NULL DEFAULT '0' COMMENT '최대 구매 수량 (0=무제한)',
  `purchase_restriction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none' COMMENT '구매 제한: none(없음), restricted(제한)',
  `allowed_roles` text COLLATE utf8mb4_unicode_ci COMMENT '구매 허용 역할 ID 배열',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '상세 설명 (다국어 JSON, HTML 포함)',
  `description_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '설명 모드: text(텍스트), html(HTML)',
  `content_thumbnail_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '설명 첫 내부 이미지 URL 캐시 — 상품 이미지가 없을 때 목록/공유 썸네일 폴백',
  `meta_title` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 제목 (다국어 JSON)',
  `seo_sync_title` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'SEO 제목 동기화 여부 (1: 상품명으로 자동 채움, 0: 직접 입력 보존)',
  `meta_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 설명 (다국어 JSON)',
  `seo_sync_description` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'SEO 설명 동기화 여부 (1: 상품 설명으로 자동 채움, 0: 직접 입력 보존)',
  `meta_keywords` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 키워드 (배열)',
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '바코드',
  `hs_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HS 코드 (관세 분류)',
  `has_options` tinyint(1) NOT NULL DEFAULT '0' COMMENT '옵션 사용 여부',
  `option_groups` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '옵션 그룹 정의: [{name: "색상", values: ["빨강", "파랑"]}]',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_products_product_code_unique` (`product_code`),
  KEY `g7_ecommerce_products_product_code_index` (`product_code`),
  KEY `g7_ecommerce_products_sku_index` (`sku`),
  KEY `g7_ecommerce_products_sales_status_index` (`sales_status`),
  KEY `g7_ecommerce_products_display_status_index` (`display_status`),
  KEY `g7_ecommerce_products_brand_id_index` (`brand_id`),
  KEY `g7_ecommerce_products_created_at_index` (`created_at`),
  KEY `g7_ecommerce_products_sales_status_display_status_index` (`sales_status`,`display_status`),
  KEY `idx_ecommerce_products_selling_price` (`selling_price`),
  KEY `idx_ecommerce_products_list_price` (`list_price`),
  KEY `idx_ecommerce_products_stock_quantity` (`stock_quantity`),
  KEY `idx_ecommerce_products_shipping_policy` (`shipping_policy_id`),
  KEY `idx_ecommerce_products_barcode` (`barcode`),
  KEY `idx_ecommerce_products_tax_status` (`tax_status`),
  KEY `idx_ecommerce_products_updated_at` (`updated_at`),
  KEY `idx_products_deleted_created_id` (`deleted_at`,`created_at`,`id`),
  KEY `idx_products_display_created_id` (`display_status`,`created_at`,`id`),
  KEY `idx_products_display_price_id` (`display_status`,`selling_price`,`id`),
  FULLTEXT KEY `ft_ecommerce_products_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ft_ecommerce_products_description` (`description`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_ecommerce_products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `g7_ecommerce_brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_promotion_coupon_categories` (
  `coupon_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `type` enum('include','exclude') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'include' COMMENT '적용유형: include(포함), exclude(제외)',
  PRIMARY KEY (`coupon_id`,`category_id`,`type`),
  KEY `idx_type` (`type`),
  KEY `g7_ecommerce_promotion_coupon_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `g7_ecommerce_promotion_coupon_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `g7_ecommerce_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_promotion_coupon_categories_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `g7_ecommerce_promotion_coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='쿠폰 적용 카테고리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_promotion_coupon_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '발급 내역 ID',
  `coupon_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '쿠폰 코드 (다운로드 쿠폰 시)',
  `status` enum('available','used','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available' COMMENT '상태: available(사용가능), used(사용완료), expired(만료), cancelled(취소)',
  `issued_at` datetime NOT NULL COMMENT '발급일시',
  `expired_at` datetime DEFAULT NULL COMMENT '만료일시',
  `used_at` datetime DEFAULT NULL COMMENT '사용일시',
  `order_id` bigint unsigned DEFAULT NULL,
  `discount_amount` decimal(15,2) DEFAULT NULL COMMENT '실제 할인 금액',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_coupon_code` (`coupon_code`),
  KEY `g7_ecommerce_promotion_coupon_issues_order_id_foreign` (`order_id`),
  KEY `idx_coupon_issues_issued_id` (`issued_at`,`id`),
  CONSTRAINT `g7_ecommerce_promotion_coupon_issues_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `g7_ecommerce_promotion_coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_promotion_coupon_issues_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `g7_ecommerce_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_ecommerce_promotion_coupon_issues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='쿠폰 발급 내역';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_promotion_coupon_products` (
  `coupon_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `type` enum('include','exclude') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'include' COMMENT '적용유형: include(포함), exclude(제외)',
  PRIMARY KEY (`coupon_id`,`product_id`,`type`),
  KEY `idx_type` (`type`),
  KEY `g7_ecommerce_promotion_coupon_products_product_id_foreign` (`product_id`),
  CONSTRAINT `g7_ecommerce_promotion_coupon_products_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `g7_ecommerce_promotion_coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_ecommerce_promotion_coupon_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `g7_ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='쿠폰 적용 상품';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_promotion_coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '쿠폰 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '쿠폰명 (다국어)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '설명 (다국어)',
  `target_type` enum('product_amount','order_amount','shipping_fee') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '적용대상: product_amount(상품금액), order_amount(주문금액), shipping_fee(배송비)',
  `discount_type` enum('fixed','rate') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '혜택유형: fixed(정액), rate(정률)',
  `discount_value` decimal(15,2) NOT NULL COMMENT '혜택값 (정액: 금액, 정률: %)',
  `discount_max_amount` decimal(15,2) DEFAULT NULL COMMENT '최대 할인액 (정률 시)',
  `min_order_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '최소 주문금액',
  `issue_method` enum('direct','download','auto') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급방법: direct(직접발급), download(다운로드), auto(자동발급)',
  `issue_condition` enum('manual','signup','first_purchase','birthday') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급조건: manual(수동), signup(회원가입), first_purchase(첫구매), birthday(생일)',
  `issue_status` enum('issuing','stopped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issuing' COMMENT '발급상태: issuing(발급중), stopped(발급중단)',
  `total_quantity` int unsigned DEFAULT NULL COMMENT '총 발급 수량 (NULL=무제한)',
  `issued_count` int unsigned NOT NULL DEFAULT '0' COMMENT '현재 발급된 수량',
  `per_user_limit` int unsigned NOT NULL DEFAULT '1' COMMENT '회원당 발급 제한',
  `valid_type` enum('period','days_from_issue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'period' COMMENT '유효기간 유형: period(기간지정), days_from_issue(발급일로부터)',
  `valid_days` int unsigned DEFAULT NULL COMMENT '발급일로부터 N일 (valid_type=days_from_issue)',
  `valid_from` datetime DEFAULT NULL COMMENT '유효기간 시작',
  `valid_to` datetime DEFAULT NULL COMMENT '유효기간 종료',
  `issue_from` datetime DEFAULT NULL COMMENT '발급기간 시작',
  `issue_to` datetime DEFAULT NULL COMMENT '발급기간 종료',
  `is_combinable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '다른 쿠폰과 중복 사용 가능 여부',
  `target_scope` enum('all','products','categories') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all' COMMENT '적용 범위: all(전체), products(특정상품), categories(특정카테고리)',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '삭제일시 (Soft Delete)',
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_promotion_coupons_created_by_foreign` (`created_by`),
  KEY `idx_issue_status` (`issue_status`),
  KEY `idx_target_type` (`target_type`),
  KEY `idx_issue_method` (`issue_method`),
  KEY `idx_issue_condition` (`issue_condition`),
  KEY `idx_valid_period` (`valid_from`,`valid_to`),
  KEY `idx_issue_period` (`issue_from`,`issue_to`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `ft_ecommerce_promotion_coupons_name` (`name`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ft_ecommerce_promotion_coupons_description` (`description`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_ecommerce_promotion_coupons_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='프로모션 쿠폰 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_search_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '프리셋 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '사용자 ID',
  `target_screen` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '대상 화면: products, orders, customers 등',
  `preset_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '프리셋 이름',
  `conditions` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '검색 조건 JSON',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 프리셋 여부',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_search_presets_unique` (`user_id`,`target_screen`,`preset_name`),
  KEY `ecommerce_search_presets_user_screen` (`user_id`,`target_screen`),
  CONSTRAINT `g7_ecommerce_search_presets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='검색 프리셋 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_sequence_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '시퀀스 코드 ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '시퀀스 타입 (product, order, shipping)',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발급된 코드',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '발급 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_sequence_codes_type_code_unique` (`type`,`code`),
  KEY `ecommerce_sequence_codes_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='채번 코드 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '시퀀스 ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '시퀀스 타입 (product: 상품, order: 주문, shipping: 배송)',
  `algorithm` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sequential' COMMENT '채번 알고리즘 (hybrid: 타임스탬프+시퀀스, sequential: 순수시퀀스, daily: 일별리셋)',
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '코드 접두사',
  `current_value` bigint unsigned NOT NULL DEFAULT '0' COMMENT '현재 시퀀스 값',
  `increment` int unsigned NOT NULL DEFAULT '1' COMMENT '증가 단위',
  `min_value` bigint unsigned NOT NULL DEFAULT '1' COMMENT '최소값',
  `max_value` bigint unsigned NOT NULL DEFAULT '9999999999' COMMENT '최대값 (10자리)',
  `cycle` tinyint(1) NOT NULL DEFAULT '0' COMMENT '순환 여부 (1: max 도달 시 min으로 순환, 0: 비순환)',
  `pad_length` int unsigned NOT NULL DEFAULT '10' COMMENT '자릿수 패딩 (10: 0000000001 형식)',
  `max_history_count` int unsigned NOT NULL DEFAULT '0' COMMENT '코드 이력 최대 보관 건수 (0: 무제한)',
  `date_format` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '날짜 형식 (일별 리셋 시 사용, 예: Ymd)',
  `last_reset_date` date DEFAULT NULL COMMENT '마지막 리셋 날짜 (일별 리셋용)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_sequences_type_date_unique` (`type`,`last_reset_date`),
  KEY `ecommerce_sequences_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='채번 시퀀스 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_shipping_carriers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송사 ID',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 코드 (cj, fedex 등)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 배송사명 {"ko":"CJ대한통운","en":"CJ Logistics"}',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송사 유형: domestic(국내), international(해외)',
  `tracking_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '배송 추적 URL 템플릿 ({tracking_number} 치환)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부: true(활성), false(비활성)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '정렬 순서 (작을수록 먼저)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "tracking_url", "sort_order"])',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_shipping_carriers_code_unique` (`code`),
  KEY `idx_carriers_type` (`type`),
  KEY `idx_carriers_is_active` (`is_active`),
  KEY `idx_carriers_active_type` (`is_active`,`type`),
  KEY `idx_carriers_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송사 마스터';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_shipping_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송정책 ID',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송정책명 (다국어 JSON: {ko: "...", en: "..."})',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '사용여부: true(사용), false(미사용)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 배송정책 여부',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '정렬순서 (낮을수록 먼저)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_ecommerce_shipping_policies_is_active_index` (`is_active`),
  KEY `g7_ecommerce_shipping_policies_sort_order_index` (`sort_order`),
  KEY `g7_ecommerce_shipping_policies_created_at_index` (`created_at`),
  KEY `g7_ecommerce_shipping_policies_is_active_sort_order_index` (`is_active`,`sort_order`),
  KEY `g7_ecommerce_shipping_policies_is_default_index` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송 정책 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_shipping_policy_country_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '국가별 설정 ID',
  `shipping_policy_id` bigint unsigned NOT NULL COMMENT '배송정책 ID',
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '국가코드 (KR, US 등)',
  `shipping_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송방법: parcel(택배), collect(착불), quick(퀵서비스), direct(직접배송), pickup(방문수령), other(기타)',
  `custom_shipping_name` text COLLATE utf8mb4_unicode_ci COMMENT '배송방법이 custom일 때 사용자 입력 배송방법명 (다국어 JSON)',
  `currency_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KRW' COMMENT '기준통화 코드',
  `charge_policy` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '부과정책: free(무료), fixed(고정), conditional_free(조건부무료), range_amount(구간별금액), range_quantity(구간별수량), range_weight(구간별무게), range_volume(구간별부피), range_volume_weight(구간별부피+무게), per_quantity(단위수량), per_weight(단위무게), per_volume(단위부피), per_volume_weight(단위부피+무게), per_amount(단위금액), api(계산API)',
  `base_fee` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '기본 배송비',
  `free_threshold` decimal(12,2) DEFAULT NULL COMMENT '무료배송 기준금액 (conditional_free 시 사용)',
  `ranges` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '구간별/단위별 설정 JSON: {type, tiers: [{min, max, fee}], unit_value}',
  `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '배송비 계산 API URL',
  `api_request_fields` text COLLATE utf8mb4_unicode_ci COMMENT 'API 전송 필드 목록',
  `api_response_fee_field` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'API 응답에서 배송비 값을 추출할 필드명',
  `api_config` text COLLATE utf8mb4_unicode_ci COMMENT '계산 API 연동 상세 설정 JSON: {http_method(GET|POST), auth_type(none|bearer|custom_header), auth_token(암호화), auth_header_name, response_type(json|text), response_path, field_map{우리키:외부키}}',
  `extra_fee_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '추가배송비(도서산간) 사용여부',
  `extra_fee_settings` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '추가배송비 설정 JSON: [{zipcode, fee}]',
  `extra_fee_multiply` tinyint(1) NOT NULL DEFAULT '0' COMMENT '추가배송비 수량비례 적용여부',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '사용여부',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_policy_country` (`shipping_policy_id`,`country_code`),
  KEY `idx_cs_country_code` (`country_code`),
  KEY `g7_ecommerce_shipping_policy_country_settings_is_active_index` (`is_active`),
  KEY `idx_policy_active` (`shipping_policy_id`,`is_active`),
  CONSTRAINT `fk_cs_shipping_policy_id` FOREIGN KEY (`shipping_policy_id`) REFERENCES `g7_ecommerce_shipping_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송정책 국가별 설정';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_shipping_policy_extra_fee_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '추가배송비 템플릿 ID',
  `zipcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '우편번호 (단일 또는 범위)',
  `fee` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '추가 배송비',
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '지역명 (예: 제주도, 울릉도)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '설명 (예: 도서산간 지역)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '사용여부: true(사용), false(미사용)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_extra_fee_tpl_zipcode` (`zipcode`),
  KEY `g7_ecommerce_shipping_policy_extra_fee_templates_region_index` (`region`),
  KEY `idx_extra_fee_tpl_is_active` (`is_active`),
  KEY `g7_ecommerce_shipping_policy_extra_fee_templates_fee_index` (`fee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송 추가비용 템플릿';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_shipping_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송유형 ID',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 코드 (parcel, pickup 등)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 배송유형명 {"ko":"택배","en":"Parcel"}',
  `category` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '카테고리: domestic(국내), international(해외), other(기타)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부: true(활성), false(비활성)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '정렬 순서 (작을수록 먼저)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "category", "is_active", "sort_order"])',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_shipping_types_code_unique` (`code`),
  KEY `idx_shipping_types_category` (`category`),
  KEY `idx_shipping_types_is_active` (`is_active`),
  KEY `idx_shipping_types_active_category` (`is_active`,`category`),
  KEY `idx_shipping_types_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송유형 마스터';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '집계 행 ID',
  `date` date NOT NULL COMMENT '집계 기준 날짜 (하루 1행, 멱등 upsert 키)',
  `sales_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '해당 날짜 판매 수량 (매출 반영 상태 옵션의 유효수량 합)',
  `sales_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '해당 날짜 상품 순매출 (매출 반영 상태 옵션의 unit_price × 유효수량 합)',
  `option_status_counts` json DEFAULT NULL COMMENT '상태별 당일 판매 수량 (option_status 7버킷)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_ecommerce_stats_date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_temp_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '임시주문 ID',
  `cart_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비회원 장바구니 키',
  `user_id` bigint unsigned DEFAULT NULL,
  `items` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '장바구니 아이템 스냅샷 [{product_id, product_option_id, quantity}]',
  `calculation_result` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'OrderCalculationResult JSON',
  `calculation_input` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '계산 입력 {promotions, use_points, shipping_address}',
  `expires_at` timestamp NOT NULL COMMENT '만료일시 (기본 30분)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_temp_orders_cart_key_index` (`cart_key`),
  KEY `ecommerce_temp_orders_user_id_index` (`user_id`),
  KEY `ecommerce_temp_orders_expires_at_index` (`expires_at`),
  CONSTRAINT `g7_ecommerce_temp_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='임시 주문 (주문서 작성 단계)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_user_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '배송지 ID',
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배송지 별칭',
  `recipient_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수령인 이름',
  `recipient_phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수령인 연락처 (국제 형식 포함)',
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KR' COMMENT '국가 코드 (ISO 3166-1 alpha-2: KR, US, JP, DE 등)',
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '우편번호 (국내)',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '기본주소 (국내)',
  `address_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '상세주소 (국내)',
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '해외 주소 1 (Street address)',
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '해외 주소 2 (Apt, Suite, Unit 등)',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '도시 (City)',
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '주/지역 (State/Province/Region)',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '우편번호 (해외)',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '기본 배송지 여부',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_user_addresses_user_id_index` (`user_id`),
  KEY `ecommerce_user_addresses_user_default_index` (`user_id`,`is_default`),
  KEY `ecommerce_user_addresses_country_code_index` (`country_code`),
  CONSTRAINT `g7_ecommerce_user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 저장 배송지 정보 (국내/해외 지원)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_ecommerce_user_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '이커머스 사용자 프로필 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '사용자 ID (코어 users FK)',
  `preferred_currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '선호 결제 통화 코드 (ISO 4217, 미설정 시 default_currency 폴백)',
  `preferred_shipping_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '선호 배송국가 코드 (ISO 3166-1 alpha-2, 미설정 시 GeoIP→default_country 폴백)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_user_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `g7_ecommerce_user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이커머스 사용자별 프로필 (결제 통화 등)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '고유 식별자',
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '연결 정보',
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '큐 이름',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '작업 페이로드',
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '예외 정보',
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '실패 시간',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실패한 작업 로그';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_identity_message_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `provider_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IDV 프로바이더 ID (예: g7:core.mail, kcp, portone)',
  `scope_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '메시지 정의 스코프 (provider_default|purpose|policy) — App\\Enums\\IdentityMessageScopeType enum',
  `scope_value` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '범위 값: provider_default 빈 문자열 / purpose 키 / policy 키',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 표시명 ({"ko":"...", "en":"..."})',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '다국어 설명',
  `channels` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '활성 채널 (현재 ["mail"], 향후 sms 등 확장)',
  `variables` text COLLATE utf8mb4_unicode_ci COMMENT '사용 가능 변수 메타데이터 ([{key, description}])',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 타입: core, module, plugin',
  `extension_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 식별자',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시더 생성 여부',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '운영자가 수정한 필드명 목록 (예: ["name","is_active"])',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identity_message_def_provider_scope_unique` (`provider_id`,`scope_type`,`scope_value`),
  KEY `idx_identity_message_def_provider_scope` (`provider_id`,`scope_type`),
  KEY `idx_identity_message_def_extension` (`extension_type`,`extension_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_identity_message_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `definition_id` bigint unsigned NOT NULL COMMENT '메시지 정의 ID (FK)',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '메시지 템플릿 채널 (mail 현재 / sms 등 향후) — IdentityVerificationChannel 과는 별개의 도메인 분류',
  `subject` text COLLATE utf8mb4_unicode_ci COMMENT '다국어 제목 ({"ko":"...", "en":"..."}) — mail 채널에서만 의미',
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 본문 ({"ko":"...", "en":"..."})',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '해당 채널 활성 여부',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시더 생성 여부 (운영자 편집 시 false)',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '운영자가 수정한 필드명 목록 (예: ["subject","body","is_active"])',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 (사용자 삭제 시 NULL)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identity_message_tpl_def_channel_unique` (`definition_id`,`channel`),
  KEY `g7_identity_message_templates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `g7_identity_message_templates_definition_id_foreign` FOREIGN KEY (`definition_id`) REFERENCES `g7_identity_message_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_identity_message_templates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_identity_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '정책 식별자 (예: core.profile.password_change)',
  `scope` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '정책 적용 범위 (route|hook|custom) — App\\Enums\\IdentityPolicyScope enum',
  `target` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '라우트명/URI 패턴, 훅 이름, 또는 custom key',
  `purpose` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IDV 목적 (signup|password_reset|self_update|sensitive_action|*module-defined*) — 코어 4종은 App\\Enums\\IdentityVerificationPurpose enum',
  `provider_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'null 이면 purpose 기본 provider 사용',
  `grace_minutes` int unsigned NOT NULL DEFAULT '0' COMMENT '최근 N 분 이내 동일 purpose verified 재사용 허용',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 여부',
  `priority` smallint unsigned NOT NULL DEFAULT '100' COMMENT '매칭 우선순위 (DESC)',
  `conditions` json DEFAULT NULL COMMENT '역할/HTTP 메서드/파라미터 매칭 조건 JSON',
  `source_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '정책 출처 (core|module|plugin|admin) — App\\Enums\\IdentityPolicySourceType enum',
  `source_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '출처 식별자 (예: sirsoft-ecommerce)',
  `applies_to` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both' COMMENT '대상 사용자 스코프 (self|admin|both) — App\\Enums\\IdentityPolicyAppliesTo enum',
  `fail_mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'block' COMMENT '정책 실패 시 동작 (block=HTTP 428 / log_only=감사 로그만) — App\\Enums\\IdentityPolicyFailMode enum',
  `user_overrides` json DEFAULT NULL COMMENT '운영자가 S1d UI 로 수정한 필드 목록 (Seeder 재실행 시 보존)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_identity_policies_key_unique` (`key`),
  KEY `idx_idp_scope_target_enabled` (`scope`,`target`,`enabled`),
  KEY `idx_idp_source` (`source_type`,`source_identifier`),
  KEY `g7_identity_policies_scope_index` (`scope`),
  KEY `g7_identity_policies_enabled_index` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_identity_verification_logs` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Challenge UUID',
  `provider_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '프로바이더 식별자 (예: g7:core.mail, kcp)',
  `purpose` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '인증 목적 (signup|password_reset|self_update|sensitive_action|*module-defined*) — 코어 4종은 App\\Enums\\IdentityVerificationPurpose enum, 모듈/플러그인은 declaredPurposes 레지스트리',
  `channel` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '인증 채널 (email|sms|ipin|...) — 코어 enum=App\\Enums\\IdentityVerificationChannel, 모듈/플러그인 provider 가 추가 채널 등록 가능',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '사용자 탈퇴 시 NULL 로 유지 — 감사 이력 보존 (CASCADE 금지 규정 준수)',
  `target_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA256(email|phone) — PII 원본 저장 회피',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'requested|sent|processing|verified|failed|expired|cancelled|policy_violation_logged',
  `render_hint` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '프론트 렌더 힌트 (text_code|link|external_redirect)',
  `attempts` smallint unsigned NOT NULL DEFAULT '0' COMMENT '시도 횟수',
  `max_attempts` smallint unsigned NOT NULL DEFAULT '5' COMMENT '허용 최대 시도 횟수',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 IP',
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 User-Agent',
  `origin_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '인증 트리거 출처 유형 (route|hook|policy|middleware|api|custom|system) — App\\Enums\\IdentityOriginType enum',
  `origin_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '실제 경로/훅명/정책키 (예: PUT /api/me/password, core.user.before_update)',
  `origin_policy_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '정책이 트리거한 경우 identity_policies.key 참조',
  `properties` json DEFAULT NULL COMMENT '요청 페이로드 요약',
  `metadata` json DEFAULT NULL COMMENT '프로바이더 내부 데이터 (코드 해시 등)',
  `verification_token` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mode B verify 성공 시 발급되는 서명 토큰 (purpose+target_hash 바인딩)',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Challenge 만료 시각',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT '검증 완료 시각',
  `consumed_at` timestamp NULL DEFAULT NULL COMMENT 'Mode B verification_token 이 register 에서 소비된 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_identity_verification_logs_verification_token_unique` (`verification_token`),
  KEY `idx_idv_logs_provider_status_created` (`provider_id`,`status`,`created_at`),
  KEY `idx_idv_logs_origin` (`origin_type`,`origin_identifier`),
  KEY `idx_idv_logs_purpose_user_verified` (`purpose`,`user_id`,`verified_at`),
  KEY `g7_identity_verification_logs_provider_id_index` (`provider_id`),
  KEY `g7_identity_verification_logs_purpose_index` (`purpose`),
  KEY `g7_identity_verification_logs_user_id_index` (`user_id`),
  KEY `g7_identity_verification_logs_target_hash_index` (`target_hash`),
  KEY `g7_identity_verification_logs_status_index` (`status`),
  KEY `g7_identity_verification_logs_origin_type_index` (`origin_type`),
  KEY `g7_identity_verification_logs_origin_identifier_index` (`origin_identifier`),
  KEY `g7_identity_verification_logs_origin_policy_key_index` (`origin_policy_key`),
  KEY `g7_identity_verification_logs_expires_at_index` (`expires_at`),
  CONSTRAINT `g7_identity_verification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배치 ID',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '배치 이름',
  `total_jobs` int NOT NULL COMMENT '전체 작업 수',
  `pending_jobs` int NOT NULL COMMENT '대기 중인 작업 수',
  `failed_jobs` int NOT NULL COMMENT '실패한 작업 수',
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '실패한 작업 ID 목록',
  `options` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '배치 옵션',
  `cancelled_at` int DEFAULT NULL COMMENT '취소 시간',
  `created_at` int NOT NULL COMMENT '생성 시간',
  `finished_at` int DEFAULT NULL COMMENT '완료 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='작업 배치 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '큐 이름',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '작업 페이로드',
  `attempts` tinyint unsigned NOT NULL COMMENT '시도 횟수',
  `reserved_at` int unsigned DEFAULT NULL COMMENT '예약 시간',
  `available_at` int unsigned NOT NULL COMMENT '실행 가능 시간',
  `created_at` int unsigned NOT NULL COMMENT '생성 시간',
  PRIMARY KEY (`id`),
  KEY `g7_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='작업 큐 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_language_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '언어팩 ID',
  `identifier` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '언어팩 고유 식별자 ({vendor}-{scope}-{target?}-{locale})',
  `vendor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '언어팩 제작자 식별자',
  `scope` enum('core','module','plugin','template') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '적용 대상 분류',
  `target_identifier` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '대상 확장 식별자 (scope=core일 때 null)',
  `locale` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IETF BCP-47 locale 태그',
  `locale_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '영문 언어명',
  `locale_native_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원어 언어명',
  `text_direction` enum('ltr','rtl') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ltr' COMMENT '텍스트 방향',
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '언어팩 버전',
  `latest_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '최신 버전 (자동 업데이트 체크)',
  `target_version_constraint` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '대상 확장 버전 제약 (semver)',
  `target_version_mismatch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '대상 버전 불일치 경고 플래그',
  `license` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '라이선스',
  `description` json DEFAULT NULL COMMENT '언어팩 설명 (다국어)',
  `status` enum('installed','active','inactive','updating','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'installed' COMMENT '언어팩 상태',
  `deactivated_reason` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비활성화 사유: manual(사용자 수동) | incompatible_core(코어 버전 호환성) | null(active)',
  `deactivated_at` timestamp NULL DEFAULT NULL COMMENT '비활성화 시점',
  `incompatible_required_version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'incompatible_core 시 요구된 g7_version 제약 (재호환 판정용)',
  `is_protected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '제거 보호 플래그 (번들 팩)',
  `manifest` json NOT NULL COMMENT 'language-pack.json 전체 스냅샷',
  `source_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '설치 소스 유형 (zip/github/url/bundled/bundled_with_extension)',
  `source_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '설치 소스 URL 또는 경로',
  `installed_by` bigint unsigned DEFAULT NULL COMMENT '설치자 사용자 ID',
  `installed_at` timestamp NULL DEFAULT NULL COMMENT '설치 시각',
  `activated_at` timestamp NULL DEFAULT NULL COMMENT '활성화 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_language_packs_identifier_unique` (`identifier`),
  KEY `g7_language_packs_installed_by_foreign` (`installed_by`),
  KEY `language_packs_slot_index` (`scope`,`target_identifier`,`locale`),
  KEY `language_packs_status_index` (`status`),
  KEY `language_packs_vendor_index` (`vendor`),
  KEY `language_packs_deactivated_reason_index` (`deactivated_reason`),
  CONSTRAINT `g7_language_packs_installed_by_foreign` FOREIGN KEY (`installed_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_mail_send_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '확장 타입 (core, module, plugin)',
  `extension_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '확장 식별자',
  `sender_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발송자 이메일',
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발송자 이름',
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수신자 이메일',
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '수신자 이름',
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '이메일 제목',
  `body` longtext COLLATE utf8mb4_unicode_ci COMMENT '이메일 본문 (HTML)',
  `template_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '템플릿 유형 (welcome, new_comment 등)',
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발송 출처 (notification, test_mail 등)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent' COMMENT '발송 상태 (sent, failed)',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '실패 시 에러 메시지',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '발송 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mail_send_logs_recipient` (`recipient_email`),
  KEY `idx_mail_send_logs_template` (`template_type`),
  KEY `idx_mail_send_logs_sent_at` (`sent_at`),
  KEY `idx_mail_send_logs_extension` (`extension_type`,`extension_identifier`),
  KEY `idx_mail_send_logs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_mail_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '템플릿 유형 (welcome, reset_password 등)',
  `subject` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 제목 ({"ko": "...", "en": "..."})',
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 HTML 본문 ({"ko": "...", "en": "..."})',
  `variables` text COLLATE utf8mb4_unicode_ci COMMENT '사용 가능 변수 메타데이터 ([{key, description}])',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부 (false = 해당 유형 메일 발송 중단)',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시더 생성 항목 여부',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '사용자가 수정한 필드명 목록 (코어 업데이트 시 보존)',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_mail_templates_type_unique` (`type`),
  KEY `g7_mail_templates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `g7_mail_templates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='코어 메일 템플릿';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_menu_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `permission_type` enum('read','write','delete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'read' COMMENT '권한 유형 (read: 읽기, write: 쓰기, delete: 삭제)',
  `is_allowed` tinyint(1) NOT NULL DEFAULT '1' COMMENT '허용 여부 (1: 허용, 0: 거부)',
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '권한 부여 일시',
  `granted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_menu_permissions_role_id_foreign` (`role_id`),
  KEY `g7_menu_permissions_user_id_foreign` (`user_id`),
  KEY `g7_menu_permissions_granted_by_foreign` (`granted_by`),
  KEY `g7_menu_permissions_menu_id_role_id_index` (`menu_id`,`role_id`),
  KEY `g7_menu_permissions_menu_id_user_id_index` (`menu_id`,`user_id`),
  CONSTRAINT `g7_menu_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_menu_permissions_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `g7_menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_menu_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `g7_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_menu_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메뉴별 접근 권한 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '메뉴 이름 (다국어 JSON)',
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '메뉴 슬러그',
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '메뉴 URL',
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '메뉴 아이콘',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '상위 메뉴 ID',
  `order` int NOT NULL DEFAULT '0' COMMENT '메뉴 순서',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 상태 (1: 활성, 0: 비활성)',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의)',
  `extension_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 식별자 (예: core, sirsoft-board, sirsoft-payment)',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "icon", "order"])',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '등록자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_menus_slug_unique` (`slug`),
  KEY `g7_menus_created_by_foreign` (`created_by`),
  KEY `g7_menus_parent_id_order_index` (`parent_id`,`order`),
  KEY `g7_menus_is_active_order_index` (`is_active`,`order`),
  KEY `idx_menus_extension` (`extension_type`,`extension_identifier`),
  CONSTRAINT `g7_menus_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_menus_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `g7_menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 메뉴 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '모듈 고유 식별자 (vendor-module 형식)',
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '벤더/개발자명',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '모듈 이름 (다국어 JSON)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '모듈 설명 (다국어 JSON)',
  `github_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 저장소 URL',
  `github_changelog_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 변경 내역 URL',
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '추가 메타데이터',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '모듈 버전',
  `latest_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마켓플레이스 최신 버전',
  `status` enum('active','inactive','installing','uninstalling','updating') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive' COMMENT '상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중)',
  `deactivated_reason` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비활성화 사유: manual(사용자 수동) | incompatible_core(코어 버전 호환성) | null(active)',
  `deactivated_at` timestamp NULL DEFAULT NULL COMMENT '비활성화 시점',
  `incompatible_required_version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'incompatible_core 시 요구된 g7_version 제약 (재호환 판정용)',
  `vendor_mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto' COMMENT 'Vendor 설치 모드 (auto|composer|bundled)',
  `update_available` tinyint(1) NOT NULL DEFAULT '0' COMMENT '업데이트 가능 여부',
  `update_source` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '업데이트 출처 (github, pending, bundled)',
  `config` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '모듈 설정 정보',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '모듈 생성자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_modules_identifier_unique` (`identifier`),
  KEY `g7_modules_created_by_foreign` (`created_by`),
  KEY `g7_modules_vendor_index` (`vendor`),
  KEY `g7_modules_status_index` (`status`),
  KEY `modules_deactivated_reason_index` (`deactivated_reason`),
  CONSTRAINT `g7_modules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 모듈 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_notification_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '알림 타입 (welcome, order_confirmed 등)',
  `hook_prefix` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '훅 접두사 (core.auth, sirsoft-ecommerce 등)',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 타입: core, module, plugin',
  `extension_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 식별자: core, sirsoft-board 등',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 이름 ({"ko": "회원가입 환영", "en": "Welcome"})',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '다국어 설명',
  `variables` text COLLATE utf8mb4_unicode_ci COMMENT '사용 가능 변수 메타데이터 ([{key, description}])',
  `channels` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '활성 채널 (["mail", "database"])',
  `hooks` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '트리거 훅 목록 (["core.auth.after_register"])',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 여부',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시더 생성 여부',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "is_active"])',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_notification_definitions_type_unique` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_notification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '채널: mail, database, fcm 등',
  `notification_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '알림 타입: welcome, order_confirmed 등',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '확장 타입: core, module, plugin',
  `extension_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT '확장 식별자',
  `recipient_user_id` bigint unsigned DEFAULT NULL COMMENT '수신자 회원 ID (회원인 경우)',
  `recipient_identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수신자 식별자 (채널별: 이메일, 디바이스토큰, user_id 등)',
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '수신자 표시명 (발송 시점 스냅샷)',
  `sender_user_id` bigint unsigned DEFAULT NULL COMMENT '발송자 회원 ID (null=시스템 자동)',
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '렌더링된 제목',
  `body` longtext COLLATE utf8mb4_unicode_ci COMMENT '렌더링된 본문',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent' COMMENT '상태: sent, failed, skipped',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '에러 메시지',
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발송 출처: notification, test_mail 등',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '발송 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_notification_logs_sender_user_id_foreign` (`sender_user_id`),
  KEY `idx_notification_logs_channel` (`channel`),
  KEY `idx_notification_logs_type` (`notification_type`),
  KEY `idx_notification_logs_status` (`status`),
  KEY `idx_notification_logs_sent_at` (`sent_at`),
  KEY `idx_notification_logs_recipient_user` (`recipient_user_id`),
  KEY `idx_notification_logs_recipient_id` (`recipient_identifier`),
  KEY `idx_notification_logs_extension` (`extension_type`,`extension_identifier`),
  KEY `idx_notification_logs_recipient_name` (`recipient_name`),
  KEY `idx_notification_logs_subject` (`subject`),
  KEY `idx_notification_logs_created_id` (`created_at`,`id`),
  CONSTRAINT `g7_notification_logs_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_notification_logs_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_notification_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `definition_id` bigint unsigned NOT NULL COMMENT '알림 정의 ID',
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '채널: mail, database, fcm',
  `subject` text COLLATE utf8mb4_unicode_ci COMMENT '다국어 제목 ({"ko": "...", "en": "..."})',
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 본문 ({"ko": "...", "en": "..."})',
  `click_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '알림 클릭 시 이동 URL 패턴 (변수 치환 가능, 예: /mypage/orders/{order_number})',
  `recipients` text COLLATE utf8mb4_unicode_ci COMMENT '수신자 규칙 JSON ([{type, value, relation, exclude_trigger_user}])',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '해당 채널 활성 여부',
  `is_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시더 생성 여부',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '사용자가 수정한 필드명 목록',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_notification_templates_definition_id_channel_unique` (`definition_id`,`channel`),
  KEY `g7_notification_templates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `g7_notification_templates_definition_id_foreign` FOREIGN KEY (`definition_id`) REFERENCES `g7_notification_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_notification_templates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID',
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '알림 클래스명',
  `notifiable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '알림 수신 모델 타입',
  `notifiable_id` bigint unsigned NOT NULL COMMENT '알림 수신 모델 ID',
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '알림 데이터 (JSON)',
  `read_at` timestamp NULL DEFAULT NULL COMMENT '읽음 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_page_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '첨부파일 ID',
  `page_id` bigint unsigned DEFAULT NULL COMMENT '페이지 ID (임시 업로드 시 null)',
  `temp_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '임시 업로드 키 (페이지 저장 전 파일 그룹화)',
  `hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL용 고유 해시 (12자)',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '원본 파일명',
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장된 파일명 (UUID 기반)',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'modules' COMMENT '스토리지 디스크 (modules, public, s3 등)',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '저장 경로 (디스크 기준 상대 경로)',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입 (예: image/jpeg, application/pdf)',
  `size` bigint unsigned NOT NULL COMMENT '파일 크기 (바이트)',
  `collection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'attachments' COMMENT '파일 컬렉션 (attachments)',
  `order` int unsigned NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `meta` text COLLATE utf8mb4_unicode_ci COMMENT '추가 메타 정보 (JSON)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '업로더 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_page_attachments_hash_unique` (`hash`),
  KEY `g7_page_attachments_page_id_index` (`page_id`),
  KEY `g7_page_attachments_temp_key_index` (`temp_key`),
  KEY `g7_page_attachments_page_id_order_index` (`page_id`,`order`),
  KEY `g7_page_attachments_created_by_foreign` (`created_by`),
  CONSTRAINT `g7_page_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_page_attachments_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `g7_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='페이지 첨부파일';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_page_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '버전 ID',
  `page_id` bigint unsigned NOT NULL COMMENT '페이지 ID',
  `version` int unsigned NOT NULL COMMENT '버전 번호',
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '제목 스냅샷 (다국어 JSON)',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '본문 스냅샷 (다국어 JSON)',
  `content_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'html' COMMENT '본문 형식 스냅샷 (html, text)',
  `seo_meta` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 메타 스냅샷 (title, description, keywords)',
  `changes_summary` text COLLATE utf8mb4_unicode_ci COMMENT '변경 요약 (변경 필드 목록, 복원 정보)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '작성자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_page_versions_page_id_version_index` (`page_id`,`version`),
  KEY `g7_page_versions_created_by_index` (`created_by`),
  CONSTRAINT `g7_page_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_page_versions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `g7_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='페이지 버전 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '페이지 ID',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL 슬러그 (고유)',
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '페이지 제목 (다국어 JSON)',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '페이지 본문 (다국어 JSON)',
  `content_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'html' COMMENT '본문 형식 (html, text)',
  `content_thumbnail_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '본문 첫 내부 이미지 URL 캐시 — 페이지 og:image 폴백',
  `published` tinyint(1) NOT NULL DEFAULT '0' COMMENT '발행 여부 (true: 발행, false: 미발행)',
  `published_at` timestamp NULL DEFAULT NULL COMMENT '발행 일시',
  `seo_meta` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO 메타 정보 (title, description, keywords)',
  `current_version` int unsigned NOT NULL DEFAULT '1' COMMENT '현재 버전 번호',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_pages_slug_unique` (`slug`),
  KEY `g7_pages_published_index` (`published`),
  KEY `g7_pages_created_by_index` (`created_by`),
  KEY `g7_pages_updated_by_foreign` (`updated_by`),
  KEY `idx_pages_published_created_id` (`published`,`created_at`,`id`),
  FULLTEXT KEY `ft_pages_title` (`title`) /*!50100 WITH PARSER `ngram` */ ,
  FULLTEXT KEY `ft_pages_content` (`content`) /*!50100 WITH PARSER `ngram` */ ,
  CONSTRAINT `g7_pages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='페이지 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '비밀번호 재설정 요청 이메일',
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '비밀번호 재설정 토큰',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '생성일시',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='비밀번호 재설정 토큰 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_permission_hooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` bigint unsigned NOT NULL,
  `hook_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '훅 이름 (예: core.attachment.download)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_permission_hooks_permission_id_hook_name_unique` (`permission_id`,`hook_name`),
  KEY `g7_permission_hooks_hook_name_index` (`hook_name`),
  CONSTRAINT `g7_permission_hooks_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `g7_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='권한-훅 매핑 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '상위 권한 ID (계층 구조)',
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '권한명 (예: users.create, menus.read)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '권한 이름 (다국어 JSON)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '권한 설명 (다국어 JSON)',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의)',
  `extension_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 식별자 (예: core, sirsoft-board, sirsoft-payment)',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '권한 타입',
  `resource_route_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '리소스 라우트 파라미터명 (예: user, menu, product)',
  `owner_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '소유자 식별 컬럼명 (예: id, created_by, user_id)',
  `order` int NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_permissions_identifier_unique` (`identifier`),
  KEY `g7_permissions_parent_id_index` (`parent_id`),
  KEY `g7_permissions_type_index` (`type`),
  KEY `idx_permissions_extension` (`extension_type`,`extension_identifier`),
  CONSTRAINT `g7_permissions_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `g7_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 권한 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '토큰 이름',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '액세스 토큰',
  `abilities` text COLLATE utf8mb4_unicode_ci COMMENT '토큰 권한',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '마지막 사용 시간',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '만료 시간',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_personal_access_tokens_token_unique` (`token`),
  KEY `g7_personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `g7_personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개인 액세스 토큰 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_plugins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '플러그인 고유 식별자 (vendor-plugin 형식)',
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '벤더/개발자명',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '플러그인 이름 (다국어 JSON)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '플러그인 설명 (다국어 JSON)',
  `github_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 저장소 URL',
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '추가 메타데이터',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '플러그인 버전',
  `latest_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마켓플레이스 최신 버전',
  `update_available` tinyint(1) NOT NULL DEFAULT '0' COMMENT '업데이트 가능 여부',
  `update_source` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '업데이트 출처 (github, pending, bundled)',
  `github_changelog_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 변경 내역 URL',
  `status` enum('active','inactive','installing','uninstalling','updating') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive' COMMENT '상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중)',
  `deactivated_reason` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비활성화 사유: manual(사용자 수동) | incompatible_core(코어 버전 호환성) | null(active)',
  `deactivated_at` timestamp NULL DEFAULT NULL COMMENT '비활성화 시점',
  `incompatible_required_version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'incompatible_core 시 요구된 g7_version 제약 (재호환 판정용)',
  `vendor_mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto' COMMENT 'Vendor 설치 모드 (auto|composer|bundled)',
  `hooks` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '훅 설정 정보',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '플러그인 생성자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_plugins_identifier_unique` (`identifier`),
  KEY `g7_plugins_created_by_foreign` (`created_by`),
  KEY `g7_plugins_vendor_index` (`vendor`),
  KEY `g7_plugins_status_index` (`status`),
  KEY `plugins_deactivated_reason_index` (`deactivated_reason`),
  CONSTRAINT `g7_plugins_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 플러그인 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_raonslab_ai_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT 'G7 사용자 내부 식별자',
  `user_uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '감사 및 외부 identity mapping용 UUID',
  `request_id` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI_GCS V2 canonical request ID',
  `project_id` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AgentOpt V2 프로젝트 ID',
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `state` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACCEPTED',
  `title` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_observed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_raonslab_ai_requests_request_id_unique` (`request_id`),
  KEY `raonslab_ai_requests_user_created_index` (`user_id`,`created_at`),
  CONSTRAINT `g7_raonslab_ai_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='G7 사용자와 AI_GCS V2 canonical request의 소유권 매핑';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_raonslab_product_consultation_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 이력 식별자',
  `consultation_id` bigint unsigned NOT NULL COMMENT '상담 내부 식별자',
  `event_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '이력 유형 (CREATED, NOTE_ADDED, STATUS_CHANGED)',
  `from_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '변경 전 처리 상태',
  `to_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '변경 후 처리 상태',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 관리자 내부 메모',
  `close_outcome` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 종결 결과 스냅샷',
  `actor_user_id` bigint unsigned DEFAULT NULL COMMENT '변경 관리자 사용자 식별자',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `raon_consultation_history_actor_fk` (`actor_user_id`),
  KEY `raonslab_consultation_history_created_idx` (`consultation_id`,`created_at`),
  CONSTRAINT `raon_consultation_history_actor_fk` FOREIGN KEY (`actor_user_id`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `raon_consultation_history_consultation_fk` FOREIGN KEY (`consultation_id`) REFERENCES `g7_raonslab_product_consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_raonslab_product_consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 내부 식별자',
  `reference` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '외부 노출용 불투명 접수 번호',
  `idempotency_key_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '멱등성 키 SHA-256 해시',
  `payload_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '정규화 요청 HMAC-SHA-256',
  `contact_name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '암호화된 담당자 이름',
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '암호화된 이메일',
  `company` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 회사명',
  `phone` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 전화번호',
  `service_interest` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 관심 서비스',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '암호화된 상담 내용',
  `privacy_consent_version` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '동의한 개인정보 처리 문안 버전',
  `privacy_consented_at` timestamp NOT NULL COMMENT '개인정보 처리 동의 시각',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NEW' COMMENT '처리 상태 (NEW, CONTACTED, QUALIFIED, CLOSED)',
  `close_outcome` text COLLATE utf8mb4_unicode_ci COMMENT '암호화된 종결 결과',
  `mail_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_CONFIGURED' COMMENT '메일 상태 (NOT_CONFIGURED, PENDING, SENT, FAILED)',
  `mail_attempted_at` timestamp NULL DEFAULT NULL COMMENT '메일 발송 시도 시각',
  `mail_sent_at` timestamp NULL DEFAULT NULL COMMENT '메일 발송 완료 시각',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_raonslab_product_consultations_reference_unique` (`reference`),
  UNIQUE KEY `g7_raonslab_product_consultations_idempotency_key_hash_unique` (`idempotency_key_hash`),
  KEY `raonslab_consultations_status_created_idx` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_role_menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `menu_id` bigint unsigned NOT NULL,
  `permission_type` enum('read','write','delete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'read' COMMENT '권한 타입 (read: 읽기/접근, write: 수정, delete: 삭제)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu_permission` (`role_id`,`menu_id`,`permission_type`),
  KEY `g7_role_menus_menu_id_foreign` (`menu_id`),
  CONSTRAINT `g7_role_menus_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `g7_menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_role_menus_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `g7_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='역할별 메뉴 접근 권한 (피벗 테이블)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `scope_type` enum('self','role') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '접근 스코프 (null: 전체, self: 본인, role: 소유역할)',
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '권한 부여 일시',
  `granted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `g7_role_permissions_permission_id_foreign` (`permission_id`),
  KEY `g7_role_permissions_granted_by_foreign` (`granted_by`),
  CONSTRAINT `g7_role_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `g7_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `g7_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='역할-권한 관계 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '역할명 (예: admin, user, manager)',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '역할 이름 (다국어 JSON)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '역할 설명 (다국어 JSON)',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의)',
  `extension_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 식별자 (예: core, sirsoft-board, sirsoft-payment)',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["name", "permissions"])',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 상태 (1: 활성, 0: 비활성)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_roles_identifier_unique` (`identifier`),
  KEY `idx_roles_extension` (`extension_type`,`extension_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 역할 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_schedule_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '이력 ID',
  `schedule_id` bigint unsigned NOT NULL,
  `started_at` timestamp NOT NULL COMMENT '실행 시작 시간',
  `ended_at` timestamp NULL DEFAULT NULL COMMENT '실행 종료 시간',
  `duration` int unsigned DEFAULT NULL COMMENT '실행 시간 (초)',
  `status` enum('success','failed','running') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running' COMMENT '실행 상태: success(성공), failed(실패), running(실행중)',
  `exit_code` tinyint DEFAULT NULL COMMENT '종료 코드 (0: 성공)',
  `memory_usage` int unsigned DEFAULT NULL COMMENT '메모리 사용량 (bytes)',
  `output` longtext COLLATE utf8mb4_unicode_ci COMMENT '표준 출력',
  `error_output` longtext COLLATE utf8mb4_unicode_ci COMMENT '에러 출력',
  `trigger_type` enum('scheduled','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled' COMMENT '트리거 유형: scheduled(예약 실행), manual(수동 실행)',
  `triggered_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_schedule_histories_schedule_id_foreign` (`schedule_id`),
  KEY `g7_schedule_histories_triggered_by_foreign` (`triggered_by`),
  KEY `idx_started_at` (`started_at`),
  KEY `idx_status` (`status`),
  KEY `idx_trigger_type` (`trigger_type`),
  CONSTRAINT `g7_schedule_histories_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `g7_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_schedule_histories_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='스케줄 실행 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '스케줄 ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '작업명',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '설명',
  `type` enum('artisan','shell','url') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '작업 유형: artisan(Artisan 커맨드), shell(쉘 명령), url(URL 호출)',
  `command` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '명령어 또는 URL',
  `expression` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Cron 표현식',
  `frequency` enum('everyMinute','hourly','daily','weekly','monthly','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT '실행 주기: everyMinute(매분), hourly(매시간), daily(매일), weekly(매주), monthly(매월), custom(사용자 정의)',
  `without_overlapping` tinyint(1) NOT NULL DEFAULT '0' COMMENT '중복 실행 방지 여부: 0(허용), 1(방지)',
  `run_in_maintenance` tinyint(1) NOT NULL DEFAULT '0' COMMENT '점검 모드 실행 여부: 0(비실행), 1(실행)',
  `timeout` int unsigned DEFAULT NULL COMMENT '실행 제한 시간 (초)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성화 여부: 0(비활성), 1(활성)',
  `user_overrides` text COLLATE utf8mb4_unicode_ci COMMENT '유저가 수정한 필드명 목록 (예: ["expression", "command", "timeout"])',
  `last_result` enum('success','failed','running','never') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'never' COMMENT '마지막 실행 결과: success(성공), failed(실패), running(실행중), never(미실행)',
  `last_run_at` timestamp NULL DEFAULT NULL COMMENT '마지막 실행 시간',
  `next_run_at` timestamp NULL DEFAULT NULL COMMENT '다음 실행 예정 시간',
  `extension_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 소유 타입: core(코어), module(모듈), plugin(플러그인), NULL(사용자 정의)',
  `extension_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '확장 식별자 (예: core, sirsoft-board, sirsoft-payment)',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_schedules_created_by_foreign` (`created_by`),
  KEY `idx_type` (`type`),
  KEY `idx_frequency` (`frequency`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_result` (`last_result`),
  KEY `idx_next_run_at` (`next_run_at`),
  KEY `idx_schedules_extension` (`extension_type`,`extension_identifier`),
  KEY `idx_schedules_created_at` (`created_at`),
  CONSTRAINT `g7_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='스케줄 작업 관리';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_seo_cache_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `url` varchar(768) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '요청 URL (경로 + 정규화 쿼리)',
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '로케일',
  `layout_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '사용된 레이아웃명',
  `module_identifier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '레이아웃 소유 모듈 식별자',
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '유형 (hit 또는 miss)',
  `response_time_ms` int DEFAULT NULL COMMENT '렌더링 소요 시간 (ms)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시각',
  PRIMARY KEY (`id`),
  KEY `idx_seo_cache_stats_url` (`url`),
  KEY `idx_seo_cache_stats_type` (`type`),
  KEY `idx_seo_cache_stats_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '세션 ID',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '사용자 ID',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT '사용자 에이전트',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '세션 데이터',
  `last_activity` int NOT NULL COMMENT '마지막 활동 시간',
  PRIMARY KEY (`id`),
  KEY `g7_sessions_user_id_index` (`user_id`),
  KEY `g7_sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 세션 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_sitemap_urls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '사이트맵 URL 행 ID',
  `resource_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '리소스 유형 (page, board_post, product, category, board, board_index, shop_index 등)',
  `resource_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '리소스 PK (uuid/int 문자열). 인덱스/컬렉션 URL 은 null',
  `loc` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '절대 base URL (로케일 무관 — 렌더 시점에 로케일별 확장)',
  `loc_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL COMMENT 'loc 의 sha256 해시 (upsert/delete identity)',
  `lastmod` timestamp NULL DEFAULT NULL COMMENT '최종 수정 시각 (<lastmod>)',
  `changefreq` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '변경 빈도 (<changefreq>)',
  `priority` decimal(2,1) DEFAULT NULL COMMENT '우선순위 0.0~1.0 (<priority>)',
  `contributor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '기여자 식별자 (getIdentifier — 샤드/리빌드 스코핑)',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT '공개 여부 (true 만 사이트맵에 노출)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sitemap_urls_identity_unique` (`resource_type`,`resource_id`,`loc_hash`),
  KEY `sitemap_urls_stream_index` (`contributor`,`is_visible`,`id`),
  KEY `g7_sitemap_urls_resource_type_index` (`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_custom_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '커스텀 다국어 키 ID',
  `template_id` bigint unsigned NOT NULL COMMENT '소속 템플릿 ID',
  `layout_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '생성 출처 레이아웃 이름',
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '다국어 키 ($t: 참조 경로)',
  `values` json NOT NULL COMMENT '로케일별 번역 값',
  `user_overrides` json DEFAULT NULL COMMENT '사용자 수정 보존 추적',
  `status` enum('active','orphaned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '상태 (active: 활성, orphaned: 고아)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자',
  `lock_version` bigint unsigned NOT NULL DEFAULT '0' COMMENT '낙관적 잠금 버전',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_custom_translations_template_key_unique` (`template_id`,`translation_key`),
  KEY `g7_template_custom_translations_created_by_foreign` (`created_by`),
  KEY `g7_template_custom_translations_updated_by_foreign` (`updated_by`),
  KEY `template_custom_translations_template_layout_index` (`template_id`,`layout_name`),
  KEY `template_custom_translations_status_index` (`status`),
  CONSTRAINT `g7_template_custom_translations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_template_custom_translations_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `g7_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_template_custom_translations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layout_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '첨부 파일 ID',
  `template_id` bigint unsigned NOT NULL COMMENT '소속 템플릿 ID',
  `layout_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '사용 출처 레이아웃 이름',
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '스토리지 디스크 이름',
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '스토리지 내 파일 경로',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '업로드 원본 파일명',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MIME 타입',
  `size` bigint unsigned NOT NULL COMMENT '파일 크기(바이트)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '업로더 사용자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g7_template_layout_attachments_created_by_foreign` (`created_by`),
  KEY `index_template_layout` (`template_id`,`layout_name`),
  KEY `index_disk_path` (`disk`,`path`),
  CONSTRAINT `g7_template_layout_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_template_layout_attachments_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `g7_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layout_extension_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '버전 ID',
  `extension_id` bigint unsigned NOT NULL COMMENT '레이아웃 확장 ID',
  `version` int unsigned NOT NULL COMMENT '버전 번호 (자동 증가)',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 정의 JSON 스냅샷',
  `changes_summary` text COLLATE utf8mb4_unicode_ci COMMENT '변경 요약 JSON: {"added": 3, "removed": 2, "is_restored": false, "restored_from": null}',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '저장자 ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '생성 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ext_layout_version` (`extension_id`,`version`),
  KEY `idx_ext_layout_versions` (`extension_id`,`created_at`),
  CONSTRAINT `g7_template_layout_extension_versions_extension_id_foreign` FOREIGN KEY (`extension_id`) REFERENCES `g7_template_layout_extensions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='템플릿 레이아웃 확장 버전 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layout_extensions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '확장 ID',
  `template_id` bigint unsigned NOT NULL COMMENT '템플릿 ID (g7_templates 참조)',
  `extension_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 타입: extension_point=확장점 방식, overlay=ID 기반 오버레이 방식',
  `target_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '타겟 이름 (extension_point: 확장점명, overlay: 레이아웃명)',
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '출처 타입: template=템플릿(오버라이드용), module=모듈, plugin=플러그인',
  `source_identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '출처 식별자 (예: sirsoft-ecommerce). 템플릿 오버라이드의 경우 오버라이드 대상 모듈/플러그인 식별자',
  `override_target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '오버라이드 대상 (source_type=template일 때만 사용). 모듈/플러그인 식별자',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '확장 정의 JSON (components, injections, data_sources 포함)',
  `original_content_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '원본 확장 콘텐츠 SHA-256 해시 (수정 감지용)',
  `original_content_size` int unsigned DEFAULT NULL COMMENT '원본 확장 콘텐츠 바이트 크기 (변화량 표시용)',
  `priority` int NOT NULL DEFAULT '100' COMMENT '우선순위 (낮을수록 먼저 적용, 기본값: 100)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '활성 상태 (true=활성, false=비활성)',
  `lock_version` bigint unsigned NOT NULL DEFAULT '0' COMMENT '낙관적 잠금 버전',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '삭제 일시 (soft delete)',
  PRIMARY KEY (`id`),
  KEY `idx_target` (`template_id`,`extension_type`,`target_name`,`is_active`),
  KEY `idx_source` (`source_type`,`source_identifier`),
  KEY `idx_override` (`override_target`,`template_id`,`extension_type`,`target_name`),
  CONSTRAINT `g7_template_layout_extensions_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `g7_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='템플릿 레이아웃 확장 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layout_previews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '미리보기 URL 토큰 (UUID)',
  `template_id` bigint unsigned NOT NULL COMMENT '템플릿 ID',
  `layout_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '미리보기 대상 레이아웃 이름',
  `preview_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'layout' COMMENT '미리보기 종류 (layout: 일반 레이아웃, extension: 레이아웃 확장)',
  `extension_id` bigint unsigned DEFAULT NULL COMMENT '레이아웃 확장 ID (preview_type=extension 일 때)',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '편집 중인 레이아웃 JSON',
  `admin_id` bigint unsigned NOT NULL COMMENT '미리보기 생성 관리자 ID',
  `expires_at` timestamp NOT NULL COMMENT '만료 시각',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시각',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_template_layout_previews_token_unique` (`token`),
  KEY `idx_layout_previews_expires_at` (`expires_at`),
  KEY `idx_layout_previews_template_layout_admin` (`template_id`,`layout_name`,`admin_id`),
  CONSTRAINT `g7_template_layout_previews_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `g7_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layout_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '버전 ID',
  `layout_id` bigint unsigned NOT NULL COMMENT '레이아웃 ID',
  `version` int unsigned NOT NULL COMMENT '버전 번호 (자동 증가)',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '레이아웃 JSON 스냅샷',
  `changes_summary` text COLLATE utf8mb4_unicode_ci COMMENT '변경 요약 JSON: {"added": 3, "removed": 2, "is_restored": false, "restored_from": null}',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '저장자 ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '생성 일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_layout_version` (`layout_id`,`version`),
  KEY `idx_layout_versions` (`layout_id`,`created_at`),
  CONSTRAINT `g7_template_layout_versions_layout_id_foreign` FOREIGN KEY (`layout_id`) REFERENCES `g7_template_layouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='템플릿 레이아웃 버전 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_template_layouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '레이아웃 ID',
  `template_id` bigint unsigned NOT NULL COMMENT '템플릿 ID',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '레이아웃 이름 (예: dashboard, users, user_edit)',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '레이아웃 JSON 내용',
  `original_content_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '원본 레이아웃 콘텐츠 SHA-256 해시 (수정 감지용)',
  `original_content_size` int unsigned DEFAULT NULL COMMENT '원본 레이아웃 콘텐츠 바이트 크기 (변화량 표시용)',
  `extends` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '부모 레이아웃 이름 (예: layouts/_admin_base)',
  `source_type` enum('template','module','plugin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template' COMMENT '레이아웃 소스 타입 (template: 템플릿 자체/오버라이드, module: 모듈 기본, plugin: 플러그인 기본)',
  `source_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '모듈/플러그인 식별자 (source_type이 module/plugin일 때 사용)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `lock_version` bigint unsigned NOT NULL DEFAULT '0' COMMENT '낙관적 잠금 버전',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_layout_source` (`template_id`,`name`,`source_type`),
  KEY `idx_source` (`source_type`,`source_identifier`),
  KEY `g7_template_layouts_deleted_at_index` (`deleted_at`),
  KEY `g7_template_layouts_extends_index` (`extends`),
  KEY `idx_template_layouts_template_id` (`template_id`),
  CONSTRAINT `g7_template_layouts_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `g7_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='템플릿 레이아웃 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '템플릿 ID',
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '템플릿 고유 식별자 (vendor-name 형식, 예: sirsoft-admin_basic)',
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '벤더/개발자명 (예: sirsoft)',
  `name` text COLLATE utf8mb4_unicode_ci COMMENT '템플릿 이름 (다국어 JSON)',
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '템플릿 버전 (예: 1.0.0)',
  `latest_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마켓플레이스 최신 버전',
  `update_available` tinyint(1) NOT NULL DEFAULT '0' COMMENT '업데이트 가능 여부',
  `update_source` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '업데이트 출처 (github, pending, bundled)',
  `type` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '템플릿 타입 (admin: 관리자용, user: 사용자용)',
  `status` enum('active','inactive','installing','uninstalling','updating') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive' COMMENT '상태 (active: 활성화, inactive: 비활성화, installing: 설치 중, uninstalling: 제거 중, updating: 업데이트 중)',
  `deactivated_reason` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '비활성화 사유: manual(사용자 수동) | incompatible_core(코어 버전 호환성) | null(active)',
  `deactivated_at` timestamp NULL DEFAULT NULL COMMENT '비활성화 시점',
  `incompatible_required_version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'incompatible_core 시 요구된 g7_version 제약 (재호환 판정용)',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '템플릿 설명 (다국어 JSON)',
  `user_modified_at` timestamp NULL DEFAULT NULL COMMENT '사용자가 레이아웃을 마지막으로 수정한 시각',
  `github_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 저장소 URL',
  `github_changelog_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GitHub 변경 내역 URL',
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '추가 메타데이터 (JSON 형식)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_templates_identifier_unique` (`identifier`),
  KEY `g7_templates_type_index` (`type`),
  KEY `g7_templates_status_index` (`status`),
  KEY `g7_templates_vendor_index` (`vendor`),
  KEY `templates_deactivated_reason_index` (`deactivated_reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 템플릿 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_user_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '동의 이력 ID',
  `user_id` bigint unsigned NOT NULL COMMENT '사용자 ID',
  `consent_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '동의 유형: terms, privacy',
  `agreed_at` timestamp NOT NULL COMMENT '동의 일시',
  `revoked_at` timestamp NULL DEFAULT NULL COMMENT '철회 일시 (향후 플러그인 확장용)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '동의 시 IP 주소 (IPv6 대응)',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '생성 일시',
  PRIMARY KEY (`id`),
  KEY `user_consents_user_type_index` (`user_id`,`consent_type`),
  CONSTRAINT `g7_user_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 약관 동의 이력';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '역할 할당 일시',
  `assigned_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `g7_user_roles_role_id_foreign` (`role_id`),
  KEY `g7_user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `g7_user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `g7_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `g7_user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `g7_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `g7_user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `g7_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자-역할 관계 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `g7_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '외부 노출용 UUID v7',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '사용자 이름',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '닉네임',
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '이메일 주소',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT '이메일 인증 일시',
  `identity_verified_at` timestamp NULL DEFAULT NULL COMMENT '최종 성공 본인인증 시각',
  `identity_verified_provider` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '최근 본인인증에 사용한 프로바이더 식별자',
  `identity_verified_purpose_last` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '최근 본인인증 목적 (signup|password_reset|self_update|sensitive_action|...)',
  `identity_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '프로바이더 교체 시 동일인 매칭용 정규화 식별자 (SHA256)',
  `mobile_verified_at` timestamp NULL DEFAULT NULL COMMENT '휴대전화 인증 시각 (email_verified_at 과 대칭)',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '비밀번호 해시',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ko' COMMENT '사용자 언어 설정 (ko: 한국어, en: 영어)',
  `is_super` tinyint(1) NOT NULL DEFAULT '0' COMMENT '슈퍼 관리자 여부 (삭제 불가, 권한 관리 가능)',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Asia/Seoul' COMMENT '사용자 시간대 (예: Asia/Seoul, UTC)',
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '국가 코드 (ISO 3166-1 alpha-2)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '계정 상태 (active: 활성, inactive: 비활성, blocked: 차단, withdrawn: 탈퇴)',
  `homepage` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '홈페이지 URL',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '휴대폰 번호',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '전화번호',
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '우편번호',
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '기본 주소',
  `address_detail` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '상세 주소',
  `signature` text COLLATE utf8mb4_unicode_ci COMMENT '서명',
  `bio` text COLLATE utf8mb4_unicode_ci COMMENT '자기소개',
  `admin_memo` text COLLATE utf8mb4_unicode_ci COMMENT '관리자 메모',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '마지막 접속 IP 주소',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '마지막 로그인 일시',
  `withdrawn_at` timestamp NULL DEFAULT NULL COMMENT '탈퇴 일시',
  `blocked_at` timestamp NULL DEFAULT NULL COMMENT '차단 일시',
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '연속 로그인 실패 횟수',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT '계정 잠금 해제 시각 (NULL = 잠금 없음)',
  `locked_permanently` tinyint(1) NOT NULL DEFAULT '0' COMMENT '영구 잠금 여부 (잠금 시간 0 = 무한대 설정으로 잠긴 계정)',
  `last_failed_login_at` timestamp NULL DEFAULT NULL COMMENT '마지막 로그인 실패 시각',
  PRIMARY KEY (`id`),
  UNIQUE KEY `g7_users_email_unique` (`email`),
  UNIQUE KEY `g7_users_uuid_unique` (`uuid`),
  KEY `g7_users_is_super_index` (`is_super`),
  KEY `g7_users_country_index` (`country`),
  KEY `g7_users_status_index` (`status`),
  KEY `g7_users_mobile_index` (`mobile`),
  KEY `g7_users_phone_index` (`phone`),
  KEY `g7_users_ip_address_index` (`ip_address`),
  KEY `idx_users_identity_hash` (`identity_hash`),
  KEY `idx_users_created_id` (`created_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 사용자 정보';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
