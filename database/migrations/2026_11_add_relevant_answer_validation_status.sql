ALTER TABLE qa_validation_results
    MODIFY result_code ENUM(
        'RELEVANT',
        'RELEVANT_AND_SUPPORTED',
        'IRRELEVANT',
        'POTENTIALLY_INCORRECT',
        'UNSUPPORTED',
        'NEEDS_CLARIFICATION',
        'REVIEW_REQUIRED'
    ) NOT NULL;
