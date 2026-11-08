CREATE DATABASE IF NOT EXISTS 4753450_agriquiz;
USE 4753450_agriquiz;

/* ===== USERS TABLE ===== */
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL DEFAULT NULL AFTER role;


/* ===== REVIEWERS TABLE ===== */
CREATE TABLE IF NOT EXISTS reviewers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    file_path   VARCHAR(500) NULL,
    file_name   VARCHAR(255) NULL,
    field       VARCHAR(255) NULL,
    content     LONGTEXT     NULL,
    expires_at  DATETIME NULL DEFAULT NULL COMMENT 'NULL = never expires. If set, students cannot save scores after this datetime.',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== QUIZZES TABLE ===== */
CREATE TABLE IF NOT EXISTS quizzes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    field      VARCHAR(255) NULL,
    questions  JSON         NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== PREBOARD MANUAL RECORDS TABLE ===== */
CREATE TABLE IF NOT EXISTS preboard_manual_records (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT            NOT NULL,
    subject      VARCHAR(100)   NOT NULL,
    score        DECIMAL(5,2)   NOT NULL,
    total_items  INT            NOT NULL DEFAULT 100,
    recorded_at  DATE           NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== QUIZ QUESTIONS TABLE ===== */
CREATE TABLE IF NOT EXISTS quiz_questions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id       INT  NOT NULL,
    question_text TEXT NOT NULL,
    options_json  LONGTEXT NOT NULL,
    correct_index INT  NOT NULL DEFAULT 0,
    explanation   TEXT,
    sort_order    INT  DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== QUIZ ATTEMPTS TABLE ===== */
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(255) NOT NULL,
    quiz_id       INT          NOT NULL,
    field         VARCHAR(255) NOT NULL,
    quiz_title    VARCHAR(255),
    score         INT          NOT NULL,
    total         INT          NOT NULL,
    pct           TINYINT      DEFAULT 0,
    answers_json  JSON,
    is_expired    TINYINT(1)   DEFAULT 0 COMMENT '1 = score saved but excluded from analytics, 0 = score included in analytics',
    taken_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_student_email  (student_email),
    INDEX idx_quiz_id        (quiz_id),
    INDEX idx_field          (field),
    INDEX idx_taken_at       (taken_at),
    INDEX idx_is_expired     (is_expired),
    INDEX idx_student_taken  (student_email, taken_at),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== STUDENT PERFORMANCE TABLE ===== */
CREATE TABLE IF NOT EXISTS student_performance (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    student_email     VARCHAR(255)   NOT NULL UNIQUE,
    total_attempts    INT            DEFAULT 0,
    overall_avg       DECIMAL(5,2),
    best_score        DECIMAL(5,2),
    fields_attempted  INT            DEFAULT 0,
    last_attempt_date TIMESTAMP      NULL,
    last_updated      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== FIELD PERFORMANCE TABLE ===== */
CREATE TABLE IF NOT EXISTS field_performance (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_email   VARCHAR(255)  NOT NULL,
    field           VARCHAR(255)  NOT NULL,
    attempts        INT           DEFAULT 0,
    avg_pct         DECIMAL(5,2),
    best_pct        DECIMAL(5,2),
    total_correct   INT           DEFAULT 0,
    total_questions INT           DEFAULT 0,
    last_taken      TIMESTAMP     NULL,
    last_updated    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_student_field (student_email, field),
    INDEX idx_student  (student_email),
    INDEX idx_last_taken (last_taken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

