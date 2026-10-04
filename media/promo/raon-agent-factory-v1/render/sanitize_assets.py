from pathlib import Path
import subprocess,concurrent.futures,re,csv,io,json
p=Path(__file__).resolve().parent.parent;dest=p/'assets-safe';report=[]
def process(f):
 rel=f.relative_to(p/'assets');o=dest/rel;o.parent.mkdir(parents=True,exist_ok=True)
 t=subprocess.run(['tesseract',str(f),'stdout','-l','kor+eng','--psm','11','tsv'],capture_output=True,text=True,check=True).stdout
 rows=list(csv.DictReader(io.StringIO(t),delimiter='\t'));boxes=[]
 for r in rows:
  word=r.get('text','').strip()
  if re.search(r'[a-fA-F0-9]{10,}|req_|work-20|https?://|\b(?:127|192|10)\.[0-9]|@|794ec|f6dd|30954',word):
   x,y,w,h=[int(r[k]) for k in ('left','top','width','height')];boxes.append([max(0,x-5),max(0,y-5),w+10,h+10])
 filters=','.join(f'drawbox=x={x}:y={y}:w={w}:h={h}:color=0x15212c:t=fill' for x,y,w,h in boxes) or 'null'
 subprocess.run(['ffmpeg','-loglevel','error','-y','-i',str(f),'-vf',filters,'-c:v','libwebp','-lossless','1',str(o)],check=True)
 return {'file':str(rel),'redacted_boxes':boxes}
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:report=list(pool.map(process,(p/'assets').rglob('*.webp')))
(p/'assets-safe/redactions.json').write_text(json.dumps(report,indent=2));print('sanitized',len(report),'images; boxes',sum(len(r['redacted_boxes']) for r in report))
