---
name: code-review
description: Use to review a diff for correctness, regressions, missing tests, design boundary violations, security risks, and documentation drift.
---

# Code Review Skill

## Mode
Operate read-only. Leave all files unchanged.

## Steps
1. Identify changed behavior, not only changed files.
2. Check SSOT, responsibility, boundary, dependency, state, error handling, auth, security, compatibility, tests, and observability.
3. Separate confirmed findings from hypotheses.
4. Report style-only issues only when they hide a correctness or maintainability problem.
5. Require evidence for each finding.

## Output
- Summary
- Required Findings
- Optional Improvements
- Missing Verification
- Approval Risks
