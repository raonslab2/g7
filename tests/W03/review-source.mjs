import {execFileSync} from 'node:child_process';
export const target='28ada286c1c34606741bcfe4f9d12e06ac50af30';
export function verifySource(){
 const head=execFileSync('git',['rev-parse','HEAD'],{encoding:'utf8'}).trim();
 // Evidence-only descendant commits may be used to rerun scripts; their product tree
 // must equal the authoritative review target. This never rebinds canonical Validation.
 const changes=execFileSync('git',['diff','--name-only',target],{encoding:'utf8'}).trim().split('\n').filter(Boolean);
 if(changes.some(p=>!(p.startsWith('tests/W03/')||p.startsWith('docs/symphony/evidence/W03/')||p==='docs/symphony/W03_BROWSER_REVIEW.md')))throw new Error('Product source differs from fixed review target');
 return {target,head,tree:execFileSync('git',['rev-parse',target+'^{tree}'],{encoding:'utf8'}).trim()};
}
verifySource();
