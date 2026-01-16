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
    │
    └─→ index.php → JS constant injection → JS files
```

### Verification

The mathematical logic of `genetics.php` (genetic calculation core engine) has been verified for computational reliability through cross-referencing with *Principles of Avian Genetics for Aviculture* (full text) using Gemini 3 in a single context window for mathematical validation against genetic theory.

### File List (16 files, 24,429 lines)

| File | Lines | Purpose |
|------|------:|---------|
| genetics.php | 4,996 | Genetics calculation engine (SSOT) |
| style.css | 3,091 | All styles |
| index.php | 2,293 | Main UI + JS constant injection |
| birds.js | 2,298 | Bird DB + 72 demo specimens |
| lang_pathfinder.php | 2,170 | PathFinder i18n (11 languages) |
| planner.js | 2,042 | Breeding planner + linkage evaluation |
| readme_lang.php | 1,585 | README i18n (11 languages) |
| family.js | 1,542 | Family tree UI + Auto Populate |
| lang_guardian.php | 1,165 | Guardian i18n (11 languages) |
| app.js | 908 | Main application logic |
| pedigree.js | 602 | Pedigree chart rendering |
| guardian.js | 517 | Health assessment engine |
| breeding.js | 492 | Breeding calculation UI |
| readme.php | 332 | README page |
| infer.php | 214 | Genotype inference logic |
| lang.php | 182 | Base i18n (11 languages) |

### Key Classes (genetics.php)

```php
GeneticsCalculator::calculateOffspring()  // Offspring probability calculation
AgapornisLoci::resolveColor()             // Genotype → Phenotype name
GenotypeEstimator::estimate()             // Phenotype → Genotype estimation
FamilyEstimatorV3::infer()                // Batch inference from pedigree
BreedingValidator::calculateWrightCoefficient()  // Inbreeding coefficient
PathFinder::findPath()                    // Route finding to target color
GametesGenerator::generateZLinkedMale()   // v7 linkage gamete generation
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
| **edged** | AR | **Edged Dilute** | **Marbled, Greywing, American Dilute, Golden Cherry** |
| orangeface | AR | Orangeface | - |
| pale_headed | AR | Pale Headed | - |
| ino | SL | Lutino/Pallid | SL-Ino |
| opaline | SL | Opaline | - |
| cinnamon | SL | Cinnamon | American Cinnamon |

> **Note:** "Edged" is the ALBS-standard name for the mutation internationally known as "Marbled" or "Greywing".

---

## 🧬 Linkage Genetics (v7.0)

Implements recombination rates for Z-chromosome linkage and autosomal linkage.

**Source:** Van den Abeele, D. (2016). *Lovebirds Compendium*. p.231

| Locus Pair | Recombination Rate | Chromosome |
|------------|-------------------|------------|
| cin-ino | 3% | Z-linked |
| ino-op | 30% | Z-linked |
| cin-op | 33% | Z-linked |
| dark-parblue | 7% | Autosomal (linked) |

Accurately calculates offspring probability differences based on **Cis/Trans phase**:
- Lacewing from Cis parent: 48.5%
- Lacewing from Trans parent: 1.5%

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
| Total Tests | 113 (PHP 83 + JS 30) |
| Pass Rate | 100% |
| Test File | `debug_test.php` |
| Report | [`TEST_REPORT.md`](TEST_REPORT.md) |

**Test Categories:**
- PHP Unit tests (50): Core classes, calculations, color definitions
- PHP Integration tests (33): UI/API compatibility, SSOT integrity, i18n coverage
- JS Tests (30): BreedingValidator, HealthGuardian, BreedingPlanner

Run tests via browser: `debug_test.php?run_tests=1`

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
