#!/usr/bin/env bash
# RAON Agent Factory 홍보영상 V1 빌드.
#   FFMPEG=/path/ffmpeg FFPROBE=/path/ffprobe PLAYWRIGHT_MODULE=/path/node_modules/playwright/index.mjs \
#     ./build.sh <out_dir>
# 산출물(대용량 MP4)은 저장소 밖 out_dir 에 만든다. 저장소에는 소스만 둔다.
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="${1:?out_dir}"
FF="${FFMPEG:-ffmpeg}"; FP="${FFPROBE:-ffprobe}"
mkdir -p "$OUT/work"
DUR=88

python3 "$HERE/make_text.py" --ass "$OUT/work"

# 1) 무음 clean 영상(자막 미번인) — 화면 내 타이틀·라벨은 포함
for o in h v; do
  node "$HERE/render-completion.mjs" video "$o" "$OUT/work/clean-$o.mp4" 30 0 $DUR
done

# 2) 음악 베드: 저작권 소재 없이 ffmpeg 로 합성한 앰비언트 패드(코드 8개 × 11초, 교차 페이드)
CHORDS=("130.81 196.00 329.63 493.88" "110.00 164.81 196.00 261.63" "87.31 130.81 164.81 220.00" "98.00 146.83 246.94 329.63"
        "130.81 196.00 329.63 493.88" "110.00 164.81 196.00 261.63" "87.31 130.81 164.81 220.00" "130.81 196.00 261.63 392.00")
inputs=(); filters=""; i=0
for c in "${CHORDS[@]}"; do
  read -r a b cc d <<<"$c"
  expr="0.16*sin(2*PI*$a*t)+0.05*sin(2*PI*$a*2.003*t)+0.11*sin(2*PI*$b*t)+0.09*sin(2*PI*$cc*t)*(0.8+0.2*sin(2*PI*0.21*t))+0.06*sin(2*PI*$d*t)*(0.7+0.3*sin(2*PI*0.13*t))"
  inputs+=(-f lavfi -i "aevalsrc=exprs='$expr|$expr':s=48000:d=13.5")
  filters+="[$i]afade=t=in:d=3,afade=t=out:st=10.5:d=3,adelay=$((i*11000))|$((i*11000))[c$i];"
  i=$((i+1))
done
mix=""; for j in $(seq 0 $((i-1))); do mix+="[c$j]"; done
"$FF" -loglevel error -y "${inputs[@]}" -filter_complex "${filters}${mix}amix=inputs=$i:normalize=0,lowpass=f=1400,aecho=0.8:0.7:60|140:0.25|0.18,atrim=0:$DUR,afade=t=in:d=2,afade=t=out:st=$((DUR-4)):d=4,loudnorm=I=-22:TP=-2:LRA=7" -ar 48000 -c:a pcm_s16le "$OUT/work/music.wav"

# 3) 마스터 합성: 자막 번인 버전 + clean 버전 (둘 다 음악 베드 포함, 내레이션 트랙 미포함)
FONTS=/usr/share/fonts/opentype/noto
for o in h v; do
  name=$([ "$o" = h ] && echo 16x9 || echo 9x16)
  "$FF" -loglevel error -y -i "$OUT/work/clean-$o.mp4" -i "$OUT/work/music.wav" \
    -vf "subtitles=$OUT/work/subs-$name.ass:fontsdir=$FONTS" -c:v libx264 -preset slow -crf 18 -pix_fmt yuv420p \
    -c:a aac -b:a 192k -shortest -movflags +faststart "$OUT/RAON_AGENT_FACTORY_PROMO_V1_$name.mp4"
  "$FF" -loglevel error -y -i "$OUT/work/clean-$o.mp4" -i "$OUT/work/music.wav" -c:v copy -c:a aac -b:a 192k -shortest \
    -movflags +faststart "$OUT/RAON_AGENT_FACTORY_PROMO_V1_${name}_clean.mp4"
done

# 4) 썸네일(엔딩 카피 프레임)과 기준 프레임
node "$HERE/render-completion.mjs" stills h "$OUT/work/thumb" 86.4
"$FF" -loglevel error -y -i "$OUT/work/thumb/16x9-t86.4.jpg" -vf scale=1280:720 -q:v 3 "$OUT/RAON_AGENT_FACTORY_PROMO_V1_thumbnail.jpg"

# 5) 검증 출력
for f in "$OUT"/RAON_AGENT_FACTORY_PROMO_V1_*.mp4; do
  echo "== $(basename "$f")"
  "$FP" -v error -show_entries format=duration,size:stream=codec_name,width,height,r_frame_rate,sample_rate,channels -of compact "$f"
done
(cd "$OUT" && sha256sum RAON_AGENT_FACTORY_PROMO_V1_* > SHA256SUMS)
