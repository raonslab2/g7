"""Apply approved Inspired soundtrack and visible attribution, retaining prior masters.
Usage: python3 use_licensed_music.py OUTPUT_DIR TRACK_MP3
"""
from pathlib import Path
import subprocess,json,sys,hashlib,shutil
out=Path(sys.argv[1]).resolve();track=Path(sys.argv[2]).resolve();pkg=Path(__file__).resolve().parent.parent
backup=out/'history/before-licensed-music';backup.mkdir(parents=True,exist_ok=True)
credit=pkg/'MUSIC-CREDIT.txt'
credit.write_text('Music: Inspired - Kevin MacLeod (incompetech.com)\nCC BY 4.0 - creativecommons.org/licenses/by/4.0/\nEdited: 88-second excerpt, fades, loudness\n')
base='atrim=0:88,asetpts=PTS-STARTPTS,afade=t=in:d=1.5,afade=t=out:st=84:d=4'
s=subprocess.run(['ffmpeg','-hide_banner','-i',str(track),'-af',base+',loudnorm=I=-14:TP=-1.5:LRA=7:print_format=json','-f','null','-'],capture_output=True,text=True,check=True).stderr
m=json.loads(s[s.rfind('{'):]);norm=f"loudnorm=I=-14:TP=-1.5:LRA=7:measured_I={m['input_i']}:measured_TP={m['input_tp']}:measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}:linear=true"
wave=out/'work/music-inspired.wav'
subprocess.run(['ffmpeg','-v','error','-y','-i',str(track),'-af',base+','+norm,'-ar','48000','-c:a','pcm_s16le',str(wave)],check=True)
for f in sorted(out.glob('RAON_AGENT_FACTORY_PROMO_V1_*.mp4')):
 old=backup/f.name
 if old.exists():raise RuntimeError('backup exists; refuse overwrite: '+str(old))
 shutil.copy2(f,old);tmp=f.with_name(f.stem+'.licensed.mp4');vertical='9x16' in f.name
 vf=f"drawtext=fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf:textfile={credit}:fontsize={25 if vertical else 22}:fontcolor=white:line_spacing=5:box=1:boxcolor=0x0a0e13@0.75:boxborderw=12:x=(w-text_w)/2:y={1500 if vertical else 80}:enable='between(t,80,88)'"
 subprocess.run(['ffmpeg','-v','error','-y','-i',str(old),'-i',str(wave),'-map','0:v:0','-map','1:a:0','-vf',vf,'-c:v','libx264','-preset','fast','-crf','18','-pix_fmt','yuv420p','-c:a','aac','-b:a','192k','-t','88','-movflags','+faststart','-metadata','comment=Music: Inspired by Kevin MacLeod (incompetech.com), CC BY 4.0 https://creativecommons.org/licenses/by/4.0/ ; edited excerpt, fades and loudness',str(tmp)],check=True)
 tmp.replace(f);print('completed',f.name,flush=True)
report={'title':'Inspired','artist':'Kevin MacLeod','isrc':'USUAN1600022','download_url':'https://incompetech.com/music/royalty-free/mp3-royaltyfree/Inspired.mp3','track_sha256':hashlib.sha256(track.read_bytes()).hexdigest(),'license':'CC BY 4.0','license_url':'https://creativecommons.org/licenses/by/4.0/','license_source':'https://incompetech.com/music/royalty-free/music.html','licensing_page':'https://incompetech.com/music/royalty-free/licenses/','checked_on':'2026-10-04','cost':0,'modifications':'first 88 seconds; fade-in/out; two-pass loudness normalization; AAC encoding','visible_credit_seconds':[80,88],'normalization_target_lufs':-14,'measured_source':m}
(pkg/'evidence/licensed-music.json').write_text(json.dumps(report,indent=2))
files=sorted(out.glob('RAON_AGENT_FACTORY_PROMO_V1_*'));(out/'SHA256SUMS').write_text(''.join(hashlib.sha256(f.read_bytes()).hexdigest()+'  '+f.name+'\n' for f in files if f.is_file()))
