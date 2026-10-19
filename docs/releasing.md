# WooCommerce Advanced Tools — Premium release policy

**This is a release boundary, not a working release publisher.** Product code, plugin bootstrap, packaging scripts, licensing/updater channel and distribution mapping do not yet exist. No release may be inferred from this document, CI PASS, task Acceptance or a merged PR.

## Distribution model

Advanced Tools is initially a standalone commercial/Premium WooCommerce plugin. Do **not** run WordPress.org SVN publication, build a Free/Core ZIP, implement premium takeover, or package source from unrelated YoOhw plugins. A future Free/Freemium split is a separate product and compatibility decision after the Premium version is established.

The repository was public at workflow creation. Human must decide repository visibility and code licensing before committing proprietary source or any confidential delivery configuration. Never put credentials, customer data, private signing secrets or production connection metadata into source, issues, Actions artifacts or release notes.

## Preparation is a separate task

Only a Human-selected Premium release/version authorizes opening a dedicated AT-N release-preparation Issue. No implementation task implicitly bumps versions or publishes files. On preparation:

1. Confirm the intended version, slug and main plugin file from the **actual** product source; verify version uniqueness against existing tags, GitHub Releases, previously published/downloaded packages and available delivery records. Do not infer what customers received from `main` alone.
2. Establish the previous **actually distributed stable** baseline. Summarize only net customer-visible changes from that baseline, not internal task or review iterations.
3. Freeze the accepted source, stage exactly the installable `wc-advanced-tools/` plugin tree from tracked, reviewed files and exclude repository/development files, tests, tooling, backups, credentials, private logs and previous archives.
4. Verify the ZIP paths/content, plugin identity, package size and SHA-256; retain a minimal provenance record of exact source commit/tree, version and staged ZIP hash. If a deterministic build tool becomes useful, develop it in that explicit release task rather than adding a generic packaging framework now.
5. Test the **extracted ZIP** in a disposable WordPress/WooCommerce environment, including activation, relevant admin flows, import/export, bulk-update preview/dry-run and HPOS where implemented. Test upgrade from the last distributed version when applicable. Runtime tests may be proportionate to shipped features; source-level CI is not package certification.
6. Bind final source/version/package hash, customer release notes and actual validation/limitations to the release PR and exact-head Acceptance. Human authorizes its merge separately.

## Publication and rollback

Actual publication is another explicit Human authorization after successful release preparation, Acceptance and merge. Before any write, verify the currently deployed YoOhw distribution mechanism, product identity and permissions. An AAP/YLM product ID, CLI publisher, server path, license scheme or provenance format **must not** be assumed to apply to Advanced Tools.

Publish only the exact accepted ZIP and release notes. Recheck package hash before/after transfer; verify the customer-facing download/updater result and record any mismatch. Do not silently rebuild the artifact after Acceptance or reuse an unreleased support ZIP as stable.

If the distribution mechanism is absent or verification fails, stop with a factual blocker; do not invent a fallback publication channel or modify production to make the task appear successful. Any rollback also requires a distinct Human decision and verified rollback candidate.

Git tags/GitHub Releases, license delivery changes, scheduled publication and production deployment remain explicitly authorized operations, not side effects of CI, `Run AT-N`, `Acceptance Review AT-N` or `Merge AT-N`.
