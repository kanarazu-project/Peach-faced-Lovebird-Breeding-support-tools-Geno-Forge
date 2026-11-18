#!/usr/bin/env bash
# SessionStart: Skill の登録と発動条件を作業の前に照合し、不一致を文脈の先頭へ入れる。
# 標準出力は文脈に追加される。不一致が無ければ何も出さない。
cat >/dev/null
root="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
[ -f "$root/scripts/route-check" ] || exit 0
if ! out="$(bash "$root/scripts/route-check" 2>&1)"; then
  echo "【最優先】Skill の登録か発動条件に不一致がある。依頼された作業に入る前に、次の不一致と修正案を報告し、直せ（CLAUDE.md「このファイルの保ち方」）。"
  printf '%s\n' "$out" | grep '^NG'
fi
exit 0
