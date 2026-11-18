# SSOT（Single Source of Truth）— Geno-Forge

遺伝の事実と判定規則の正本は `genetics.php` だけに置く。遺伝子型の記述先も `genetics.php` だけである。
JS は `index.php` が注入した定数を表として引くだけにし、PHP の他のファイルは `AgapornisLoci` などの関数を呼ぶ。
原則の初出は README.md「File Structure and SSOT」。この文書はその詳細と、守らせる仕組みの一覧である。

## 1. 正本と注入の経路

```
genetics.php（正本）
  AgapornisLoci        LOCI / COLOR_DEFINITIONS / GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI /
                       LINKAGE_GROUPS / RECOMBINATION_RATES / INDEPENDENT_LOCI / COLOR_PART_LABELS /
                       splitGenotype() / wildAllele() / isExpressedAlleles() / resolveDarkGenotype() /
                       canonicalGenotypes() / canonicalGenotype() / wildGenotype() / genotypeData()
  GeneticsCalculator   子の確率
  FamilyEstimatorV3    家系推定
  GenotypeEstimator    表現型からの推定・確信度（phenotypeConfidence）・ACADEMIC_NOTATION・pedigreeData()
  PathFinder           目標までの経路・最短世代数
      │
      └─ index.php（const X = <?= json_encode(...) ?>）→ JS 定数 → genodata.js（引き出し）→ *.js
```

| JS 定数 | 正本 |
|---|---|
| LOCI_MASTER | AgapornisLoci::LOCI |
| COLOR_MASTER | AgapornisLoci::COLOR_DEFINITIONS |
| COLOR_LABELS / LABEL_TO_KEY / COLOR_GROUPED / CATEGORY_LABELS / COLOR_PART_LABELS | AgapornisLoci の各メソッド・定数 |
| GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI | AgapornisLoci::GENOTYPE_OPTIONS / UI_GENOTYPE_LOCI |
| LINKAGE_GROUPS / RECOMBINATION_RATES / INDEPENDENT_LOCI | AgapornisLoci の同名定数 |
| PEDIGREE_DATA | GenotypeEstimator::pedigreeData()（座位順・伴性座位・野生型・学術表記・確信度表） |
| GENOTYPE_DATA | AgapornisLoci::genotypeData()（下記） |

`GENOTYPE_DATA` の中身（JS は `genodata.js` の `GenoData` から引く）:

| キー | 中身 | 主な利用者 |
|---|---|---|
| loci[座位].genotypes[表記] | canonical / alleles / mutant / dose / wild / expressed / hemi / phenoForm。並び順違い（ino+）と旧表記（eded）も引ける | 全 JS |
| loci[座位].wild / byAlleles | 野生型（オス/メス）、アレルの組 → 正規の遺伝子型 | planner.js, birds.js |
| byColor[色][性別] | 色の定義の遺伝子型（伴性メスはヘミ接合） | family.js（整合性チェックの補完） |
| targets[色] | 繁殖計画の目標（required / slr / tier / minGen は PathFinder / difficulty / inbreedingLimit） | planner.js |
| plannerSlrKeys | 繁殖計画の伴性キー（ino / cin / op）→ 座位 | planner.js |
| inoTypes | パーブルー → INO 色（表示の優先順） | guardian.js |
| colorKey | 定義済みの色の署名 → 色キー（bySignature）と、定義に無い組み合わせの組み立て規則 | birds.js |
| darknessByDark | ダーク因子の遺伝子型 → 濃さ欄の値 | birds.js |

## 2. JS・他の PHP に置いてよいもの

- 描画、入力の受け取り、表引き、座位名・アレル名での問い合わせ（`GenoData.dose('dark', g)` など）。
- 個体の記録（デモの鳥・サンプル家系）。`ssot-data:begin` 〜 `ssot-data:end` で囲み、表記は正本の選択肢に合わせる。
- テストの入力と期待値（`.claude/ssot-literal.tsv` に載せたファイル）。

遺伝子型の文字列（'++' 'inoW' 'EdEd' など）、どの遺伝子型が表現するか、何が何を隠すか、表記の読み替え、率の数字は、
JS にも他の PHP にも書かない。PHP で計算した結果を注入して引くか、`AgapornisLoci` を呼ぶ。

## 3. 守らせる仕組み

| 層 | 仕組み | 対象 |
|---|---|---|
| 手順 | `.claude/skills/genetics-change/SKILL.md`（CLAUDE.md から名指し・経路表に登録） | 遺伝のデータ・判定を変える作業 |
| リテラル検査 | `scripts/ssot-literal-check`（語彙は genetics.php から読む） | genetics.php 以外の *.js / *.php の遺伝子型リテラル（出現1つずつ）。ssot-data ブロックは正本の選択肢・色と照合 |
| 規則表 | `.claude/rule-lint.tsv` の js-rate-fallback / inject-* | JS の率の直書き、注入の欠落 |
| 一致検査 | `scripts/pedigree-check` | pedigree.js の確信度と PHP の phenotypeConfidence（全色 × 性別 × 座位 × 濃さ欄） |
| 一致検査 | `scripts/js-check` | birds.js の色キー（全色 × 性別）、guardian.js の INO 型、planner.js の目標と ino/pld の区別、genodata.js の引き方 |
| 回帰テスト | `scripts/php-test`（debug_test.php） | 計算・推定の挙動 |
| 入口 | `scripts/agent-check` | 上の検査をまとめて実行 |
| CI | `.github/workflows/verify.yml` | push（main）と pull request で `scripts/agent-check` を全量実行 |
| pre-commit | `.githooks/pre-commit`（有効化: `bash scripts/install-hooks`、クローンごとに1回） | route-check と、ステージした変更ファイルへの rule-lint・ssot-literal-check |

## 4. 残っている課題

| 箇所 | 中身 | 見立て |
|---|---|---|
| planner.js の確率の見積もり | ペアの評価で、親の遺伝子型の組み合わせから 1.0 / 0.5 / 0.25 などの係数を掛けている | 遺伝子型の文字列は無くなったが、メンデル比の近似という計算そのものは GeneticsCalculator と二重である。PHP の計算結果を使う形への置き換えは未着手 |
| breeding.js | BreedingEngine はどこからも呼ばれていない（交配計算の写しは削除済み） | ファイルごと削除するかは未判断 |
| birds.js の色キー組み立て（定義に無い組み合わせ） | 部品の並べ方は genetics.php の規則（colorKey.steps）だが、ブロンズファロー・ドミナントパイド・オレンジフェイスの部品が無い | 定義済みの310色は署名で正しく引ける。定義に無い組み合わせの名前は、PHP の generateColorName と揃っていない |
