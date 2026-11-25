CREATE TABLE verified_college_facts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category ENUM('Admissions', 'Academics', 'Placements', 'Campus Life') NOT NULL,
    fact_text TEXT NOT NULL,
    source_reference VARCHAR(500) NOT NULL,
    status ENUM('APPROVED', 'RETIRED') NOT NULL DEFAULT 'APPROVED',
    created_by INT NOT NULL,
    verified_by INT NOT NULL,
    verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verified_facts_category_status (category, status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qa_validation_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    content_type ENUM('question', 'answer') NOT NULL,
    content_id INT NOT NULL,
    result_code ENUM(
        'RELEVANT',
        'RELEVANT_AND_SUPPORTED',
        'IRRELEVANT',
        'POTENTIALLY_INCORRECT',
        'UNSUPPORTED',
        'NEEDS_CLARIFICATION',
        'REVIEW_REQUIRED'
    ) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    category VARCHAR(32) NOT NULL,
    model VARCHAR(100) DEFAULT NULL,
    api_status VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_qa_validation_content (content_type, content_id, id),
    KEY idx_qa_validation_queue (result_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
