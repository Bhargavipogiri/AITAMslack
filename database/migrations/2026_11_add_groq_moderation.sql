ALTER TABLE questions
    ADD COLUMN publication_status
        ENUM('PENDING', 'APPROVED', 'REJECTED')
        NOT NULL DEFAULT 'APPROVED'
        AFTER status;

ALTER TABLE answers
    ADD COLUMN publication_status
        ENUM('PENDING', 'APPROVED', 'REJECTED')
        NOT NULL DEFAULT 'APPROVED'
        AFTER answer_text;

CREATE TABLE moderation_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    content_type ENUM('question', 'answer') NOT NULL,
    content_id INT NOT NULL,
    event_type ENUM('AI', 'ADMIN') NOT NULL,
    ai_decision ENUM('APPROVED', 'REJECTED', 'REVIEW', 'FAILED') DEFAULT NULL,
    publication_status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL,
    ai_reason TEXT DEFAULT NULL,
    ai_confidence DECIMAL(5,4) DEFAULT NULL,
    relevance_score DECIMAL(5,4) DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    detected_violations LONGTEXT DEFAULT NULL,
    model VARCHAR(150) DEFAULT NULL,
    api_status VARCHAR(40) NOT NULL,
    admin_reviewer_id INT DEFAULT NULL,
    admin_decision ENUM('APPROVED', 'REJECTED') DEFAULT NULL,
    admin_reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_moderation_content (content_type, content_id, id),
    KEY idx_moderation_queue (event_type, ai_decision, publication_status, created_at),
    KEY idx_moderation_reviewer (admin_reviewer_id, admin_reviewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
