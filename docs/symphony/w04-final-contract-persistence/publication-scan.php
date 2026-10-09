<?php
/** Scan only own authorized private values against owned PUBLIC deliverables; output no values. */
require __DIR__.'/guard.php';w04Env();
$env=Dotenv\Dotenv::parse(file_get_contents(w04fRoot().'/.env'));$secrets=[];
foreach($env as $k=>$v){if(str_contains($k,'PASSWORD')||in_array($k,['APP_KEY','MAIL_FROM_ADDRESS','INSTALLER_ADMIN_EMAIL'],true)){$secrets[]=$v;}}
$access=json_decode(file_get_contents(w04fPrivate('private').'/access.json'),true,flags:JSON_THROW_ON_ERROR);
foreach(['member','other_member','admin'] as $r){foreach(['email','password','bearer_token'] as $k){$secrets[]=$access[$r][$k];}}
$p=json_decode(file_get_contents(w04fPrivate('private').'/persistence.json'),true,flags:JSON_THROW_ON_ERROR);
foreach(['question_body','answer_body','campaign_body'] as $k){$secrets[]=$p[$k];}
// Own first-login tokens are retained only in own access history if available; never foreign handoffs.
$violations=[];$files=[];
$walk=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__,FilesystemIterator::SKIP_DOTS));
foreach($walk as $f){if(!$f->isFile()){continue;}$files[]=$f->getPathname();}
$files[]=w04fRoot().'/docs/symphony/W04_FINAL_CONTRACT_PERSISTENCE.md';
foreach($files as $f){$text=file_get_contents($f);$relative=str_replace(w04fRoot().'/', '',$f);
 foreach(array_unique($secrets) as $secret){if(is_string($secret)&&strlen($secret)>8&&str_contains($text,$secret)){$violations[$relative]='own private value';}}
 if(preg_match('/(?:Bearer\s+|[0-9]+\|)[A-Za-z0-9]{35,}/',$text)){$violations[$relative]='plaintext token pattern';}
 if(preg_match('/\.(sql|cnf|sqlite|db|env|pyc)$/',$f)||str_contains($relative,'__pycache__')){$violations[$relative]='private/generated path';}
 if(preg_match('/^INSERT INTO `g7_/m',$text)){$violations[$relative]='dump data';}
}
w04Save(w04fEvidence('publication').'/scan.json',['utc'=>gmdate('c'),'public_files_scanned'=>count($files),'known_private_values_scanned'=>count(array_unique($secrets)),'violation_count'=>count($violations),'violation_paths'=>array_keys($violations),'result'=>$violations===[]?'PASS':'FAIL','foreign_private_values_read'=>false]);
echo ($violations===[]?'PASS':'FAIL').' owned public artifact privacy scan'.PHP_EOL;exit($violations===[]?0:1);
