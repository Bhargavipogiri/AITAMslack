
<?php

require_once __DIR__ . '/includes/ai_helpers.php';

$key = groqEnvValue('GROQ_API_KEY');

echo 'ENV FILE: ' .
    (is_file(__DIR__ . '/.env') ? 'FOUND' : 'NOT FOUND');

echo '<br>';

echo 'API KEY: ' .
    ($key !== null ? 'DETECTED' : 'NOT DETECTED');

echo '<br>';

echo 'KEY FORMAT: ' .
    ($key !== null ? 'VALID FORMAT' : 'NOT AVAILABLE');
