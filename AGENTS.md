# WooCommerce Advanced Tools — agent instructions

Repository: `yoohwz/wc-advanced-tools`. Product: **Premium-first, standalone WooCommerce plugin**. The repository was initially empty; `AT-1` bootstraps governance only. No free/core companion, public WordPress.org release, commercial publisher, or runtime plugin exists yet.

Read `docs/workflow.md` before making or reviewing repository changes. Read `docs/releasing.md` for release tasks. Work on one admitted AT-N Issue, branch and draft PR; do not change `main` directly after the one-time README bootstrap.

## Roles and authority

- Human: product direction, unresolved trade-offs, merge, release, production deployment and rollback.
- ChatGPT: task objective, scope, Acceptance criteria, evidenced risks, Plan Review only when a material boundary is unresolved, and exact-candidate Acceptance.
- Codex: repository discovery, technical design, implementation, tests, validation and PR corrections inside the accepted scope.
- Fresh independent reviewer: technical review for Controlled candidates.
- GitHub: task/PR/head/base/check/review/merge facts. No duplicate task-state files or approval engines.

Prefer existing WordPress/WooCommerce APIs and the smallest complete solution. Do not invent a framework, service, data store, background daemon, test matrix or coordination process for speculative needs. Do not expand an Issue into adjacent feature work. Do not block Acceptance for aesthetic preferences or optional cleanup without a material issue.

Human-facing analysis, plans, status and handoffs are **concise Vietnamese**. Lead with result/problem, practical impact and the next exact Human action. Keep source/code identifiers, commands, paths, statuses and repository documentation in their original language.

## Human commands

GitHub Issue `#N` is `AT-N`. Every merge-bound change requires an admitted Issue; read-only discovery may precede it. Operator commands:

- `Create ...`: ChatGPT opens an outcome/scope/Acceptance Issue.
- `Run AT-N`: Codex recovers current Issue, branch/PR and performs implementation or needed discovery.
- `Continue AT-N`: resume the same task from GitHub facts.
- `Plan Review AT-N`: ChatGPT decides only a genuine product/data/security/compatibility boundary.
- `Review AT-N`: request a fresh independent review for Controlled work when not already available.
- `Acceptance Review AT-N`: ChatGPT checks the frozen candidate, transitions the unchanged draft PR to Ready only when blocker-free, observes the final exact-head `AT Required CI` and records SHA-bound Acceptance after PASS.
- `Merge AT-N`: separate explicit Human merge authorization after fresh verification. Never implies release.

End task handoffs with `STATUS: <label>`, `Task: AT-N`, and `Next: <one command or None>`. Labels are navigation text, not a separate state machine.

## Risk

**Fast**: bounded docs, UI copy/presentation, harmless tooling or a well-understood low-risk fix. Focused validation; no mandatory independent reviewer.

**Controlled**: changes to import/export of customer/order personal data, protected file uploads/downloads, admin/REST/AJAX capabilities, WooCommerce financial/order/subscription state, bulk writes/deletes, data integrity/recovery, scheduled jobs, schema/migrations, licensing/update delivery, cross-repository contracts, release packaging or workflow safety. Require a fresh independent exact-head Technical Review; separate Plan Review only if an actual decision is unresolved.

Never rely on a nonce without permissions/ownership checks. Never access or leak secrets, payment tokens or credentials. Treat dry runs as strictly read-only and make write scope/confirmation explicit. Use WooCommerce CRUD APIs for order/HPOS compatibility; do not directly update order post tables.

Tests involving real writes, destructive cleanup, subscriptions, payments or emails belong to approved disposable environments. Do not modify production/customer stores or use a Human-owned Local site for destructive automation. Use focused tests and real WooCommerce runtime evidence when behavior requires it; syntax-only green CI is insufficient proof of business behavior.

## Premium-first boundary

Implement one Premium product with supported functionality. Do not create Free stubs, dual-plugin hooks, future takeover/migration mechanisms, WordPress.org distribution or speculative licensing infrastructure. Future Freemium extraction is a separate Human-approved architecture/product task after Premium is mature.

Repository visibility is currently **public**. No proprietary source or secrets should be committed until Human confirms the intended visibility/licensing policy. This warning does not block non-sensitive workflow documentation.

## Release boundary

Merge is not release. A separate approved release task selects a Premium version, verifies actually distributed baseline, stages and tests an installable package and binds hashes to Acceptance. Actual publication/rollback require distinct Human authorization and verified operational capability. Do not claim the existing AAP/YLM publisher or WordPress.org workflow works for this new product without inspection.
