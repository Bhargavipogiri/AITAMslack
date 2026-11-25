AITAM SLACK — QUESTION AND ANSWER MODERATION

CONFIGURATION
1. Copy the settings from `.env.example` into the project-root `.env` file for the existing Groq moderation.
2. Set `GROQ_API_KEY` locally. Never commit `.env` or share the key.
3. Configure `XAI_API_KEY` in the PHP/Apache server environment. The new validation
   integration reads this environment variable directly; it does not read the key
   from frontend code or `.env`. `XAI_MODEL` is optional and defaults to `grok-4.7`.
   The integration uses xAI's Responses API and stores `store=false`.
4. `GROQ_MODEL` defaults to `openai/gpt-oss-safeguard-20b`. The account's available
   models are checked before each moderation request. Set another model only if it
   supports Groq Chat Completions JSON mode and is enabled for your account.
   The former `OPENAI_API_KEY` setting is accepted only when its value is a Groq
   `gsk_` key; `OPENAI_QUALITY_MODEL` is not used as a Groq model setting.
5. If Groq moderation cannot confirm an answer is safe, it remains PENDING. If xAI
   validation is unavailable, an answer may use Groq's existing relevance and safety
   checks. Questions may use Groq as a fallback only when it confirms the exact
   selected category and existing moderation approves the question.

DATABASE
Apply `database/migrations/2026_11_add_groq_moderation.sql` once to the existing
`aitamslack` database. It adds `publication_status` to the existing questions and
answers tables and creates the append-only `moderation_events` audit table. Existing
questions and answers are marked APPROVED. New submissions are approved only after a
valid, confident AI approval; flagged, uncertain, or failed moderation results are
saved as PENDING. Do not rerun the migration after it has already been applied.
Apply `database/migrations/2026_11_add_college_content_validation.sql` once as well.
It creates the append-only validation-result table and the staff-managed verified
college facts table. Existing installations must also apply
`database/migrations/2026_11_add_relevant_answer_validation_status.sql` to allow the
answer-relevance result code in the validation table.

WORKFLOW
- Students must be signed in to submit questions or answers.
- Question submissions are checked for safety, relevance, and likely duplicates.
  Questions classified as off-topic or needing clarification are not stored; the
  form explains what the student should change. Campus Life includes facilities,
  student services, clubs, events, and activities, not personal relationships or
  gossip about identifiable people.
- Answers are checked for safety and whether they address the original question.
  Relevant answers are published automatically after safety checks; answers marked
  irrelevant are not stored. Relevance does not establish that college-specific
  claims are factually correct or supported by verified facts.
- xAI only classifies submitted content; it is not asked to generate or rewrite
  questions or answers. The original user submission is retained.
- Clearly safe, meaningful, and relevant content is automatically approved and
  published. Off-topic, unclear, or unverified questions are not stored. If xAI is
  unavailable, question submission uses Groq only when it confirms the selected
  category; otherwise it asks the student to retry. Answers may use Groq's relevance
  and safety checks as a fallback.
- Public visitors see approved content only; authors can view their own pending or
  rejected submissions and their validation result.
- Administrators review pending items in Admin > Question Management and Answer Management;
  those pages default to the pending queue and allow filtering by publication status.
  AI results and append-only decisions are available under Moderation History.
- Public leaderboard totals include approved questions and answers only. Points are
  based on approved answers; approved questions asked break ties.

TRANSPORT AND PRIVACY
Both integrations use HTTPS with certificate verification. Existing Groq moderation
uses cURL when available and a verified HTTPS stream transport otherwise. The new
xAI integration uses PHP cURL only. Submitted content is sent to each provider only
when its corresponding server-side key is configured; xAI requests set `store=false`.
Provider failures are shown as safe retry messages and raw provider errors are not
shown to users. The fallbacks above apply only when xAI validation is unavailable.

TESTS
Run `C:\xampp\php\php.exe tests\ai-validation-test.php` for deterministic validation
checks. Live xAI behavior requires an active `XAI_API_KEY` and an applied migration;
the local deterministic tests are not live API tests.
