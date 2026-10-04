from pathlib import Path
import subprocess,json,hashlib,concurrent.futures,os,re
import sys
out=Path(sys.argv[1]).resolve();p=Path(__file__).resolve().parent.parent;ev=p/'evidence';frames=out/'review-frames';frames.mkdir(exist_ok=True);report=[]
times=[3,12,19,25,30,37,43,48,56,64,70,77,86]
for orient,w,h in [('16x9',1920,1080),('9x16',1080,1920)]:
 f=out/f'RAON_AGENT_FACTORY_PROMO_V1_{orient}.mp4';meta=json.loads(subprocess.check_output(['ffprobe','-v','error','-show_format','-show_streams','-of','json',str(f)]));video=next(s for s in meta['streams'] if s['codec_type']=='video');audio=next(s for s in meta['streams'] if s['codec_type']=='audio');assert(video['width'],video['height'])==(w,h);assert abs(float(meta['format']['duration'])-88)<.1;assert audio['codec_name']=='aac';assert video['codec_name']=='h264'
 subprocess.run(['ffmpeg','-v','error','-i',str(f),'-f','null','-'],check=True)
 volume=subprocess.run(['ffmpeg','-hide_banner','-i',str(f),'-vn','-af','volumedetect','-f','null','-'],capture_output=True,text=True,check=True).stderr
 peak=re.search(r'max_volume: (-?[0-9.]+) dB',volume);assert peak and -60<float(peak.group(1))<=0
 for t in times:subprocess.run(['ffmpeg','-v','error','-y','-ss',str(t),'-i',str(f),'-frames:v','1',str(frames/f'{orient}-{t:02}.jpg')],check=True)
 # Stable chronological filenames: contact sheet from decoded final movie, includes subtitles.
 subprocess.run(['ffmpeg','-v','error','-y','-pattern_type','glob','-i',str(frames/f'{orient}-*.jpg'),'-vf','scale=384:-1,tile=7x2','-frames:v','1',str(ev/f'contact-{orient}.jpg')],check=True)
 report.append({'name':f.name,'duration':float(meta['format']['duration']),'width':w,'height':h,'fps':video['r_frame_rate'],'audio_codec':audio['codec_name'],'audio_sample_rate':audio['sample_rate'],'audio_channels':audio['channels'],'bytes':f.stat().st_size,'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'audio_peak_db':float(peak.group(1)),'full_decode':'PASS','sample_times':times})
def ocr(f):
 text=subprocess.run(['tesseract',str(f),'stdout','-l','eng','--psm','11'],capture_output=True,text=True,env={**os.environ,'OMP_THREAD_LIMIT':'1'}).stdout
 hits=re.findall(r'\b[a-f0-9]{10,}\b|req_[A-Za-z0-9_]+|794ec\S*|f6dd\S*|30954\S*|[\w.-]+@[\w.-]+\.[A-Za-z]{2,}|https?://\S+',text,re.I)
 return {'file':f.name,'identifier_hits':hits}
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:scan=list(pool.map(ocr,frames.glob('*.jpg')))
(ev/'video-probe.json').write_text(json.dumps(report,indent=2));(ev/'frame-ocr.json').write_text(json.dumps(scan,indent=2));assert not any(r['identifier_hits'] for r in scan),scan
subprocess.run(['node',str(p/'render/playback.mjs'),str(out)],check=True)
(ev/'playback.json').write_text((out/'playback.json').read_text());print(json.dumps(report));print('verification finished')
