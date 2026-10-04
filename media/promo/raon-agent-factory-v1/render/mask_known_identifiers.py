from pathlib import Path
import subprocess,json
p=Path(__file__).resolve().parent.parent
# Mask complete source-revision values and commit identifiers using recorded DOM bounds.
for name,boxes,dpr in [('07-observations-1440x900',[(470,98,292,40),(470,160,292,40),(470,297,80,26)],1.5),('07-observations-390x844',[(39,90,312,40),(39,155,312,40),(39,495,100,26)],3)]:
 f=p/'assets-safe/ui'/f'{name}.webp';tmp=f.with_suffix('.tmp.webp')
 vf=','.join(f'drawbox=x={int(x*dpr)}:y={int(y*dpr)}:w={int(w*dpr)}:h={int(h*dpr)}:color=0x15212c:t=fill' for x,y,w,h in boxes)
 subprocess.run(['ffmpeg','-v','error','-y','-i',str(f),'-vf',vf,'-c:v','libwebp','-lossless','1',str(tmp)],check=True);tmp.replace(f)

# Additional desktop release SHA missed by first OCR pass (not used by video).
f=p/'assets-safe/ui/06-parent-result-1440x900.webp';tmp=f.with_suffix('.tmp.webp')
subprocess.run(['ffmpeg','-v','error','-y','-i',str(f),'-vf','drawbox=x=1585:y=1033:w=80:h=18:color=0x15212c:t=fill','-c:v','libwebp','-lossless','1',str(tmp)],check=True);tmp.replace(f)
