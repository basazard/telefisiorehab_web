# PRD 10: Clinical review of DRAFT values and instrument licences, TeleFisioRehab

> **Status:** to build (engineering part) + human work in parallel · **Branch:** `main` (no feature branches); tags `clinical-w1-done` and `clinical-signed`
> **Repository:** `basazard/telefisiorehab_web` · **Stack:** PHP 8.3.4 (no PHP 8.4+ syntax), Laravel 13, Pest 4, Windows + PowerShell
> **Read first:** `CLAUDE.md`, `prd/03` to `07`. **Effort:** engineering 1 to 2 days; the clinical review itself takes 2 to 4 weeks of elapsed time.

---

## 1. Why

The app already decides things about patients: which red flags stop the program, which risk tier a patient gets, which exercises and doses they receive, and when a program is
judged "not improving". Every one of those rules is marked DRAFT in the PRDs and in the code ("perlu persetujuan klinis"). No real patient should be handled by DRAFT rules.
Separately, three instruments are copyrighted (ODI, SPADI, Borg CR10), and the repo reproduces their item wording in code.

**Goal:** (1) every clinical rule has an owner-visible record (value, source, status, who approved it, when); (2) a physiotherapist or doctor reviews them with a pack that is easy
to review; (3) approved values are in the code and later changes cannot slip in unreviewed; (4) each licensed instrument is either licensed or switched off and replaced.

## 2. Who does what

| Claude Code (this PRD) | The owner (people work, in parallel) |
| --- | --- |
| Inventory every DRAFT or clinical value into a register and keep it in sync with the code | Find the reviewers (at least one physiotherapist; for red flags and urgency rules a doctor, for example a rehabilitation specialist) |
| Generate the reviewer pack and worked example cases from the real code | Run the review sessions and collect signed decisions |
| Apply approved changes, one commit each | Contact the instrument licensors and decide with a lawyer |
| Add a release gate that blocks deployment while values are unreviewed | Decide the fallback when a licence is refused |
| Add switches so a refused instrument can be turned off | Answer the open questions in section 9 |

Claude Code must **never invent clinical values, sources or licence terms**. Where something is unknown it writes "tidak diketahui, perlu diisi".

## 3. What exists today (verified in the repo)

DRAFT areas and where they live:

| Area | PRD | Code | What the reviewer decides |
| --- | --- | --- | --- |
| Red flags | 03 | `RedFlags.php` (16 questions, `PAIN_STOP_THRESHOLD = 9`, fever wording "di atas 38,5 °C"), `Intervention`/stop pages | the question list, the pain threshold, the urgent-care text, emergency numbers 112 / 119 |
| Domain and analysis | 04 §3.1, §3.3 | `ClinicalAssessment.php` | rule order for the domain, "keterbatasan fungsi atau risiko" mapping, prevention education text |
| Instrument cut-offs | 05 §3.1 | `Instruments.php` (`LEFS_LIMIT 60`, `ODI_LIMIT 20`, `SPADI_LIMIT 20`, `PSFS_LIMIT 7`, `BARTHEL_LIMIT 100`, `STEADI_LIMIT 4`, `TUG_LIMIT 12`, `MMRC_LIMIT 2`, `STS_LIMIT 20`, `BORG_LIMIT 5`, `HR_LIMIT 100`, `RR_LIMIT 20`, `EDEMA_CM_LIMIT 2`) | each cut-off and its unit |
| Risk bands and emergency rules | 05 | `RiskStratification.php` (`NRS_HIGH 7`, `LEFS_HIGH 30`, `ODI_HIGH 40`, `SPADI_HIGH 50`, `PSFS_HIGH 4`, `BARTHEL_HIGH 60`, `STEADI_HIGH 7`, `TUG_HIGH 20`, `MMRC_HIGH 3`, `STS_HIGH 12`, `BORG_HIGH 7`) | each band and the "Tinggi" and emergency rules |
| Program selection and dose | 06 §3.1 | `Intervention.php`, `Programs.php`, `ExerciseLibrary.php` (the built-in library) | exercise choice per pathway, dose, progression, intervals, education, the library itself |
| Monitoring and reassessment | 07 §3.2 to 3.4 | `Monitoring.php` (MCIDs, 4-week reassessment, drastic worsening, goal achieved, barrier tips) | each MCID and direction, thresholds, decision rules |

Clinical content that is **not labelled DRAFT but should be reviewed too**:
- BMI categories in `Assessment::bmi` use 18.5 / 25 / 30. Indonesia's health ministry classification may differ for adults. Ask the reviewer which to use.
- Age bands (under 18, 60 and over) and the special groups (pregnancy, post-op).
- All patient-facing safety text: the red-flag stop page, the "bukan diagnosis" wording, the consultation and referral messages.

Instruments in the code (files: `Instruments.php`, `RiskStratification.php`, `Questionnaires.php`, `Assessment.php`, `Programs.php`, `Monitoring.php`,
`resources/views/components/instrument-fields.blade.php`, `pages/patient/evaluation.blade.php`):
- The three you named: **ODI**, **SPADI**, **Borg CR10** (used in the 1-minute sit-to-stand and the "berat" limit).
- Also present, not yet on anyone's list: **LEFS, PSFS, Barthel Index, mMRC, CDC STEADI checklist, TUG**.
- `Instruments.php` and `Questionnaires.php` contain the item wording and answer choices of these instruments in Indonesian. Who produced that translation is not recorded.

## 4. Working rules (read first)

1. Do the work packages in order and show a short plan before each. One commit per package (per decision in W4). Tests green after each. Work directly on `main`: no feature branches and no pull requests. Commit small and often, run `php artisan test --compact` before every commit and never commit with a failing test. Push to `origin/main` only when the tests pass. Never force-push or rewrite history on `main`. Mark rollback points with tags (`git tag <name>`, then `git push --tags`).
2. Do **not** change any clinical value or text except in W4, and only as an approved decision from the owner.
3. Do not copy licensed instrument wording into any new document. In the reviewer pack and the register refer to instruments by name and item number only.
4. Documents for reviewers and licensors are in Indonesian. Code and tests stay in English with Indonesian user-facing strings as today.
5. Never put secrets, names or personal data of reviewers in the repository except the name, profession and registration number they agree to sign with.
6. Report after each package: files changed, tests result, anything unknown that the owner must find out.

## 5. Work packages

### W1. Clinical parameter register (Claude Code)
- `prd/clinical/parameters.json` is the source of truth. One entry per clinical value or rule:
  `id` (for example `P-RF-02`), `module`, `label`, `value` (as in the code), `unit`, `location` (class and constant or method), `prd_ref`, `rationale` and `source`
  (only what the PRD or code comment already states, otherwise "tidak diketahui, perlu diisi"), `status` (`draft`, `approved`, `changed`, `removed`),
  `reviewer`, `reviewed_on`, `comment`, and `approved_hash` (hash of the value when approved).
- Add a tag comment `@clinical P-XXX` next to each constant or rule in the code.
- `php artisan clinical:status [--strict]`: prints counts per module and status, lists drafts, verifies each `approved_hash` against the current code value. `--strict` exits with code 1 if anything is `draft` or `changed`, or a hash no longer matches (code changed after approval).
- Pest tests (pure): every `@clinical` tag exists in the register and every register entry with a location has a tag; scalar values in the register equal the constants in the code.

**Done when:** every item in section 3 is registered, `clinical:status` runs, and the tests pass. Report the total number of parameters.

### W2. Reviewer pack and worked cases (Claude Code)
- `php artisan clinical:review-sheet` writes `prd/clinical/review-sheet.md` in Indonesian:
  - Cover page: what the app does and does not do (not a diagnosis tool), what the reviewer is asked to sign off, how to answer, and a sign-off block (name, profession, STR or SIP number, date, signature, statement of review scope).
  - One section per module with a table: ID, parameter, current value, rationale and source, decision (`[ ] Setuju  [ ] Ubah menjadi ____  [ ] Hapus`), comment.
  - Open questions per module (section 9 plus anything unknown found in W1).
- `php artisan clinical:vignettes` writes `prd/clinical/vignettes.md`: **at least 20 worked cases** computed by the real code (inputs and outputs): one or more per domain,
  every risk tier, the red-flag stops (including pain 9), pediatric, geriatric, pregnancy, post-op, a patient who improves, stays the same, worsens drastically, and reaches the goal.
  For each case show inputs, domain, instrument results, risk tier and urgency, pathway, dose, education headline, and a line `[ ] Sesuai  [ ] Tidak sesuai, karena ____`.
  Reviewing outputs on cases is easier and safer than reviewing numbers alone.
- Both commands are deterministic and can be re-run after changes.

**Done when:** both files are generated, readable on a phone and printable, and contain no licensed item wording.

### W3. Review and decisions (the owner, people work)
1. Send the reviewer the sheet and the cases (print or PDF) a week before the session.
2. Hold the review (about 90 minutes per module group). Record every decision in the sheet and have it signed.
3. Transcribe the decisions into `prd/clinical/decisions.csv` with columns `id,decision,new_value,reviewer,date,comment` and give it to Claude Code.
4. Repeat for anything that changes the patient-facing text.

### W4. Apply the decisions (Claude Code, after W3)
- For each row of `decisions.csv`: change the constant, rule or text; update the PRD document it comes from; update the register (`approved`, reviewer, date, new `approved_hash`, or `changed` / `removed`);
  update the affected tests and the vignettes. One commit per decision, message `clinical(P-XXX): <decision>`.
- Rows that say only "Setuju" change code nothing; they only update the register.
- Anything not stated in the CSV is left as `draft`. Ask the owner; never guess.

### W5. Release gate (Claude Code, after W1)
- `.env` key `CLINICAL_SIGNOFF_REQUIRED` (default `true` when `APP_ENV=production`).
- `deploy.sh` runs `php artisan clinical:status --strict` before going live when that key is true and **stops** if it fails.
  Overriding needs an explicit `ALLOW_UNREVIEWED=1 bash deploy.sh ...`, prints a loud warning and appends a line to `storage/logs/deploy-overrides.log` (date, commit, user).
- Add the same check as a CI step. It only prints a warning until the owner sets the repository variable `CLINICAL_STRICT=true` after the sign-off; from then on it fails the build.
- Show the clinical version (`config('clinical.version')`, for example `2026.1-draft`, later `2026.1`) in the footer of staff pages. Optional: store it on each assessment and program created (new nullable column), so a result can be traced to the rule set that produced it.

**Done when:** `deploy.sh` refuses to deploy while drafts exist and accepts after approval, with tests for the command.

### W6. Instrument licences (Claude Code + the owner)
1. **Claude Code:** write `prd/legal/instrument-licences.md` with one row per instrument: where it is used (files, screens), version and language in the app, who produced the Indonesian wording (unknown unless stated),
   what the app does with it (shows items, scores, stores answers, shows results to staff), licensor (to verify), commercial-use terms (to verify), status (`not started`, `requested`, `granted`, `refused`, `replaced`),
   contact, dates, fee, required attribution text, expiry, notes. Fill in the starting points below; leave unknown cells as "tidak diketahui".
2. **The owner:** contact each licensor, describe the use truthfully (a commercial web app for home physiotherapy in Indonesia, patients complete the questionnaire in-app, answers are stored, staff see the scores), and record the outcome in that file.
3. **Claude Code:** add switches in `config/clinical.php`: `instruments.odi.enabled`, `instruments.spadi.enabled`, `instruments.borg.enabled` (default `true` until decided).
   When an instrument is off, flows must still complete using the alternatives the PRDs already allow:
   - ODI and SPADI off: use PSFS for that area (PRD 04 already lists PSFS as an alternative) and skip the instrument-specific cut-offs.
   - Borg off: replace the Borg CR10 question in the sit-to-stand with a plain 0 to 10 effort question without the name "Borg" or its verbal anchors, **only with a reviewer-approved wording and cut-off** (register entry), and remove the word "Borg" from the UI.
4. **Claude Code:** tests proving the assessment, risk, program and monitoring flows complete with each switch off.
5. When a licence is granted, add the required attribution text where the licensor demands it.

**Starting points to verify (not legal advice, not confirmed for your case):**
- **ODI:** Mapi Research Trust (Lyon, France) distributes the licence for version 2.1a; its page says clinical and academic users can obtain a licence at no charge while commercial users are asked to pay a licence fee; a translation also needs to come from the licensed set.
  Contact listed on the licensed questionnaire: PROinformation@mapi-trust.org.
- **Borg CR10:** the scale's author site is borgperception.se; the author has objected publicly to unauthorised changes to the scale's wording, so a paraphrased version is risky.
- **SPADI:** terms not confirmed; find the current permission page of the original authors and ask whether a commercial app is covered.
- **Also check** LEFS, PSFS, Barthel Index, mMRC, CDC STEADI and TUG (usage terms differ and some are free to use but require attribution or permission), and before the therapist side builds on them: MMSE, Denver II, GMFM, FMS and Berg Balance Scale.

**Done when:** every instrument row has a status, the switches work with tests, and no instrument is live without `granted` (or an explicit written owner decision recorded in the file).

## 6. Order and timeline

1. W1 and W2 first (engineering, 1 to 2 days), then the owner starts W3 and W6 contacts immediately: they take the longest.
2. W5 can be built while waiting. 3. W4 when the signed decisions arrive. 4. Re-run W2 after W4 and ask the reviewer to confirm the changed items only.
5. Only then should real patients use the app (a pilot with a clinic, with the clinic therapist supervising, needs the same sign-off).

## 7. Out of scope (separate work, not blocked by this PRD)

Consent screens and privacy policy, audit viewing, data export and deletion, encryption of sensitive columns, 2FA for staff, medical-device classification, professional liability, translation of Laravel's own messages.
Section 9 lists the questions to take to a lawyer.

## 8. Definition of done

- [ ] Every clinical parameter in section 3 is in the register with a status; `clinical:status --strict` passes after the review.
- [ ] A signed review sheet from a physiotherapist (and a doctor for red flags and urgency) is stored outside the code repository and referenced by file name in the register.
- [ ] Approved values are in the code, one commit each; tests and vignettes updated.
- [ ] `deploy.sh` is gated; the override has been used zero times for production.
- [ ] Each of ODI, SPADI, Borg CR10 is `granted` or `replaced`, with tests for the switches; the second-tier instruments have a status.

## 9. Open questions for the owner and for a lawyer

For the clinicians: Which BMI classification applies? Are the age bands right? Which instruments does the clinic already use and license? Should the pain threshold of 9 apply to every area or the highest only?
For a lawyer (not answered here): Does the app count as a medical device or only as patient education software under Indonesian rules? What do the telemedicine and personal-data rules
require for health data and for staff working across clinics? What consent and privacy notice wording is needed? What must the app say about being non-diagnostic?
