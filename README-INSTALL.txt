COLLEGE CONNECT — ASK QUESTION PAGE

FILES
- ask_question.php
- assets/ask-question.css
- assets/ask-question.js

INSTALL
1. Back up your current ask_question.php.
2. Put ask_question.php beside questions.php and question.php in your project root.
3. Copy the CSS and JS into your existing assets folder.
4. Confirm these existing files are present:
   includes/bootstrap.php
   includes/ai_helpers.php
   includes/openai_moderation.php
   includes/header.php
   includes/footer.php
5. Confirm your header loads the shared root CSS tokens.

EXISTING INTEGRATION
This page reuses the Phase 16 functions:
cc_phase16_diagnostic_id()
cc_phase16_log_diagnostic()
cc_phase16_risk_scan()
cc_phase16_question_quality_for_submission()
cc_phase16_question_decision()
cc_phase16_question_can_persist()
cc_phase16_log_submission_decision()
cc_phase16_record_moderation()

It reads categories(id, name) and inserts into the already-referenced questions fields:
user_id, category_id, title, body, status, moderation_status.
Moderation logging uses your existing helper/table. No new SQL tables or migration are added.

FAIL-CLOSED
A question is inserted only when both safety and quality checks complete and the existing
Phase 16 policy explicitly allows publication. If a check fails, is uncertain, or is flagged,
the question is not inserted. The OpenAI key remains server-side in the existing .env setup.

VERIFY BEFORE DEPLOYING
The current pasted project source confirms the named helper functions and the questions
fields used in existing question detail queries. I could not inspect the full live project
folder or database dump here, so compare the INSERT column names with your actual
questions table before replacing the live page. Test an approved question, a flagged
question, and an API-unavailable submission.
