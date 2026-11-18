#!/usr/bin/env bash
# UserPromptSubmit: 依頼文を .claude/skill-routes.tsv の正規表現と機械照合し、一致した手順の本文を文脈へ直接入れる。
# Skill が読まれるかどうかをモデルの判断に任せないための経路。標準出力は、そのまま文脈に追加される。
# 入力(stdin)はデータとして照合するだけで、評価・実行しない。
# 信頼境界: 投入するのはリポジトリ内 .claude/skills/<名前>/SKILL.md だけ。1依頼あたりの投入本数に上限を置く。
input="$(cat)"
root="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
routes="$root/.claude/skill-routes.tsv"
[ -f "$routes" ] || exit 0
max="${SKILL_ROUTER_MAX:-3}"
if command -v python3 >/dev/null 2>&1; then
  prompt="$(printf '%s' "$input" | python3 -I -c 'import json,sys
try: print(json.load(sys.stdin).get("prompt",""))
except Exception: pass' 2>/dev/null)"
fi
[ -n "${prompt:-}" ] || prompt="$input"
tab="$(printf '\t')"; seen=""; n=0
while IFS="$tab" read -r regex path; do
  case "$regex" in ''|'#'*) continue;; esac
  case "$path" in *..*) continue;; .claude/skills/*/SKILL.md) ;; *) continue;; esac
  [ -f "$root/$path" ] || continue
  case " $seen " in *" $path "*) continue;; esac
  if printf '%s' "$prompt" | grep -Eiq -e "$regex" 2>/dev/null; then
    seen="$seen $path"; n=$((n+1))
    if [ "$n" -gt "$max" ]; then
      echo "（一致した手順が上限 ${max} 本を超えたため本文を省いた: $path。必要なら自分で読め。）"
      continue
    fi
    echo "この依頼には、次の手順が当てはまる（出どころ: $path）。この手順に従え。"
    echo "-----"
    awk 'NR==1 && $0=="---" {fm=1; next} fm && $0=="---" {fm=0; next} !fm' "$root/$path"
    echo "-----"
  fi
done < "$routes"
exit 0
