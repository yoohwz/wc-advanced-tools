# AT Lean Engineering Workflow

This is the delivery workflow for **WooCommerce Advanced Tools** (`yoohwz/wc-advanced-tools`). It borrows the useful GitHub-native lifecycle from AAP while removing AAP-specific identity, membership, OTP, Free/Core and licensing machinery.

**Product boundary:** Premium-first, standalone WooCommerce tools: safe import/export, bulk operations and data-health utilities. The Free/Freemium split is deferred; no Free code, shared Core contract, WordPress.org release or product implementation is authorized by this workflow task.

## 1. One source of truth per fact

| Fact | Authority |
| --- | --- |
| Goal, scope, acceptance and risk | GitHub Issue `#N` (task `AT-N`) |
| Candidate, ancestry, changed files | Git branch, PR head/base and diff |
| Technical findings and remedies | PR review/comments |
| CI results | GitHub Actions and Checks |
| Product/data/compatibility invariants | Applicable repository docs and approved Issue boundary |
| Acceptance | Explicit exact-head ChatGPT record on PR |
| Merge | GitHub merge record following separate Human approval |
| Release / publication / rollback | Separate Human authorization and verified runbook |

No task allocator, identity registry, mutable task-state file, approval-comment parser, proof ledger or second workflow engine. Do not treat prior conversation/context as stronger evidence than current GitHub state.

## 2. Roles and work admission

Human decides what to build, priorities, unresolved business trade-offs, merge, production publication and rollback. ChatGPT specifies desired outcome, customer value, scope and Acceptance, and resolves genuine undecided product/security/architecture boundaries. Codex independently chooses the technical design, performs discovery, changes/tests and maintains the PR. Fresh independent review is required for Controlled candidates.

Except for read-only discovery, use a GitHub Issue `AT-N` for every change intended for merge. One Issue, one `agent/at-N-scope` branch and one Draft PR to `main` by default. Recover existing GitHub state before creating new work. Do not push directly to `main`, force-push or overwrite unrelated changes. A moved base requires a fresh comparison; material conflicts go back to the appropriate decision owner.

`AT-1` first-adoption exception: this repository had no commits or `main` branch. The single README commit initializing `main` is the smallest unavoidable bootstrap and does not grant general direct-push rights. All subsequent workflow files are proposed through AT-1's Draft PR. The new workflow becomes authoritative only after its own Human-approved merge; it cannot approve or merge itself.

## 3. Commands and outcomes

- `Create ...`: ChatGPT records objective, included/excluded scope, risk, user-visible Acceptance and relevant constraints on a new Issue.
- `Run AT-N`: Codex recovers Issue/PR/head/base and implements the bounded task, or performs read-only discovery when a decision is still unresolved.
- `Continue AT-N`: resumes the same task; not a new lifecycle.
- `Plan Review AT-N`: ChatGPT resolves a concrete unresolved product, data-loss, privacy, architecture, permission or compatibility decision. Controlled work does not automatically require it.
- `Review AT-N`: request/refresh independent Technical Review of the frozen Controlled candidate when necessary.
- `Acceptance Review AT-N`: ChatGPT checks exact candidate, scope, relevant tests, review and blockers while PR is Draft; after blocker-free precheck, marks unchanged PR Ready, observes `AT Required CI` to a terminal result, then records an explicit SHA-bound Acceptance PASS only after fresh head/base verification.
- `Merge AT-N`: Human authorizes a merge of the exact accepted candidate after checks, unresolved blockers and Acceptance are re-verified. Does **not** publish software.

Each terminal handoff should end with:
```text
STATUS: READY_TO_RUN | PLAN_REVIEW_REQUIRED | TECHNICAL_REVIEW_REQUIRED | ACCEPTANCE_REVIEW_REQUIRED | READY_TO_MERGE | HUMAN_DECISION_REQUIRED | FINALIZED
Task: AT-N
Next: <one exact Human command, or None>
```
These are readable hints, never workflow state stored for automation.

Do not declare `READY_TO_RUN` if a known unresolved in-scope product/safety/data decision prevents implementation. Codex may do read-only discovery and return a concrete Plan Review packet (facts, options, trade-offs, recommended narrow choice), without writing across that boundary. Explicitly defer optional work instead of turning it into a blocker or silently implementing it.

## 4. Risk-based execution

**Fast** covers bounded docs/copy, presentation-only adjustments, safe maintenance of existing tests/tooling and understood low-risk defects without security, data or financial consequences. Codex checks its own work; no mandatory second reviewer.

**Controlled** is required for changes affecting customer/order personal data or privacy, import/upload/download and data retention, authorization/capabilities/admin endpoints, financial/order/subscription state, bulk writes/deletes, persistent identity/reconciliation/migration, job retry/resume/cancellation, data recovery/undo, plugin licensing/updater, external integrations, shared public contracts, architecture, release safety or this workflow. Codex implements and validates; a fresh independent Technical Reviewer must inspect the exact frozen head. If a Fast task uncovers one of these concerns, escalate in the same Issue/PR before continuing.

A reviewer starts with Issue/approved boundary, current base/head/diff, applicable source and tests, not the implementer's scratch reasoning or predetermined verdict. Review security/privacy, correct behavior, failures/retries, compatibility and evidence. Only evidenced material defects, scope violations, data loss or safety risks block; optional improvements are non-blocking. Corrections remain in the same PR and require a fresh reviewer when the head changes. Stop repeated unproductive review loops and report a concrete Human decision.

## 5. Product-specific safety and Acceptance

For data-changing tools, state exactly which object types/fields can change, how matches and duplicates are resolved, what null/empty means, which external side effects can occur, and how failures are reported. A true `Dry Run` must perform no data mutation. Preview should report creates/updates/skips/errors and meaningful before/after values as appropriate. Prevent accidental overwrites, duplicate processing on retry and partial-result ambiguity. Do not promise universal rollback; scoped undo requires evidence that it is safe.

Use WooCommerce public CRUD/data-store interfaces for orders and HPOS. Preserve WooCommerce permissions and capabilities; nonces alone do not authorize access. Validate files and field mappings, protect private exports, avoid spreadsheet formula injection, redact sensitive logs and never export credentials/payment tokens by default. Use existing WooCommerce/WordPress facilities (including Action Scheduler when warranted) for bounded background jobs and existing maintenance tools before building replacements.

For data/import/price/order changes, check behavior on the applicable WooCommerce surface, including failure/cancellation where relevant. Validate against disposable WordPress/WooCommerce/HPOS setups for destructive, sensitive or automated tests. A Human-owned Local site is for explicitly bounded manual checks, not uncontrolled test writes. No live production/customer data write without separately approved operational scope.

## 6. Candidate, review, CI and Acceptance

Develop on a Draft PR. Before freezing, Codex runs applicable inexpensive lint/tests, a focused behavior check when possible and a `git diff --check`. Record exact head/base, scope, executed tests, meaningful limitations and next owner on the PR. Use a real runtime test when CI syntax checks cannot establish the feature's customer-visible outcome.

`.github/workflows/ci.yml` intentionally has one lean CI job with three GitHub-visible names:
- **AT Iteration Gate** on Draft PR events: quick syntax/whitespace feedback, not merge/Acceptance evidence.
- **AT Required CI** on Ready non-draft PR candidate: exact PR/head/base validation and applicable source syntax/whitespace. This is the required final hosted check for the present bootstrap.
- **AT Main Integrity** on main push: lightweight smoke/syntax/whitespace signal, never publication.

Initially the repository contains no PHP, JS, runtime tests or release tooling. **A successful AT Required CI now proves only the checks it executes**, not WordPress/WooCommerce runtime compatibility. On the first implementation tasks, Codex must add *relevant* focused behavioral/regression checks and expand the Required CI to run the agreed essential cases before claiming product Acceptance; do not build an arbitrary compatibility matrix upfront. An explicitly authorized release task must validate an actual installable ZIP independently.

Keep PR Draft through correction, Controlled technical review and internal Acceptance precheck. A blocker-free precheck transitions the same candidate to Ready, initiating final CI. ChatGPT correlates the run to the current PR/head/base and observes terminal success; queued, skipped, cancelled, missing or stale CI is not PASS. If platform limitations prevent a terminal result, stop and record the run and limitation, without inventing Acceptance. A material source/test defect returns the PR to Draft for correction and refreshed evidence.

Any head SHA change invalidates that candidate's review/Acceptance (including docs/tests only). A base move requires compatibility recheck; new blockers invalidate the precheck. An accepted head may merge only after a separate Human `Merge AT-N`, with fresh exact-head checks and Acceptance verification. GitHub Actions cannot grant Acceptance or merge authority.

## 7. Release and distribution

This repository distributes a **Premium plugin**, not a WordPress.org Free/Core build. The release workflow is `docs/releasing.md`; it remains a policy/runbook until actual code, staging and delivery capabilities are established. Merge does not bump a version, package, tag, deploy or publish anything. All those actions require separate Human-selected release scope and approval. Do not silently copy AAP-specific YLM product IDs, origin/bridge configuration, hidden keys or hosting paths.

When/if Human later chooses to split Free from Premium, create a separate scoped architecture/product task informed by the actual mature code and compatibility contracts. Do not pre-build free stubs, shared service layers, migration tables, entitlement routes or WordPress.org CI for a hypothetical future Free version.

## 8. Lean-first review of workflow changes

Do not add generic helpers, multiple queues, reviewers, proof tools, serial gate ceremonies or state machinery without an evidenced current problem. Keep tests proportional to actual product risks, use existing WordPress/WooCommerce capabilities, and stop when accepted behavior, mandatory safety and necessary checks pass. Changes to this workflow itself use the workflow accepted on their admitted base: proposed changes cannot waive their own review, Acceptance or Human merge authority.
