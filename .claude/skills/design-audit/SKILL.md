---
name: design-audit
description: Use before implementation, when the SSOT, boundaries, dependency direction, state, risk, or verification entrypoints of a codebase or change request are unclear.
---

# Design Audit Skill

## Purpose
Find the project facts required before implementation.

## Mode
Operate read-only. Leave all files unchanged.

## Steps
1. Identify the user goal.
2. List relevant files and directories.
3. Locate SSOT for data, config, types, validation, auth, state, and contracts.
4. Map responsibilities and boundaries.
5. Map dependency direction.
6. Identify state owners and update paths.
7. Identify verification entrypoints.
8. Mark risks and approval-required zones.
9. Output an Audit Report and Minimal Plan.

## Output
- Goal
- Fact Map
- SSOT Map
- Boundary Map
- Dependency Map
- State Map
- Verification Entrypoints
- Risks / Needs Approval
- Minimal Plan
