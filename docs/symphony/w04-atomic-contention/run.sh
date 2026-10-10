#!/usr/bin/env bash
# W04 atomic contention probe runner: fixed private-handoff/out-dir arguments; appends exact command/timing to the private run log.
set -u
PRIV=/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-live-review-38789ea501e5
OUT=/home/ubuntu/.agentopt-v2/workspaces/req_ea58da0bbb7043999b64641fcfc32490/storage/framework/testing/w04-atomic-contention
HERE=$(cd "$(dirname "$0")" && pwd)
script=$1; shift
s=$(date -u +%s.%N); st=$(date -u +%H:%M:%S)
python3 -I "$HERE/$script" "$PRIV" "$OUT" "$@"; rc=$?
e=$(date -u +%s.%N)
printf '%s\t%s\t%s %s\trc=%s\t%.1fs\n' "$st" "$(date -u +%H:%M:%S)" "$script" "$*" "$rc" "$(echo "$e - $s" | bc)" >> "$OUT/run-log.tsv"
exit $rc
