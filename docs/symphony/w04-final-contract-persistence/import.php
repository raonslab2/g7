<?php
require __DIR__.'/guard.php';
$p=w04Pdo();w04fExclusive($p);
$sql=file_get_contents($argv[1]);$n=strlen($sql);$part='';$quote=null;$comment=false;$line=false;$count=0;
for($i=0;$i<$n;$i++){
 $c=$sql[$i];$next=$sql[$i+1]??'';
 if($line){if($c==="\n"){$line=false;$part.="\n";}continue;}
 if($quote!==null){$part.=$c;if($c==='\\'){$part.=$sql[++$i]??'';}elseif($c===$quote){if($next===$quote){$part.=$sql[++$i];}else{$quote=null;}}continue;}
 if($comment){$part.=$c;if($c==='*'&&$next==='/'){$part.=$sql[++$i];$comment=false;}continue;}
 if($c==='-'&&$next==='-'||$c==='#'){$line=true;continue;}
 if($c==='/'&&$next==='*'){
  $end=strpos($sql,'*/',$i+2);if($end===false){throw new RuntimeException('Unclosed comment.');}
  if(substr($sql,$i,4)==='/*M!'){$i=$end+1;continue;}
  $comment=true;$part.=$c;continue;
 }
 if(in_array($c,["'",'"','`'],true)){$quote=$c;$part.=$c;continue;}
 if($c===';'){if(trim($part)!==''){$p->exec($part);$count++;if(($argv[3]??'')==='--inject-session-only'&&$count===1){if(!preg_match('/^\s*\/\*!\d*\s*SET|^\s*SET/i',$part)){throw new RuntimeException('Injection must follow only session SET.');}echo json_encode(['injected_primary_failure'=>true,'completed_session_statements'=>1,'completed_table_statements'=>0]).PHP_EOL;exit(73);}}$part='';continue;}$part.=$c;
}
if(trim($part)!==''||$quote||$comment){throw new RuntimeException('Incomplete dump.');}
echo json_encode(['imported_statements'=>$count,'per_statement_TEST_guard'=>true]).PHP_EOL;
