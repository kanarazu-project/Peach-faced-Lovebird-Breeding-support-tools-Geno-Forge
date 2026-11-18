# LLM Software Design Constitution — Compact Core

Before editing code, obey the project design constitution.

Runtime core:
- Read before editing.
- Find the SSOT before creating new facts.
- Preserve responsibility boundaries.
- Preserve dependency direction.
- Make the smallest correct change.
- Keep feature change, refactor, formatting, dependency update, and migration as separate approved changes.
- Treat auth, secrets, payments, migrations, deploys, destructive commands, external services, and production data as approval-required zones.
- Completion requires verification evidence. Treat an unverified success claim as incomplete.

Working sequence:
Audit → Fact Map → Constitution Check → Plan → Implement → Verify → Review → Report → Reflect.
For trivial changes, compress the report, not the discipline.

Stop conditions:
- SSOT not found.
- Boundary unclear.
- Dependency direction unclear.
- State owner unclear.
- Verification entrypoint unconfirmed.
- Dangerous zone touched.
- Same failure repeated twice.
- Required files cannot be read.

Harness placement:
- Keep only this core in CLAUDE.md / AGENTS.md / AGENT.md.
- Move long workflows to Skills.
- Move mandatory checks to hooks, CI, scripts, pre-commit, or review gates.
- Move task plans to PLANS.md / SPEC.md.
- Move full rules to docs/llm-software-design-constitution.md.
- If this file becomes a procedure manual, split it.
- Name every skill in the section "手順（Skill）" below, and register its route in .claude/skill-routes.tsv in the same change.
- Move machine-checkable rules to .claude/rule-lint.tsv in the same change that writes them.

Fact labels:
Use [FACT], [INFER], [ASSUMPTION], [UNVERIFIED], [RISK], [NEEDS APPROVAL] when reporting.

Before changing code, check:
Goal / SSOT / Responsibility / Boundary / Dependency / State / Data / Interface / Error / Idempotency / Auth / Security / Config / Compatibility / Testability / Observability / Simplicity / Minimal Diff / Operations / Learning.

Full constitution:
See docs/llm-software-design-constitution.md.

## このプロジェクトの核（Geno-Forge・SSOT）
- 遺伝の事実と判定規則（座位・アレル・遺伝子型の表記・色の遺伝子型・組換え率・表現と隠蔽・確信度）と、遺伝子型の文字列は genetics.php にだけ置け。
- JS では、index.php が注入した定数（GENOTYPE_DATA・PEDIGREE_DATA・LOCI_MASTER・COLOR_MASTER・RECOMBINATION_RATES など）から遺伝の事実を引け。遺伝子型の中身は genodata.js の GenoData で座位名・アレル名を指定して引け。他の PHP では AgapornisLoci を呼べ。
- JS に遺伝の結果が要るときは、PHP で計算した表を index.php から注入せよ（例: AgapornisLoci::genotypeData()、GenotypeEstimator::pedigreeData()）。
- 個体の記録（デモ・サンプル）は ssot-data:begin 〜 ssot-data:end で囲み、正本の表記で書け。
- 検証入口は `bash scripts/agent-check`（route-check・rule-lint・PHP テスト・血統書の一致検査・SSOT リテラル検査・JS の一致検査・ブラウザ検査）。完了の報告には、その出力と終了コードを添えよ。CI（.github/workflows/verify.yml）が同じ入口を全量で走らせる。クローンしたら `bash scripts/install-hooks` で pre-commit を有効にせよ。

## 文書（docs）
- 遺伝のデータ・判定・注入に触れる作業では、先に docs/ssot.md を読み、そこに書かれた正本の地図と規則に従え。
- 設計判断・危険領域・報告の形では、先に docs/llm-software-design-constitution.md を読み、そこに書かれた規則に従え。
- レビューでは、先に docs/llm-harness-review-rubric.md を読み、そこに書かれた基準に従え。
- ハーネスの失敗が起きたら、docs/harness-corrections.md に1行追記し、実証された教訓だけを規則・テスト・hook・規則表へ昇格せよ。
- 作業計画は docs/PLANS.template.md の形で PLANS.md に書け。

## 手順（Skill）
- 設計監査（設計監査・現状把握・影響調査・design audit）では、先に .claude/skills/design-audit/SKILL.md を読み、その手順に従え。
- 安全なリファクタ（リファクタ・リファクタリング・refactor）では、先に .claude/skills/safe-refactor/SKILL.md を読み、その手順に従え。
- コードレビュー（レビュー・差分・プルリク・review）では、先に .claude/skills/code-review/SKILL.md を読み、その手順に従え。
- 遺伝のデータ・判定の変更（遺伝・座位・アレル・遺伝子型・組換え・SSOT）では、先に .claude/skills/genetics-change/SKILL.md を読み、その手順に従え。

## このファイルの保ち方
- Skill・サブエージェント・docs に手順や規則を置いたら、このファイルに「〈条件〉では、先に 〈パス〉 を読み、従え。」の1行を必ず足せ。このファイルから名指しされていない手順は、読まれないものとして扱え。
- Skill を足したら、「手順（Skill）」の1行と、.claude/skill-routes.tsv の「依頼文の正規表現・パス」の1行を、同じ変更の中で足せ。一致した依頼では、hook がその手順の本文を文脈へ直接入れる。
- 「手順（Skill）」の各行の括弧内の語は、.claude/skill-routes.tsv の正規表現でも同じ Skill に一致するように保て。セッションの開始時に `scripts/route-check` が、実在する全 Skill について名指し・登録・一致を照合する。
- 照合が `NG` を出したら、ほかのどの作業よりも先に、食い違っている箇所と修正案を報告し、直せ。登録か条件が食い違ったままの Skill は、読まれないものとして扱え。
- Skill や docs に「すべての〜に…を付けよ」「〜を使うな」のように機械で判定できる規則を書いたら、同じ変更の中で .claude/rule-lint.tsv に1行足せ。規則表に載っていない規則は、守られている保証がないものとして扱え。
- 規則の意図した例外は、該当行かその直前の1行に `lint-allow:<id>` と理由を書け。
- 手順の本文はここに書かず、Skill か docs に置け。ここに置くのは、核と、名指しの1行だけにせよ。
