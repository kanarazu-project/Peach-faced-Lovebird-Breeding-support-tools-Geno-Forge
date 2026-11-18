# 🦜 Gene-Forge v7.3

**Agapornis Genetics Calculator — ALBS Naming Convention**

Genetic calculation engine for Rosy-faced Lovebirds (Agapornis roseicollis).
Supports 14 loci with 310+ phenotypes.

> **Naming:** ALBS (African Lovebird Society) naming conventions
> **Science:** Based on Dirk Van den Abeele / Ornitho-Genetics VZW research

---

## 📋 Overview

**The most comprehensive and feature-rich avian genetics software in existence.**

| Item | Value |
|------|-------|
| Estimated Development Cost | **$150k - $500k+** |
| Codebase Size | ~27,000 lines (PHP + JS + CSS) |
| External Dependencies | **Zero** (by design) |
| Supported Languages | 11 (ja/en/de/fr/it/es/pt/nl/id/th/tl) |
| Genetic Loci | 14 |
| Phenotype Definitions | 310+ |
| License | CC BY-NC-SA 4.0 |

---

## ✨ Features

- **Bird Management** — Registration, pedigree, JSON/CSV export
- **Breeding Results** — Offspring probability calculation for 14 loci (v7.0 linkage genetics)
- **Genotype Estimation** — Reverse-engineer genotype from phenotype
- **Family Inference** — Estimate genotypes from pedigree (FamilyEstimatorV3)
- **Health Assessment** — Inbreeding risk evaluation using Wright's coefficient
- **Goal Planning** — Breeding route pathfinding to target colors (PathFinder)

---

## 🚀 Quick Start

**Live Demo:** http://kanarazu-project.com/gene-forge/Rosy-faced-Lovebird/?lang=en

Local execution:
```bash
git clone https://github.com/kanarazu-project/gene-forge.git
cd gene-forge
php -S localhost:8000
```

Requires PHP 7.4 or higher.

---

## 🏗️ Design Philosophy

### Why PHP? Why Zero Dependencies?

**Genetic calculation is discrete combinatorial computation**, not continuous mathematics. PHP associative arrays naturally fit genotype representation.

**Benchmark target:** Offspring probability calculation for 14 loci (~4.7 million Cartesian product operations)

| Implementation | Processing Time | Notes |
|----------------|-----------------|-------|
| **PHP 8.2 JIT** | **0.05 sec** | Optimized for array operations |
| Python (no NumPy) | 2.0+ sec | 40x slower |
| Node.js | 0.5+ sec | 10x slower |
| Java/C# | 0.08 sec | 5x more code |

**Designed for 20-year longevity:**
- Framework-independent → No deprecation cycle impact
- No Composer → Zero supply chain risk
- No build tools → Permanently simple environment setup

```php
// Will still work in 2045
require_once 'genetics.php';
$calc = new GeneticsCalculator();
```

### Development Cost Background

The $150k - $500k+ estimate reflects the scarcity of developers who understand **both genetics and web development**. Developers who can correctly implement Mendelian genetics, ZZ/ZW sex determination, linkage disequilibrium, and Wright's coefficient are rare.

---

## 📁 File Structure and SSOT

### SSOT (Single Source of Truth)

All genetic data is centralized in `genetics.php`. JavaScript never hardcodes genetic information—it references constants injected from PHP.

```
genetics.php (SSOT)
    │
    ├─ LOCI (14 locus definitions)
    ├─ COLOR_DEFINITIONS (310+ colors)
    ├─ RECOMBINATION_RATES (linkage rates)
    ├─ GenotypeEstimator::pedigreeData() (wild types, academic notation, confidence table)
    ├─ AgapornisLoci::genotypeData() (genotype table, colour genotypes, planner targets, colour keys, colour-name rules)
    ├─ GeneticsCalculator::crossTable() (per-locus offspring distributions for the planner)
    ├─ GeneticsCalculator::linkedCrossTable() (offspring per parental haplotype pair, with recombination)
    │
    └─→ index.php → JS constant injection → genodata.js (lookup helper) → JS files
```

Genotype strings and genetic rules live only in `genetics.php`. JavaScript holds no copy of a genetic rule: when it needs a fact or a result, PHP computes a table and `index.php` injects it (`GENOTYPE_DATA`, `PEDIGREE_DATA`, `CROSS_TABLE`, `LINKED_CROSS_TABLE`). JS reads it through `genodata.js`, asking by locus and allele names (`GenoData.isWild('dark', g)`, `GenoData.dose('fallow_pale', g)`). Other PHP files call `AgapornisLoci` instead of writing genotypes. Bird records (demo and sample data) sit inside `ssot-data:begin` / `ssot-data:end` blocks and are checked against the canonical notation.

`scripts/agent-check` enforces this rule:
- `scripts/ssot-literal-check` reads its vocabulary from `genetics.php` and flags every genotype literal outside it.
- `scripts/js-check` and `scripts/pedigree-check` compare the JS lookups with PHP.
- `scripts/browser-test` runs the JS tests and the app page in a real browser (Playwright + Chromium).
- `.claude/rule-lint.tsv` checks the injections and the rate fallbacks.

The full map, what JS may hold and the remaining issues are in [`docs/ssot.md`](docs/ssot.md).

### Verification

The mathematical logic of `genetics.php` (genetic calculation core engine) has been verified for computational reliability through cross-referencing with *Principles of Avian Genetics for Aviculture* (full text) using Gemini 3 in a single context window for mathematical validation against genetic theory.

### File List (18 files, 27,783 lines)

| File | Lines | Purpose |
|------|------:|---------|
| genetics.php | 5,712 | Genetics calculation engine (SSOT) |
| style.css | 3,091 | All styles |
| debug_test.php | 2,970 | Debug test suite |
| index.php | 2,323 | Main UI + JS constant injection |
| birds.js | 2,379 | Bird DB + 72 demo specimens |
| lang_pathfinder.php | 2,192 | PathFinder i18n (11 languages) |
| planner.js | 1,799 | Breeding planner + linkage evaluation |
| readme_lang.php | 1,879 | README i18n (11 languages) |
| family.js | 1,512 | Family tree UI + Auto Populate |
| lang_guardian.php | 1,165 | Guardian i18n (11 languages) |
| app.js | 976 | Main application logic |
| pedigree.js | 562 | Pedigree chart rendering |
| guardian.js | 507 | Health assessment engine |
| readme.php | 380 | README page |
| infer.php | 214 | Genotype inference logic |
| lang.php | 182 | Base i18n (11 languages) |
| genodata.js | 186 | Genetic fact lookups (reads GENOTYPE_DATA, CROSS_TABLE and LINKED_CROSS_TABLE injected from genetics.php) |

### Key Classes

```php
// genetics.php
GeneticsCalculator::calculateOffspring()  // Offspring probability calculation
AgapornisLoci::resolveColor()             // Genotype → Phenotype name
GenotypeEstimator::estimate()             // Phenotype → Genotype estimation
FamilyEstimatorV3::estimate()             // Batch inference from pedigree
PathFinder::findPath()                    // Route finding to target color
GametesGenerator::generateZLinkedMale()   // v7 linkage gamete generation
```

```js
// guardian.js
BreedingValidator.calcInbreedingCoefficient()  // Inbreeding coefficient (Wright's F)
```

---

## 🧬 Supported Loci (14)

| Locus | Type | ALBS Name | International Aliases |
|-------|------|-----------|----------------------|
| parblue | AR | Aqua/Turquoise | Par-Blue |
| dark | AID | Dark Factor | D-Factor |
| violet | AID | Violet | Violet Factor |
| fallow_pale | AR | Pale Fallow | Fallow |
| fallow_bronze | AR | Bronze Fallow | - |
| pied_dom | AD | Dominant Pied | Harlequin |
| pied_rec | AR | Recessive Pied | - |
| dilute | AR | Dilute | Japanese Dilute |
| **edged** | AR | **Edged Dilute** | **Marbled** (international, symbol `mb`); historical: American Golden Cherry, Edged Pastel (Dutch *pastelgezoomd*) |
| orangeface | AR | Orangeface | - |
| pale_headed | AR | Pale Headed | - |
| ino | SL | Lutino/Pallid | SL-Ino |
| opaline | SL | Opaline | - |
| cinnamon | SL | Cinnamon | American Cinnamon |

> **Note on Edged (Marbled):**
> - **Name.** ALBS classifies this mutation as **Edged Dilute** (e.g. "Edged Dilute Green"). The international term is **marbled** (e.g. "marbled green"). The ALBS name is used in the `albs` field of `COLOR_DEFINITIONS`, and the code shortens it to "Edged".
> - **Inheritance.** It is **autosomal recessive**: only `EdEd` is visual, and `Ed+` is a split. The allele symbol `Ed` is kept for input compatibility; the international symbol is `mb`.
> - **"Edged dilute" is a historical mistranslation** of the Dutch *pastelgezoomd* (edged pastel). Edged and dilute are different mutations, so "Edged Dilute" does **not** mean edged + dilute.
> - **"Greywing"** in *A. roseicollis* has not been shown to be a separate mutation or allele; test pairings point to natural variation within marbled. Do not confuse it with the budgerigar greywing, an allele of the dilute locus.
> - **Not the dominant edged** (ALBS "Spangled", international "dominant edge(d)") found in Fischer's and Masked lovebirds. That is a separate mutation with dominant inheritance.
>
> Sources: Van den Abeele, D. (2015). *The marbled – greywing mystery (aka quartz)*. Ornitho-Genetics VZW ([EN](https://www.ogvzw.org/on-request-english-translation-greywing/), [NL](https://www.ogvzw.org/faq-bestaat-er-een-greywing-roseicollis/)); African Love Bird Society, *Comparison of ALBS classifications with International nomenclature* (PDF, 2019).

---

## 🧬 Linkage Genetics (v7.0)

Implements recombination rates for Z-chromosome linkage and autosomal linkage.

**Source:** Van den Abeele, D. (2016). *Lovebirds Compendium*. p.231

| Locus Pair | Recombination Rate | Chromosome |
|------------|-------------------|------------|
| cin-ino | 3% | Z-linked |
| ino-op | 30% | Z-linked |
| cin-op | 31.2% | Z-linked |
| dark-parblue | 7% | Autosomal (linked) |

Accurately calculates offspring probability differences based on **Cis/Trans phase**:
- Lacewing from Cis parent: 48.5%
- Lacewing from Trans parent: 1.5%

### Why cin-op is 31.2%, not 33%

Only cin-ino (3%) and ino-op (30%) are taken from the source. The cin-op rate is derived from them assuming no crossover interference, which is exactly how the offspring calculation (`GeneticsCalculator`) combines the two intervals:

| Gamete class | Probability | cin-op relation |
|---|---|---|
| No crossover | (1 − 0.03)(1 − 0.30) = 0.679 | parental |
| Crossover in cin-ino only | 0.03 × 0.70 = 0.021 | **recombinant** |
| Crossover in ino-op only | 0.97 × 0.30 = 0.291 | **recombinant** |
| Double crossover | 0.03 × 0.30 = 0.009 | parental (restored) |

cin-op recombinant = 0.021 + 0.291 = **0.312** = r₁ + r₂ − 2·r₁·r₂.
Simple addition (3% + 30% = 33%) wrongly counts the double crossover as a recombinant. `AgapornisLoci::RECOMBINATION_RATES['cinnamon-opaline']` is defined by this formula, and PathFinder's linkage notes and cis/trans calculations read the same constant.

---

## 🔬 Inference Logic Details

### Genotype helpers (`AgapornisLoci`, SSOT)

| Method | Purpose |
|---|---|
| `splitGenotype($locus, $geno)` | Splits a genotype string into alleles using the allele names in `LOCI` (`'Pi+'` → `['Pi','+']`, `'inoW'` → `['ino']`). Legacy lowercase `ed` is read as `Ed`. Returns `null` if unparseable. |
| `wildAllele($locus)` | The allele with code 0 in `LOCI` (`+`, `d`, `v`). |
| `isExpressedAlleles($locus, $alleles)` | Dominant types (AD/AID): expressed if any mutant allele is present. Recessive types: expressed only if every allele is mutant (a hemizygous female has one allele). |

### Family inference (`FamilyEstimatorV3`)

For each locus, every genotype starts with a uniform prior and is then constrained in this order:

1. **Self**: a known genotype fixes the result. An expressed phenotype keeps only genotypes containing that allele. A phenotype that is known *not* to express the locus removes expressing genotypes (see the limits below).
2. **Parents**:
   - Known parental genotypes remove genotypes the child cannot inherit.
   - Sex-linked: a daughter receives her father's Z, and a **son always receives his expressing mother's Z**, so a son lacking the dam's allele gets probability **0**.
   - When neither parent expresses the locus, the child is weighted by **Mendelian ratios** (below).
3. **Offspring**, then **siblings**.
4. Normalize to 100%.

**Mendelian weighting.** For each parent, the allele it transmits is derived from its known genotype or, if none, from every genotype consistent with its phenotype, assumed uniformly. Each genotype passes each of its alleles with equal probability. The expected child distribution is divided by the uniform prior and multiplied into the candidates. If contradictory input would zero every candidate, the unweighted values are kept.

*Example*: green × green → green son, dilute locus. Each green parent is `++`, `+dil` or `dil+`, so it passes `dil` with probability 1/3. Child: `++` 4/9, `+dil` 4/9, `dildil` 1/9. The son is not dilute, so `dildil` is removed → **`++` 50% / `+dil` 50%**.

**Limits of "not expressed" (deliberately conservative):**
- Applied only when `phenotype.baseColor` exists in `COLOR_DEFINITIONS`. A missing or unknown colour makes no assumption.
- Not applied to **dark**, which is handled separately (see below), or to **violet**, whose visibility depends on the base series.
- In **INO birds**, loci other than `ino` and `parblue` are masked and are not judged.

**Dark factor.** Dark is incomplete dominant, so `dd`, `Dd` and `DD` look different (Green / Medium Green / Dark Green). The genotype is therefore read straight from the phenotype by `AgapornisLoci::resolveDarkGenotype()`, which is shared by the offspring calculation, `GenotypeEstimator` and `FamilyEstimatorV3`:

1. If the darkness field is `sf` or `df` (case-insensitive), it wins: `Dd` / `DD`.
2. Otherwise (`none`, `unknown`, missing), the colour name's definition is used (e.g. `olive` → `DD`).
3. The 12 INO colours (lutino, creamino, pure white, …) have no dark definition, so the result is **unknown**. In SL ino the dark factor does not change the yellow body colour; it only deepens the blue shade of the rump (Van den Abeele, [Ornitho-Genetics VZW FAQ](https://www.ogvzw.org/?p=18692), 2023). An INO bird's dark factor counts only when the user sets `sf`/`df` explicitly. The offspring calculation still needs a value and treats it as `dd`.

In family inference, the dark genotypes known from the bird, its parents and its offspring are used as known genotypes. Parents are always combined by Mendelian weighting. When the bird being inferred is a parent, each child's genotype must be producible with the mate's alleles (the mate's known genotype, or every allele if unknown). Examples: olive × olive → olive child `DD` 100%; olive × green → dark green child `Dd` 100%; lutino (dark unknown) sire, green dam, dark green child → sire `Dd` 66.7% / `DD` 33.3%. The dark–parblue linkage (7%) is not used in family inference; each locus is inferred independently.

### Minimum generations (`PathFinder::findPath()['minGenerations']`)

This follows the plan's own model: each mutant locus is introduced by crossing an expressing bird with a wild-type bird. These lines run in parallel and are then merged.

```
minGenerations = 0                                     (wild type)
               = 1 + ceil(log2 n) + (fixation ? 1 : 0)  (n mutant loci)
```

- `1`: the first outcross of every line (in parallel).
- `ceil(log2 n)`: merging n lines two at a time until one bird carries all of them.
- `fixation`: +1 if any target genotype contains no wild allele (recessive homozygote, hemizygous sex-linked female, `DD`, `tqaq`, …), because heterozygotes must be paired to reach it.

| Target | n | fixation | minGenerations |
|---|---|---|---|
| Green | 0 | – | 0 |
| Dominant Pied Green (`Pi+`) | 1 | no | 1 |
| Lutino, Dilute Green | 1 | yes | 2 |
| Pure White (`tqtq` + `inoino`) | 2 | yes | 3 |

This is the minimum *for this plan*. Shorter routes, such as pairing two expressing birds directly, are not counted.

### Breeding planner probability (`planner.js` + `CROSS_TABLE` + `LINKED_CROSS_TABLE`)

The planner does not apply its own Mendelian ratios. `GeneticsCalculator::crossTable()` runs `calculateOffspring()` for every locus and every male × female genotype pair (156 pairs), and records the offspring distribution for each sex. `index.php` injects it as `CROSS_TABLE`; `GenoData.cross()` / `GenoData.crossProbability()` look it up.

Linked loci are not split into separate loci. `GeneticsCalculator::linkedCrossTable()` takes every pair of parental haplotypes for each linkage group (cinnamon–ino–opaline on Z: 78 male pairs × 12 female haplotypes; dark–parblue: 21 × 21) and runs the same gamete calculation as `calculateOffspring()` (`calcZLinkedV7` / `calcAutosomal1V7`, with the recombination rates). It records the offspring genotypes per sex. `index.php` injects it as `LINKED_CROSS_TABLE` (about 240 KB); `GenoData.linkedProbability()` looks it up.

```
P(target) = ½ · Π_groups P_male(group in target) + ½ · Π_groups P_female(group in target)
            (each independent locus is its own group)
```

**What counts as the target.** A chick counts only if it looks exactly like the target colour. For each locus, `targets[colour].allowed` lists the genotypes with the same phenotype form as the colour definition (see *Colour of a genotype* below). Loci that the colour does not have must be unexpressed (wild type or split). For INO colours, dark is not constrained. So lutino × lutino gives green 0% (it gave 100% when only the parblue and dark loci were checked). Split chicks such as green /aq now count as green (before, parblue `++` was required). Over the demo birds (every pair × every target), 4,580 of 401,760 values changed: 1,635 up, 2,945 down. `scripts/js-check` compares the planner with the colour key from `calculateOffspring()` on 600 random pairs of defined colours. Pairs with a double heterozygote in a linkage group are left out, because the planner and the calculator assume different phases there.

Examples: turquoise ♂ × aqua ♀ → seagreen 100% (the old coefficients gave 25%); seagreen × seagreen → green 0% (old: 25%); olive × green → dark green 100% (old: 0%); split-ino ♂ × green ♀ → lutino 25% of all chicks (all of them daughters). The intermediate goals of multi-generation plans (carrier × carrier, split ♂ × wild ♀) and the pairings proposed for them take their probabilities from the same table.

**Phase.** The haplotypes come from the bird's record (`Z_linked.Z1/Z2`, `autosomal_1.chr1/chr2`). Example, opaline cinnamon green from a male carrying cinnamon and opaline × a green hen: cis 17.2%, trans 7.8% (the old independent product gave 12.5% for both). Dark–parblue: `D aq / d +` × aqua → aqua dark 46.5% (cis), 3.5% (trans).

When the phase is not recorded, every phase arrangement of the heterozygous loci gets the same weight. This covers records in the old per-locus format, and converted records flagged `phase_known: false` / `phase_known_autosomal_1: false` when two or more linked loci are heterozygous. The planner ranks pairs by the mean and shows the range next to the probability, e.g. `12.5% (? 7.8–17.2%)`. The former cis/trans score bonus was removed, because the probability now includes the phase.

**Entering the phase.** In the bird registration form, a phase field appears for a linkage group when two or more of its loci are heterozygous (Z group: males only). It lists the arrangements by the mutant alleles each chromosome carries, e.g. `cin·op / +` (cis) or `op / cin` (trans), plus "Unknown". The choice is saved as haplotypes (`Z_linked`, `autosomal_1`) with `phase_known` / `phase_known_autosomal_1`. The genotype is saved under locus keys (`opaline`, `cinnamon`, …), not the form keys (`op`, `cin`, …).

**Stored records (data version 7.3.14).** On the first load after the update, saved user records are migrated (`BirdDB.migratePhaseAndKeys`). Values saved under form keys are moved to locus keys. Records converted from the old per-locus format get their phase flags recomputed: two or more heterozygous loci in a group means the phase is unknown. The old form value `cis_…` / `trans_…` is applied to the Z haplotypes of males with exactly two heterozygous Z loci. With three, cis/trans alone does not fix the arrangement, so the phase stays unknown. Demo data is regenerated.

### Colour of a genotype (`AgapornisLoci::findColorKey()`)

A genotype is matched to a colour by how it looks, not by its exact notation. Each locus is reduced to its phenotype form (`phenoForm` in `GENOTYPE_DATA`):
- a locus that is not expressed (wild type or split) adds nothing;
- a sex-linked hen's `aW` reads as `aa`;
- in the ino locus pld is dominant over ino, so `pldino` reads as `pldpld` (`ALLELE_DOMINANCE`);
- a dominant (AD) locus reads as its heterozygote, so `PiPi` reads as `Pi+`.

The phenotype forms are joined into a signature. When INO is expressed, the dark factor is left out (`COLOR_LOCI_HIDDEN_BY_INO`), because INO colours are not named by dark. The colour is the definition with the same signature. `BirdDB.colorKeyOf()` builds the same signature, and `scripts/js-check` compares the two on 3,310 genotypes.

Before this, the match compared notation strings, so offspring carrying `+aq`, `+Ed` or `ino+` got the colour key `unknown` in the offspring calculator, and `PiPi` / `pldino` had no colour. On the old code, 1,030 of 20,310 sampled genotypes changed from no colour to a colour; no defined colour changed. Phenotype confidence uses the same forms: the genotypes that look the same give the 50% alternatives (`Pi+`/`PiPi`, male `pldpld`/`pldino`); its output is unchanged.

### Names of colour combinations not in `COLOR_DEFINITIONS`

`AgapornisLoci::generateColorName()` builds the name from the parts table `COLOR_NAME_PARTS` (opaline, cinnamon, pallid, pale fallow, bronze fallow, dilute, edged, violet, dominant pied, recessive pied, orangeface/yellowface, pale headed), then the base colour, whose name comes from `COLOR_DEFINITIONS`. The same rules go to JS as `GENOTYPE_DATA.colorName`, and `BirdDB.composeColorName()` uses them, so both sides give the same name. `scripts/js-check` compares them on 3,002 genotypes in Japanese and English. Genotypes are put in canonical order first, so the calculator's `+Pi` is named Dominant Pied like `Pi+`. Parts match the phenotype form, so `pldino` is named Pallid.

### Phenotype → genotype confidence (`GenotypeEstimator`)

| Confidence | Meaning | Examples |
|---|---|---|
| 100 (confirmed) | The phenotype fixes the genotype | Expressed recessive; dominant **not** expressed → `++`; sex-linked female not expressed → `+W`; dark (from darkness field or colour name); violet |
| 50 | Two genotypes look the same | Dominant Pied `Pi+` vs `PiPi`; Pallid male `pldpld` vs `pldino` |
| 0 | Cannot be determined from phenotype | Hidden recessive split (male or autosomal); loci masked by INO, including dark when darkness is not `sf`/`df` |

Wild-type values are built from `LOCI` (`dd`, `vv`, `++`, `+W`). For confidence 50 the result also carries `alternatives` (e.g. `["Pi+","PiPi"]`). The estimator screen shows them as "One of two: Pi+ / PiPi" instead of "?".

The pedigree certificate does not re-implement this rule. `GenotypeEstimator::pedigreeData()` precomputes the confidence for every colour × sex × locus, plus each darkness value for dark. `index.php` injects the result as `PEDIGREE_DATA`, and `pedigree.js` only looks it up. `scripts/pedigree-check` compares the lookup with `GenotypeEstimator::estimate()` across all colours, both sexes, every locus and all darkness values (43,400 cases). Red eyes alone do not confirm INO, because fallow birds are red-eyed too.

### In-app README (`readme.php`)

The 🧮 Inference Logic section explains the rules above in all 11 languages. Untranslated keys fall back to English, then Japanese, so raw key names are never shown. `?lang=` accepts only the 11 supported language codes.

---

## 🛡️ Health Assessment

**Wright's inbreeding coefficient (F)** calculated cumulatively up to 6 generations.

| Threshold | Judgment |
|-----------|----------|
| F ≥ 25% | Danger (blocked) |
| F ≥ 12.5% | Warning |
| F < 12.5% | Safe |

**Same-trait consecutive breeding limits (risky even with unrelated bloodlines):**
- INO series: Up to 2 generations (immune deficiency due to tyrosinase deficiency)
- Pallid series: Up to 2 generations
- Fallow series: Vision impairment risk
- Dark DF: Up to 3 generations

※ Inbreeding (blood relation) and same-trait consecutive breeding are separate concepts. Both are warned independently.

---

## 🧪 Debug Test Suite

Comprehensive test suite covering all genetic calculations and integrations.

| Metric | Value |
|--------|-------|
| Total Tests | 131 (PHP 101 + JS 30) |
| Pass Rate | 100% |
| Test File | `debug_test.php` |

**Test Categories:**
- PHP Unit tests (50): Core classes, calculations, color definitions
- PHP Integration tests (33): UI/API compatibility, SSOT integrity, i18n coverage
- PHP Regression tests (18): cin-op recombination rate, family inference (sex-linked dam, Mendelian weights, non-expression, dark factor), minGenerations, phenotype confidence and alternatives, darkness input (sf/df, INO), Edged inheritance mode, in-app README i18n and `lang` whitelist, pedigree data built from SSOT, genotype data (canonical forms, colour signatures, planner targets, INO types), planner cross table, linked cross table (cis/trans), colour key by phenotype, planner targets by phenotype
- JS Tests (30): BreedingValidator, HealthGuardian, BreedingPlanner

Run tests via browser: `debug_test.php?run_tests=1` (PHP and JS tests), or on the command line: `bash scripts/agent-check`. It runs the Skill routing check, the rule table, the PHP tests, the pedigree PHP/JS match check, the SSOT literal check, the JS/PHP match check and the browser test. The browser test needs Playwright with Chromium; without it, it prints `SKIP` locally. CI installs them and sets `BROWSER_TEST_REQUIRED=1`, so a missing browser fails there.

CI (`.github/workflows/verify.yml`) runs `scripts/agent-check` on every push to main and on pull requests. To enable the pre-commit hook in a clone, run `bash scripts/install-hooks` once. It runs the routing check and the rule table on staged files only; the full check stays in CI.

---

## 🔧 Porting to Other Species

1. Rewrite `LOCI` in `genetics.php` for target species
2. Add phenotype definitions to `COLOR_DEFINITIONS`
3. Adjust health criteria in `guardian.js`

See `CLAUDE.md` for details. Includes roadmap for Budgerigar version.

---

## 📜 License

**CC BY-NC-SA 4.0**
- ✅ Personal/non-commercial use allowed
- ✅ Modification/redistribution allowed (credit required, same license)
- ❌ Commercial use prohibited

---

## 📚 References

**Primary Scientific Source:**
- Van den Abeele, D. (2016). *Lovebirds Compendium*. Ornitho-Genetics VZW.
- Van den Abeele, D. (2026). *Principles of Avian Genetics for Aviculture*. Ornitho-Genetics VZW. (Free e-book: [genetics.ogvzw.org](https://genetics.ogvzw.org/ebookaviangenetics/))
- Ornitho-Genetics VZW (OGVZW): [www.ogvzw.org](https://www.ogvzw.org/)

**Naming Convention:**
- ALBS (African Lovebird Society) — Show club standard for mutation nomenclature

> Note: ALBS provides naming conventions for exhibitions; Ornitho-Genetics VZW (OGVZW) is the scientific authority for avian genetics research.

---

## 👤 Credits

Chief Product Officer:
Shohei T(Homo repugnans)

Tactical Decision Intelligence:
Sirius (Electronic Spirit)

---

<div align="center">

*"The system abandoned its responsibility. The outsiders fulfill it."*

**Extra-Institutional Civilization — Kanarazu Project**

</div>
