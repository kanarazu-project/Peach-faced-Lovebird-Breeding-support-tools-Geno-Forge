---
name: safe-refactor
description: Use for a small behavior-preserving refactor, after the SSOT, tests, and boundaries are understood.
---

# Safe Refactor Skill

## Preconditions
- Goal is behavior-preserving.
- Existing behavior is understood.
- Verification entrypoint exists or limitation is reported.
- Dangerous zones require approval before touching.
- Limit the change to refactoring. Route feature changes, formatting sweeps, dependency updates, and migrations to separate approved tasks.

## Steps
1. State the invariant behavior.
2. Identify the smallest refactor boundary.
3. Make one conceptual change at a time.
4. Preserve public contracts.
5. Run available tests/lint/typecheck.
6. Report changed files and verification evidence.

## Output
- Refactor Goal
- Invariants Preserved
- Changed Files
- Verification Evidence
- Remaining Risks
