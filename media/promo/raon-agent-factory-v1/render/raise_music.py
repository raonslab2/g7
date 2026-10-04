"""Raise existing music to -14 LUFS using measured two-pass normalization.
Video streams are copied; previous masters are preserved outside Git.
Usage: python3 raise_music.py OUTPUT_DIR
"""
from pathlib import Path
import subprocess,json,re,sys,shutil,hashlib
out=Path(sys.argv[1]).resolve();evidence=Path(__file__).resolve().parent.parent/'evidence';backup=out.parent/'before-audio-fix';backup.mkdir(exist_ok=True)
source=out/'work/music.wav'
measure=subprocess.run(['ffmpeg','-hide_banner','-i',str(source),'-af','loudnorm=I=-14:TP=-1.5:LRA=7:print_format=json','-f','null','-'],capture_output=True,text=True,check=True).stderr
m=json.loads(measure[measure.rfind('{'):]);af=f"loudnorm=I=-14:TP=-1.5:LRA=7:measured_I={m['input_i']}:measured_TP={m['input_tp']}:measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}:linear=true:print_format=json"
wave=out/'work/music-audible.wav';subprocess.run(['ffmpeg','-v','error','-y','-i',str(source),'-af',af,'-ar','48000','-c:a','pcm_s16le',str(wave)],check=True)
report=[]
for f in sorted(out.glob('RAON_AGENT_FACTORY_PROMO_V1_*.mp4')):
 old=backup/f.name
 if old.exists():raise RuntimeError('backup already exists; refusing to overwrite '+str(old))
 shutil.copy2(f,old);temp=f.with_name(f.stem+'.audio-fix.mp4')
 subprocess.run(['ffmpeg','-v','error','-y','-i',str(old),'-i',str(wave),'-map','0:v:0','-map','1:a:0','-c:v','copy','-c:a','aac','-b:a','192k','-shortest','-movflags','+faststart',str(temp)],check=True)
 def video_hash(path):return subprocess.check_output(['ffmpeg','-v','error','-i',str(path),'-map','0:v:0','-c:v','copy','-f','hash','-hash','sha256','-']).decode().strip()
 assert video_hash(old)==video_hash(temp),'video stream changed'
 def level(path):
  s=subprocess.run(['ffmpeg','-hide_banner','-i',str(path),'-vn','-af','volumedetect','-f','null','-'],capture_output=True,text=True,check=True).stderr
  return {k:float(re.search(k+r': (-?[0-9.]+) dB',s).group(1)) for k in ['mean_volume','max_volume']}
 before,after=level(old),level(temp);assert after['max_volume']<0 and after['mean_volume']>before['mean_volume']+5
 temp.replace(f);report.append({'file':f.name,'before_db':before,'after_db':after,'video_stream_unchanged':True,'sha256':hashlib.sha256(f.read_bytes()).hexdigest()})
(evidence/'audio-fix.json').write_text(json.dumps({'target_lufs':-14,'source_measurement':m,'files':report},indent=2))
files=sorted(out.glob('RAON_AGENT_FACTORY_PROMO_V1_*'));(out/'SHA256SUMS').write_text(''.join(hashlib.sha256(f.read_bytes()).hexdigest()+'  '+f.name+'\n' for f in files if f.is_file()))
print(json.dumps(report,indent=2))
