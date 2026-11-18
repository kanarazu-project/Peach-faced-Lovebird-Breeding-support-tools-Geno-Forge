# LLM Harness Review Rubric

A review finding is valid only if it is tied to correctness, maintainability, security, compatibility, explicit requirements, or project rules.

## Required checks
- SSOT was preserved.
- Responsibilities remain separated.
- Boundaries and contracts remain clear.
- Dependency direction remains forward.
- State ownership remains clear.
- Error paths are handled or explicitly out of scope.
- Auth/security/config/secrets remain at least as strong as before.
- Public contracts and existing data remain compatible.
- Tests or verification evidence exist.
- Diff is minimal and purpose-bound.

## Finding format
- File / symbol / behavior:
- Evidence:
- Risk:
- Required fix:
- Verification:

Report only evidence-backed findings. When evidence is insufficient, mark the item as hypothesis or report no finding.

## Project checks (Geno-Forge)
- Every genetic fact or rule touched by the diff lives in `genetics.php` (see `docs/ssot.md`).
- JS reads injected constants (`LOCI_MASTER`, `COLOR_MASTER`, `RECOMBINATION_RATES`, `PEDIGREE_DATA`, …) and holds no copy of a genetic rule.
- A behaviour change has a regression test in `debug_test.php` that fails on the previous code.
- User-visible changes update README.md, genetics_summary.txt and the in-app README (11 languages).
