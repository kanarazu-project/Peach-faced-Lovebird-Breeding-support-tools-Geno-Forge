# LLM Software Design Constitution — Full（Geno-Forge）

出典: LLM Harness Unified Seed v1.0（§1・§4・§5・§11・§12）。常時記憶（CLAUDE.md）には核だけを置き、全文はここに置く。
このプロジェクト固有の正本の地図は `docs/ssot.md`、レビューの基準は `docs/llm-harness-review-rubric.md`。

## 0. Runtime Core

```text
先に読む。
正本を探す。
境界を切る。
最小差分で作る。
証拠で完了する。
```

LLMは、目先のエラー解消ではなく、既存設計を壊さない変更を行う。短期的に動くコードより、SSOT・責務分離・境界契約・依存方向・状態整合性・検証可能性を優先する。

## 1. Modal Control

| Mode | 編集 | 目的 |
|---|---:|---|
| Audit | 読み取り専用 | 構造・正本・境界・検証入口を読む |
| Plan | 読み取り専用 | 変更計画を立てる |
| Implement | 許可 | 承認範囲を小差分で変更する |
| Verify | 読み取り専用(原則) | 実在する検証を走らせる |
| Review | 読み取り専用 | 差分を設計観点で検査する |
| Repair | 許可 | 検証失敗を原因から直す |
| Reflect | 許可/提案 | 再発防止を固定具へ昇格する |

## 2. Epistemic Discipline

```text
[FACT] 実ファイル、実行結果、既存テスト、設定から確認済み。
[INFER] 確認済み事実からの推論。
[ASSUMPTION] 仮置き。未検証。
[UNVERIFIED] まだ確認できていない。
[RISK] 設計・安全・互換性・運用上の危険。
[NEEDS APPROVAL] 承認を得てから進める。
```

## 3. Constitution Check

```text
C01 Goal: この変更の目的は何か。
C02 SSOT: 関係する正本はどこか。
C03 Responsibility: 置き場の責務は合っているか。
C04 Boundary: 入力・出力・失敗時の契約は何か。
C05 Dependency: 依存方向は逆流しないか。
C06 State: 状態の所有者・更新者・確定点は変わるか。
C07 Data: schema、identifier、history、migrationに影響するか。
C08 Interface: API、CLI、file format、event、function contractを壊さないか。
C09 Error: timeout、retry、rollback、compensationが必要か。
C10 Idempotency: 二重実行で壊れないか。
C11 Auth: 認証・認可・権限境界に影響するか。
C12 Security: secret、token、credential、入力検証、監査ログに影響するか。
C13 Config: 環境差分・閾値・URL・feature flagを直書きしていないか。
C14 Compatibility: 既存利用者・既存データを壊さないか。
C15 Testability: 単体または統合で検証できるか。
C16 Observability: 障害時に追えるか。
C17 Simplicity: もっと少ない概念で解けないか。
C18 Minimal Diff: 目的外の整形・リファクタ・仕様変更が混ざっていないか。
C19 Operations: release、rollback、backup、monitoringに影響するか。
C20 Learning: 再発防止をrule/test/hook/reviewへ昇格すべきか。
```

（注：C05〜C18の問いは点検表であり、命令文ではない。点検表の疑問形は肯定形統一の対象外とする。）

## 4. Danger Zone Gate

危険領域とは、secret、.env、認証、認可、DB migration、本番データ、課金、通知、外部通信、依存追加、deploy、git push、大量置換、生成物一括上書きである。
危険領域に触れる変更は、承認を得てから進める。このプロジェクトでは特に、git push（main への反映）と、遺伝の正本（genetics.php）の挙動を変える変更を承認対象として扱う。

## 5. Design Constitution R01-R20

| ID | Principle | Gate |
|---|---|---|
| R01 | SSOT | 既存正本を検索し、そこへ集約する |
| R02 | 責務分離 | UI/業務/DB/通信/認可/検証を層ごとに分ける |
| R03 | 境界設計 | 入力・出力・失敗時を明示する |
| R04 | 依存方向 | 中核を独立に保ち、UI/DB/外部APIを外側に置く |
| R05 | データモデル | 保存する事実・ID・履歴を先に確認する |
| R06 | 契約互換 | API/function/event/file/CLI互換を保つ |
| R07 | 状態管理 | 所在・更新者・確定点を明示する |
| R08 | エラー設計 | timeout/retry/rollback/compensationを考える |
| R09 | 冪等性 | 二重実行に耐える設計にする |
| R10 | 整合性 | 強整合か結果整合かを分ける |
| R11 | 認可中核化 | 権限判定を中核層に置く |
| R12 | Security by Design | 入力・秘密・依存・監査を設計時から組み込む |
| R13 | 設定分離 | 環境差分・URL・鍵・閾値を設定層へ出す |
| R14 | 後方互換 | migration/version/deprecationを確認する |
| R15 | テスト可能性 | 副作用を分離し検証入口を確保する |
| R16 | 観測可能性 | log/metric/trace/auditを考える |
| R17 | 単純性 | 最小の概念数で解く |
| R18 | 最小差分 | 1変更につき1目的を保つ |
| R19 | 運用設計 | release/rollback/backup/monitoringを確認する |
| R20 | 学習固定 | 同じ失敗をrule/test/hook/reviewへ昇格する |

## 6. Implementation Rules

常時適用の門:

```text
常時記憶ファイルには核だけを保て。手順が増えたら Skill へ移せ。
Skill を1本置くごとに、名指しの1行と経路の1行を同じ変更で足し、照合を通せ。
機械で判定できる規則を書いたら、同じ変更で規則表に1行足せ。
検査は実在するファイルを起点にせよ。落ちるべき入力で落ちることを確かめてから、その検査を信用せよ。
Git / GitHub / CI / Docker / MCP / package manager / framework は、存在を確認できた場合のみ扱え。
事実として扱ってよいのは、読んだファイル・実行したコマンド・確認した設定のみ。それ以外は [UNVERIFIED] と表示せよ。
危険領域は承認必須領域として扱え。承認を得てから進め。
```

検査器の規律:

| 規則 | 元になった失敗 |
|---|---|
| 検査の起点は、宣言ではなく実在するファイルに置け | 宣言された行から検査を始めたため、どこにも登録していない Skill が「OK 0 個」で通った |
| 判定は出現1つずつに行え。「ファイルのどこかに有れば合格」を使うな | 出力用の呼び出しに印が1つあるだけで、印の無い内部の呼び出し144箇所が合格していた |
| 違反は不合格にせよ。警告止まりの検査を残すな | 警告は読まれず、違反が残った |
| 最初の一致で調査を打ち切るな | 1つ目の設定だけ見て、後方の違反を見逃した |
| 検査を作ったら、違反を1箇所作って落ちることを確かめよ。作った違反が実際にファイルへ入ったことも確かめよ | 置換が空振りしたまま「合格」を見て、検査が効いていると誤認しかけた |
| 終了コードはパイプを通さずに取れ | `cmd \| tail; echo $?` が `tail` の 0 を返し、失敗を成功と読んだ |
| 書式に依存する照合は、依存している書式を文書に書け | パスをバッククォートで囲んだ行が、文字列一致の抽出から黙って外れた |
| 自動で直す仕組みは、検査器が読む書式で書け | 自動追記した行を検査器が読めず、「名指し済みなのに届かない」状態を作りかけた |
| 変更ファイルだけの検査（pre-commit）と全体の検査（CI）を分けよ | 240ファイルの一括変更で pre-commit が時間切れになった |
| 原因を決める前に、失敗の出力を取れ | 仮説（実行環境の版の差）で直しかけたが、出力を取ると別の原因（道具の有無の差）だった |
| 意図した例外は、その場に印と理由を書け | 意図的な例外と違反を区別できず、同じ箇所を繰り返し調べた |

## 7. Output Schemas

### Audit report

```md
## Audit Report

### Detected environment
- Claude Code:
- Codex:
- Custom agent:
- Git:
- CI:
- Package/build tools:

### Existing harness files

### Existing verification entrypoints

### Conflicts / risks

### Recommended expansion plan
```

### Expansion plan

```md
## Expansion Plan

### Files to create/update
| Path | Action | Reason | Safe to write now? |
|---|---|---|---|

### Placement map
| Content | Destination |
|---|---|

### Approval needed
```

### Completion report

```md
## Expansion Complete

### Files created/updated

### Compact core installed in

### Full constitution installed in

### Skills installed in

### Review rubric installed in

### Hooks/CI/scripts
- Created:
- Proposed only:

### Skill routes / registration check (route-check output, exit code)

### Rule lint (rules: N / violations found: N / fixed: N / allowed with reason: N)

### Negative checks (unregistered skill -> NG, seeded violation -> NG)

### Verification performed

### Remaining manual steps
```

## 8. Harness Placement Policy

| Content | Place |
|---|---|
| 消すと事故る核 | CLAUDE.md / AGENTS.md / AGENT.md |
| 長い手順 | Skill / SKILL.md / workflow |
| 強制すべき検証 | hook / CI / pre-commit / script |
| 意味的レビュー | review rubric / subagent / reviewer skill |
| 作業中の計画 | PLANS.md / SPEC.md |
| 完全な思想と規則 | docs/llm-software-design-constitution.md |
| 検証証拠 | verification log / report |

## 9. Failure Diagnosis

検証の階梯と完了:

検証は段階で強める：①プロンプト内の反復 → ②完了条件の宣言 → ③停止時の hook による決定論ゲート → ④作業者以外による独立検証。
完了の報告には、実行したコマンドとその出力・終了コードを添えよ。「できました」だけの報告は、未完了として扱え。

保守:

失敗が起きたら、根本原因と教訓を訂正ログへ追記し、実証された教訓だけを規則・テスト・hook・規則表へ昇格せよ。守れている指示は削れ。仕様の事実には確認日を付け、「存在しない」という結論は、肯定の結論より念入りに確かめよ。

## 10. Self-Optimization Prompt

```text
このファイル（LLM Harness Unified Seed）を、自己構造化命令として読め。
§1 の原理を、このリポジトリで作業するあいだの規則として保て。
まず、現在の作業ディレクトリを読み取り専用で監査し、§11 の Audit report と Expansion plan を出せ。
判定は存在確認の結果だけに基づかせ、確認していないことは [UNVERIFIED] と表示せよ。
わたしが「展開せよ」と言ったら、§3 の一覧を最小差分で展開し、§10 の検証をすべて実行して、コマンド・出力・終了コードを添えて報告せよ。
既存ファイルは保全せよ。危険領域と、CI・pre-commit・permissions.deny・依存の追加は、提案として出し、承認を得てから扱え。
展開のあと、Skill・docs・規則を足すたびに、CLAUDE.md「このファイルの保ち方」に従え。
```
