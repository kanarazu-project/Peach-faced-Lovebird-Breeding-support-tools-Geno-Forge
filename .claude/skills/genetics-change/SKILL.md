---
name: genetics-change
description: Use when changing genetic data or genetic logic in this simulator — loci, alleles, genotype notation, colour definitions, recombination rates, inheritance, family inference, phenotype confidence, or anything the JS side shows about genetics.
---

# Genetics Change Skill（遺伝のデータ・判定を変える手順）

## Purpose
Keep `genetics.php` the single source of truth (SSOT) for every genetic fact and rule. The full map is `docs/ssot.md`.

## Preconditions
- Read `docs/ssot.md` and locate the SSOT entry for the fact or rule you change.
- Freeze unrelated changes: one genetic change per commit.

## Steps
1. Find the SSOT. Search `genetics.php` (`AgapornisLoci`, `GeneticsCalculator`, `FamilyEstimatorV3`, `GenotypeEstimator`, `PathFinder`) for the existing constant or function. Extend it there.
2. Change `genetics.php` only. Write new genetic facts as constants or functions in `genetics.php`.
3. When JS needs the fact or the result of a rule, expose it from PHP (usually by extending `AgapornisLoci::genotypeData()`, or a precomputed table such as `GenotypeEstimator::pedigreeData()`) and inject it in `index.php` next to the other `const … = <?= json_encode(...) ?>` lines. Inject the same constants into the browser test page in `debug_test.php`.
4. In JS, read the injected constant through `genodata.js` (`GenoData.canonical / isWild / isExpressed / dose / mutantAlleles / wildGenotype / fromAlleles / colorGenotype`), asking by locus and allele names. Keep JS to rendering, input handling and table lookups; write no genotype strings in JS or in PHP files other than `genetics.php`. In other PHP files, call `AgapornisLoci` (e.g. `wildGenotype()`, `canonicalGenotype()`, `GENOTYPE_OPTIONS`).
4a. Bird records (demo or sample data) go inside `ssot-data:begin` / `ssot-data:end` blocks and use canonical genotypes and defined colour keys.
5. Add a regression test to `debug_test.php` that fails on the code before your change, and confirm that it fails there.
6. If the change is visible to users, update `README.md`, `genetics_summary.txt` and the in-app README (`readme.php` + `readme_lang.php`, 11 languages) in the same change.
7. Run `bash scripts/agent-check` (it includes `scripts/ssot-literal-check` and `scripts/js-check`) and keep its output for the report.
8. When the change introduces a new machine-checkable SSOT rule, add one row to `.claude/rule-lint.tsv` in the same change and seed one violation to confirm it fails.

## Output
- SSOT entry changed (file / constant / function)
- Injection points touched (`index.php` lines)
- JS consumers checked
- Regression test (name, and its failing output before the change)
- `scripts/agent-check` output and exit code
- Docs updated
