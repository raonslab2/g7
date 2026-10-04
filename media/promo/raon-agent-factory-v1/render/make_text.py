#!/usr/bin/env python3
"""timeline.json 하나에서 자막(SRT/ASS)과 내레이션 원고를 만든다.

  python3 make_text.py            # ../subtitles-ko.srt, ../narration-ko.txt 갱신
  python3 make_text.py --ass DIR  # DIR/subs-16x9.ass, DIR/subs-9x16.ass (번인용)

자막·내레이션·렌더 타이밍이 같은 원천을 쓰도록 이 스크립트 외에서 손으로 고치지 않는다.
"""
import json
import pathlib
import sys

HERE = pathlib.Path(__file__).resolve().parent
PKG = HERE.parent
T = json.loads((HERE / "timeline.json").read_text(encoding="utf-8"))


def ts(sec: float, sep: str = ",") -> str:
    ms = round(sec * 1000)
    h, ms = divmod(ms, 3_600_000)
    m, ms = divmod(ms, 60_000)
    s, ms = divmod(ms, 1000)
    return f"{h:02d}:{m:02d}:{s:02d}{sep}{ms:03d}"


def ass_ts(sec: float) -> str:
    cs = round(sec * 100)
    h, cs = divmod(cs, 360_000)
    m, cs = divmod(cs, 6000)
    s, cs = divmod(cs, 100)
    return f"{h:d}:{m:02d}:{s:02d}.{cs:02d}"


def write_srt() -> None:
    lines = []
    for i, c in enumerate(T["cues"], 1):
        lines += [str(i), f"{ts(c['start'])} --> {ts(c['end'])}", c["text"], ""]
    (PKG / "subtitles-ko.srt").write_text("\n".join(lines), encoding="utf-8")


def write_narration() -> None:
    out = [
        "RAON Agent Factory 홍보영상 V1 — 한국어 내레이션 원고",
        f"러닝타임 {T['duration']:.0f}초 · 차분한 중저음 · 기업 발표 톤 · 과장 금지",
        "타임코드는 subtitles-ko.srt 와 같다(원천: render/timeline.json).",
        "",
    ]
    scenes = T["scenes"]
    for c in T["cues"]:
        sc = next((s for s in scenes if s["start"] <= c["start"] < s["end"]), None)
        tag = f"[{sc['id']} {sc['name']}]" if sc else ""
        sec = c["end"] - c["start"]
        text = c["text"].replace("\n", " ")
        syl = sum(1 for ch in text if "가" <= ch <= "힣") + sum(1 for w in text.split() if w.isascii() and w.isalpha())
        out.append(f"{ts(c['start'], '.')[3:]} – {ts(c['end'], '.')[3:]}  {tag}")
        out.append(f"  {text}")
        out.append(f"  ({sec:.1f}초 · 약 {syl / sec:.1f}음절/초)")
        out.append("")
    (PKG / "narration-ko.txt").write_text("\n".join(out), encoding="utf-8")


def write_ass(directory: pathlib.Path) -> None:
    directory.mkdir(parents=True, exist_ok=True)
    for name, (w, h, size, margin) in {"subs-16x9.ass": (1920, 1080, 44, 46), "subs-9x16.ass": (1080, 1920, 48, 150)}.items():
        head = f"""[Script Info]
ScriptType: v4.00+
PlayResX: {w}
PlayResY: {h}
WrapStyle: 2
ScaledBorderAndShadow: yes

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Sub,Noto Sans CJK KR,{size},&H00F2EEEA,&H000000FF,&H8C0B0806,&H8C0B0806,-1,0,0,0,100,100,0,0,3,14,0,2,80,80,{margin},1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
"""
        ev = []
        for c in T["cues"]:
            txt = c["text"].replace("\n", "\\N")
            ev.append(f"Dialogue: 0,{ass_ts(c['start'])},{ass_ts(c['end'])},Sub,,0,0,0,,{{\\fad(180,180)}}{txt}")
        (directory / name).write_text(head + "\n".join(ev) + "\n", encoding="utf-8")


if __name__ == "__main__":
    if len(sys.argv) == 3 and sys.argv[1] == "--ass":
        write_ass(pathlib.Path(sys.argv[2]))
    else:
        write_srt()
        write_narration()
