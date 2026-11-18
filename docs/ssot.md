# SSOT（Single Source of Truth）— Geno-Forge

遺伝の事実と判定規則の正本は `genetics.php` だけに置く。JS は `index.php` が注入した定数を読むだけにする。
原則の初出は README.md「File Structure and SSOT」。この文書はその詳細と、守らせる仕組みの一覧である。

## 1. 正本と注入の経路

```
genetics.php（正本）
  AgapornisLoci        LOCI / COLOR_DEFINITIONS / GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI /
                       LINKAGE_GROUPS / RECOMBINATION_RATES / INDEPENDENT_LOCI / COLOR_PART_LABELS /
                       splitGenotype() / wildAllele() / isExpressedAlleles() / resolveDarkGenotype()
  GeneticsCalculator   子の確率
  FamilyEstimatorV3    家系推定
  GenotypeEstimator    表現型からの推定・確信度（phenotypeConfidence）・ACADEMIC_NOTATION・pedigreeData()
  PathFinder           目標までの経路・最短世代数
      │
      └─ index.php（const X = <?= json_encode(...) ?>）→ JS 定数 → *.js
```

| JS 定数 | 正本 |
|---|---|
| LOCI_MASTER | AgapornisLoci::LOCI |
| COLOR_MASTER | AgapornisLoci::COLOR_DEFINITIONS |
| COLOR_LABELS / LABEL_TO_KEY / COLOR_GROUPED / CATEGORY_LABELS / COLOR_PART_LABELS | AgapornisLoci の各メソッド・定数 |
| GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI | AgapornisLoci::GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI |
| LINKAGE_GROUPS / RECOMBINATION_RATES / INDEPENDENT_LOCI | AgapornisLoci の同名定数 |
| PEDIGREE_DATA | GenotypeEstimator::pedigreeData()（座位順・伴性座位・野生型・学術表記・確信度表） |

## 2. JS に置いてよいもの

- 描画、入力の受け取り、表引き。
- デモ用の個体データ（birds.js の個体の遺伝子型）。データであって規則ではない。

遺伝の規則（どの遺伝子型が表現するか、何が何を隠すか、表記の読み替え、率の数字）は、JS には書かず、PHP で計算した結果を注入して引く。

## 3. 守らせる仕組み

| 層 | 仕組み | 対象 |
|---|---|---|
| 手順 | `.claude/skills/genetics-change/SKILL.md`（CLAUDE.md から名指し・経路表に登録） | 遺伝のデータ・判定を変える作業 |
| 規則表 | `.claude/rule-lint.tsv` の ped-geno-literal / js-rate-fallback / inject-* | pedigree.js の遺伝子型リテラル、JS の率の直書き、注入の欠落 |
| 一致検査 | `scripts/pedigree-check` | pedigree.js の表引き結果と PHP の phenotypeConfidence が、全色 × 性別 × 座位 × 濃さ欄で一致すること |
| 回帰テスト | `scripts/php-test`（debug_test.php） | 計算・推定の挙動 |
| 入口 | `scripts/agent-check` | 上の検査をまとめて実行 |

## 4. 残っている二重化の候補（未仕分け）

2026-11-18 時点で、pedigree.js 以外の JS にも遺伝子型の文字列がある（件数は `grep` による出現数）。
規則表の ped-geno-literal を広げる前に、1件ずつ「データ（許可）」「規則の写し（PHP へ移す）」に仕分ける。

| ファイル | 出現数 | 見立て |
|---|---|---|
| birds.js | 523 | 大半はデモ個体のデータ [INFER] |
| planner.js | 160 | TARGET_REQUIREMENTS が COLOR_DEFINITIONS の写しの可能性 [UNVERIFIED] |
| family.js | 54 | 未調査 |
| app.js | 30 | 未調査 |
| guardian.js | 11 | 未調査 |
| breeding.js | 9 | 未調査 |
