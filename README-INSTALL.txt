AITAM SLACK — QUESTION AND ANSWER MODERATION

CONFIGURATION
1. Copy the Groq settings from `.env.example` into the project-root `.env` file.
2. Set `GROQ_API_KEY` locally. Never commit `.env` or share the key.
3. `GROQ_MODEL` defaults to `openai/gpt-oss-safeguard-20b`. The account's available
   models are checked before each moderation request. Set another model only if it
   supports Groq Chat Completions JSON mode and is enabled for your account.
   The former `OPENAI_API_KEY` setting is accepted only when its value is a Groq
   `gsk_` key; `OPENAI_QUALITY_MODEL` is not used as a Groq model setting.
4. If the key, model, or API is unavailable, the question or answer is still saved as
   PENDING and remains private until administrator review.

DATABASE
Apply `database/migrations/2026_11_add_groq_moderation.sql` once to the existing
`aitamslack` database. It adds `publication_status` to the existing questions and
answers tables and creates the append-only `moderation_events` audit table. Existing
questions and answers are marked APPROVED. New submissions are approved only after a
valid, confident AI approval; flagged, uncertain, or failed moderation results are
saved as PENDING. Do not rerun the migration after it has already been applied.

WORKFLOW
- Students must be signed in to submit questions or answers.
- Question and answer submissions are checked for safety, relevance, and likely
  duplicates. Answers are evaluated with their original question for context.
- Clearly safe, meaningful, and relevant content is automatically approved and
  published. Flagged or uncertain content and API failures remain private as PENDING.
- Public Question History and answer pages show approved content only.
- Administrators review pending items in Admin > Question Management and Answer Management;
  those pages default to the pending queue and allow filtering by publication status.
  AI results and append-only decisions are available under Moderation History.
- Public leaderboard totals include approved questions and answers only. Points are
  based on approved answers; approved questions asked break ties.

TRANSPORT AND PRIVACY
The Groq request uses HTTPS with certificate verification. cURL is preferred; PHP's
verified HTTPS stream transport is used when cURL is unavailable. Student-submitted
content is sent to Groq for moderation when the service is configured. Without a
configured Groq key, no external request is made and submissions remain pending for
admin review.
