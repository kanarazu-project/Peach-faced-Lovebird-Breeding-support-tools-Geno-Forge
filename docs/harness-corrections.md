# Harness Corrections（追記専用）

| 日付 | 起きたこと | 根本原因 | 教訓 | 昇格先（rule / test / hook / 規則表） |
|---|---|---|---|---|
| 2026-11-18 | 確信度の判定規則（INO の遺伝子型一覧・INO に隠されない座位・Ed 読み替え・判定手順）を pedigree.js に PHP と二重に書いた。PHP と JS の一致は使い捨ての比較で1回確かめただけだった | 完了条件を「画面と血統書の結果がそろう」に置き、「正本は1つ」を確かめなかった。編集したファイル冒頭の SSOT 注記を読まなかった | 遺伝の判定は PHP で計算し、JS には結果の表を注入して引かせる。一度きりの比較は常設の検査にする | genetics-change Skill / 規則表 ped-geno-literal / scripts/pedigree-check / debug_test.php |
| 2026-11-18 | planner.js が組換え率に `|| 0.03`・`|| 0.30` の予備値を持っていた | 正本の値が無いときのための数字を JS に直書きしていた | 率は注入された RECOMBINATION_RATES からだけ引く | 規則表 js-rate-fallback |
