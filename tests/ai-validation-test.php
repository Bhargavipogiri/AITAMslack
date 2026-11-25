<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ai_validation.php';
require_once __DIR__ . '/../includes/ai_helpers.php';

$checks = 0;

$expect = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
};

foreach (collegeValidationTopics() as $topic) {
    $expect(collegeValidationTopic($topic) === $topic, 'Allowed topic was not retained.');

    $topicClassification = collegeValidationClassification(
        json_encode([
            'result' => 'RELEVANT_AND_SUPPORTED',
            'category' => $topic,
        ]),
        'question',
        $topic,
        'grok-4.7'
    );
    $expect(
        $topicClassification['result'] === 'RELEVANT_AND_SUPPORTED',
        'A valid allowed-topic classification was not accepted.'
    );
}

$expect(
    collegeValidationTopic('College Life') === 'Campus Life',
    'Legacy College Life category was not mapped to Campus Life.'
);
$expect(
    collegeValidationTopic('General') === null,
    'Non-allowed category was accepted.'
);
$expect(
    !collegeValidationRateLimitAllowed(0, 'question'),
    'A zero user id was allowed through the rate limiter.'
);
$expect(
    collegeValidationHttpFailureStatus(401) === 'invalid_api_key',
    'Invalid xAI API credentials were not mapped to a safe status.'
);
$expect(
    collegeValidationHttpFailureStatus(429) === 'rate_limited',
    'xAI rate limits were not mapped to a safe status.'
);
$expect(
    collegeValidationHttpFailureStatus(503) === 'provider_unavailable',
    'Provider errors were not mapped to a safe status.'
);
foreach ([
    'IRRELEVANT',
    'NEEDS_CLARIFICATION',
    'POTENTIALLY_INCORRECT',
    'UNSUPPORTED',
    'REVIEW_REQUIRED',
] as $blockedQuestionResult) {
    $expect(
        collegeQuestionValidationBlocksSubmission([
            'result' => $blockedQuestionResult,
        ]),
        "Question result {$blockedQuestionResult} was not blocked before submission."
    );
}
$expect(
    !collegeQuestionValidationBlocksSubmission([
        'result' => 'RELEVANT_AND_SUPPORTED',
    ]),
    'A relevant question was incorrectly blocked.'
);
$questionFallbackModeration = [
    'ok' => true,
    'moderation' => [
        'decision' => 'APPROVED',
        'category' => 'Admissions',
        'is_college_related' => true,
        'is_relevant_to_selected_category' => true,
    ],
];
$expect(
    collegeQuestionModerationMatchesCategory(
        $questionFallbackModeration,
        'Admissions'
    ),
    'A question matching its selected category was not recognized.'
);
$expect(
    !collegeQuestionModerationMatchesCategory(
        [
            'moderation' => array_merge(
                $questionFallbackModeration['moderation'],
                ['is_relevant_to_selected_category' => false]
            ),
        ],
        'Admissions'
    ),
    'Broad college relevance was incorrectly accepted as selected-category relevance.'
);
$expect(
    !collegeQuestionCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'missing_api_key'],
        [
            'ok' => true,
            'moderation' => array_merge(
                $questionFallbackModeration['moderation'],
                ['is_relevant_to_selected_category' => false]
            ),
        ],
        'Admissions'
    ),
    'A question rejected by the selected-category classifier used the validator fallback.'
);
$expect(
    collegeQuestionCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'missing_api_key'],
        $questionFallbackModeration,
        'Admissions'
    ),
    'A matching, approved Groq category was not accepted as an xAI outage fallback.'
);
$expect(
    !collegeQuestionCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'missing_api_key'],
        $questionFallbackModeration,
        'Academics'
    ),
    'A question classified under a different category used the outage fallback.'
);
$expect(
    !collegeQuestionCanUseRelevanceFallback(
        ['result' => 'IRRELEVANT', 'api_status' => 'ok'],
        $questionFallbackModeration,
        'Admissions'
    ),
    'A model-classified irrelevant question used the outage fallback.'
);

$questionModerationResponse = [
    'content_type' => 'question',
    'decision' => 'APPROVED',
    'is_college_related' => true,
    'is_relevant_to_selected_category' => true,
    'is_vulgar' => false,
    'is_abusive' => false,
    'is_spam' => false,
    'is_meaningful' => true,
    'is_duplicate' => false,
    'category' => 'Campus Life',
    'confidence' => 0.9,
    'reason' => 'It asks about campus clubs.',
    'violations' => [],
];
$validQuestionModeration = validateModerationResponse(
    $questionModerationResponse,
    'question'
);
$expect(
    !empty($validQuestionModeration['ok']) &&
        $validQuestionModeration['moderation']['decision'] === 'APPROVED',
    'A question matching its selected category was not approved by moderation.'
);
$privateRelationshipModeration = validateModerationResponse(
    array_merge(
        $questionModerationResponse,
        ['is_relevant_to_selected_category' => false]
    ),
    'question'
);
$expect(
    !empty($privateRelationshipModeration['ok']) &&
        $privateRelationshipModeration['moderation']['decision'] === 'REVIEW',
    'Broad college relevance incorrectly approved content unrelated to the selected topic.'
);

$validClassification = collegeValidationClassification(
    '{"result":"RELEVANT_AND_SUPPORTED","category":"Academics"}',
    'question',
    'Academics',
    'grok-4.7'
);
$expect(
    $validClassification['result'] === 'RELEVANT_AND_SUPPORTED' &&
        $validClassification['category'] === 'Academics',
    'Valid classification did not retain only the expected result and topic.'
);

$mismatchedTopic = collegeValidationClassification(
    '{"result":"RELEVANT_AND_SUPPORTED","category":"Admissions"}',
    'question',
    'Academics',
    'grok-4.7'
);
$expect(
    $mismatchedTopic['result'] === 'REVIEW_REQUIRED',
    'A positive result with a different category was not failed closed.'
);

$answerText = collegeValidationClassification(
    '{"result":"RELEVANT","category":"Placements"}',
    'answer',
    'Placements',
    'grok-4.7'
);
$expect(
    $answerText['result'] === 'RELEVANT' &&
        !array_key_exists('answer', $answerText),
    'Relevant answer classification was not retained without answer content.'
);

$extraOutput = collegeValidationClassification(
    '{"result":"RELEVANT_AND_SUPPORTED","category":"Academics","answer":"generated"}',
    'answer',
    'Academics',
    'grok-4.7'
);
$expect(
    $extraOutput['result'] === 'REVIEW_REQUIRED',
    'Unexpected model output fields were accepted.'
);

$answerModeration = [
    'ok' => true,
    'moderation' => [
        'is_vulgar' => false,
        'is_abusive' => false,
        'is_spam' => false,
        'is_safe' => true,
        'is_relevant' => true,
        'relevance_score' => 0.95,
        'potentially_misleading' => false,
        'is_duplicate' => false,
        'violations' => [],
    ],
];
$expect(
    collegeAnswerCanPublish($answerText, $answerModeration),
    'A safe answer classified as relevant was not publishable.'
);
$expect(
    !collegeAnswerCanPublish(
        collegeValidationClassification(
            '{"result":"IRRELEVANT","category":"Placements"}',
            'answer',
            'Placements',
            'grok-4.7'
        ),
        $answerModeration
    ),
    'An irrelevant answer was allowed to publish.'
);
$expect(
    collegeAnswerCanPublish(
        $answerText,
        ['ok' => true, 'moderation' => array_merge(
            $answerModeration['moderation'],
            [
                'decision' => 'REVIEW',
                'is_duplicate' => true,
                'is_complete' => false,
                'is_correct' => true,
                'is_uncertain' => false,
                'potentially_misleading' => false,
            ]
        )]
    ),
    'A safe relevant answer was blocked for completeness or duplicate concerns.'
);
$expect(
    !collegeAnswerCanPublish(
        $answerText,
        ['ok' => true, 'moderation' => array_merge(
            $answerModeration['moderation'],
            ['potentially_misleading' => true]
        )]
    ),
    'A potentially misleading answer was allowed to publish.'
);
$lowRelevanceModeration = [
    'ok' => true,
    'moderation' => array_merge(
        $answerModeration['moderation'],
        ['relevance_score' => 0.70]
    ),
];
$expect(
    !collegeAnswerCanPublish($answerText, $lowRelevanceModeration),
    'A low-relevance answer was automatically published.'
);
$expect(
    collegeAnswerIsIrrelevant($answerText, $lowRelevanceModeration),
    'A low-relevance answer was not rejected.'
);
$expect(
    !collegeAnswerCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'missing_api_key'],
        $lowRelevanceModeration
    ),
    'A low-relevance fallback answer was accepted.'
);
$expect(
    collegeAnswerCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'missing_api_key'],
        $answerModeration
    ),
    'A safe, clearly relevant answer could not use the validator-unavailable fallback.'
);
$expect(
    collegeAnswerCanUseRelevanceFallback(
        ['result' => 'REVIEW_REQUIRED', 'api_status' => 'ok'],
        $answerModeration
    ),
    'A safe, relevant Groq result could not resolve an uncertain xAI result.'
);
$expect(
    collegeAnswerIsIrrelevant(
        ['result' => 'IRRELEVANT'],
        $answerModeration
    ),
    'An xAI-classified irrelevant answer was not rejected.'
);
$expect(
    collegeAnswerIsIrrelevant(
        ['result' => 'RELEVANT'],
        ['ok' => true, 'moderation' => array_merge(
            $answerModeration['moderation'],
            ['is_relevant' => false]
        )]
    ),
    'A Groq-classified irrelevant answer was not rejected.'
);
$expect(
    !collegeAnswerIsIrrelevant(
        ['result' => 'REVIEW_REQUIRED'],
        $answerModeration
    ),
    'An uncertain result was incorrectly treated as irrelevant.'
);

foreach ([
    'IRRELEVANT',
    'NEEDS_CLARIFICATION',
    'REVIEW_REQUIRED',
] as $resultCode) {
    $classification = collegeValidationClassification(
        json_encode(['result' => $resultCode, 'category' => 'Placements']),
        'answer',
        'Placements',
        'grok-4.7'
    );
    $expect(
        $classification['result'] === $resultCode,
        'A supported validation status was not preserved.'
    );
}

$pdo = new PDO('sqlite::memory:');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
putenv('XAI_API_KEY=');

$withoutFacts = validateCollegeContent(
    $pdo,
    21,
    'answer',
    'Academics',
    'The answer text makes a college-specific claim.',
    'Question title',
    'Question details'
);
$expect(
    $withoutFacts['result'] === 'REVIEW_REQUIRED' &&
        $withoutFacts['api_status'] === 'missing_api_key',
    'An answer with a missing xAI key did not report validator unavailability.'
);

$missingKey = collegeValidationApiRequest(['model' => 'grok-4.7']);
$expect(
    empty($missingKey['ok']) &&
        ($missingKey['api_status'] ?? '') === 'missing_api_key',
    'Missing xAI key did not fail safely before making a request.'
);

$overlongQuestion = validateCollegeContent(
    $pdo,
    21,
    'question',
    'Admissions',
    str_repeat('x', 10001),
    'Question title'
);
$expect(
    $overlongQuestion['result'] === 'NEEDS_CLARIFICATION' &&
        $overlongQuestion['api_status'] === 'invalid_input',
    'Excessively long question input was not rejected before API submission.'
);

$emptyQuestion = validateCollegeContent(
    $pdo,
    21,
    'question',
    'Admissions',
    '',
    'Question title'
);
$expect(
    $emptyQuestion['result'] === 'NEEDS_CLARIFICATION' &&
        $emptyQuestion['api_status'] === 'invalid_input',
    'Empty question input was not rejected before API submission.'
);

for ($attempt = 0; $attempt < 5; $attempt++) {
    $expect(
        collegeValidationRateLimitAllowed(321, 'question'),
        'An allowed request was rejected before the rate limit.'
    );
}
$expect(
    !collegeValidationRateLimitAllowed(321, 'question'),
    'The sixth request within a minute passed the rate limit.'
);

echo "Passed {$checks} AI validation checks." . PHP_EOL;
