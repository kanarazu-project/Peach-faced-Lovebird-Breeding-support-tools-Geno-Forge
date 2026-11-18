# Harness Corrections（追記専用）

| 日付 | 起きたこと | 根本原因 | 教訓 | 昇格先（rule / test / hook / 規則表） |
|---|---|---|---|---|
| 2026-11-18 | 確信度の判定規則（INO の遺伝子型一覧・INO に隠されない座位・Ed 読み替え・判定手順）を pedigree.js に PHP と二重に書いた。PHP と JS の一致は使い捨ての比較で1回確かめただけだった | 完了条件を「画面と血統書の結果がそろう」に置き、「正本は1つ」を確かめなかった。編集したファイル冒頭の SSOT 注記を読まなかった | 遺伝の判定は PHP で計算し、JS には結果の表を注入して引かせる。一度きりの比較は常設の検査にする | genetics-change Skill / 規則表 ped-geno-literal / scripts/pedigree-check / debug_test.php |
| 2026-11-18 | planner.js が組換え率に `|| 0.03`・`|| 0.30` の予備値を持っていた | 正本の値が無いときのための数字を JS に直書きしていた | 率は注入された RECOMBINATION_RATES からだけ引く | 規則表 js-rate-fallback |
| 2026-11-18 | SSOT の規則を pedigree.js だけに当てていた。全ファイルを数えると、JS・index.php に遺伝子型リテラルが977件あった | 規則の範囲を段階分けの都合で狭めた。語彙を手書きの正規表現にしていて、英単語 pied を誤検出した | 遺伝子型の記述先は genetics.php だけ。語彙は正本から読み、全ファイルを出現1つずつ検査する。データは ssot-data ブロックで囲み正本と照合する | scripts/ssot-literal-check / .claude/ssot-literal.tsv |
| 2026-11-18 | 写しが正本から外れていた: birds.js の色キー組み立ては正本の310色のうち86色しか正しく返さず、EdEd をエッジドと認識しなかった。family.js はファロー（赤目）を INO とし、ピュアホワイトを aqaq とした。planner.js の目標表は存在しない flfl を使い43色しかなかった | 規則を JS に写した時点から、正本の変更が届かなくなっていた | 規則は PHP で計算した表を注入して引く。写しの一致は常設の検査で見る | GENOTYPE_DATA / genodata.js / scripts/js-check |
| 2026-11-18 | ブラウザ用の繁殖計画テストが「BreedingPlanner が無い」で常に失敗していた | planner.js が window.BreedingPlanner を設定していなかった。ブラウザ用テストは CLI の検査に入っていなかった | ブラウザ用テストも実ブラウザで走らせて確かめる | scripts/browser-test（agent-check・CI。CI は BROWSER_TEST_REQUIRED=1） |
| 2026-11-18 | planner.js が交配の確率を 1.0 / 0.5 / 0.25 の係数で見積もっていた。turquoise × aqua → seagreen を 25%（正しくは 100%）、olive × green → dark green を 0%（正しくは 100%）とした。デモ72羽の全組 × 全目標 401,760 件のうち 10,776 件が PHP の計算と食い違った | 遺伝子型の文字列を消した時点で SSOT 化を完了とし、計算規則の写しを残した | 確率は PHP の calculateOffspring から作った表（CROSS_TABLE）を引く。JS の確率は PHP の計算と常設の検査で照合する | GeneticsCalculator::crossTable() / scripts/js-check / 規則表 inject-cross-table |
