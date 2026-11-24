CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categories (name)
SELECT DISTINCT TRIM(category)
FROM questions
WHERE category IS NOT NULL
  AND TRIM(category) <> '';

INSERT INTO categories (name)
SELECT 'General'
WHERE NOT EXISTS (SELECT 1 FROM categories);

INSERT IGNORE INTO categories (name)
VALUES
    ('Academics'),
    ('Admissions'),
    ('Placements'),
    ('College Life');
