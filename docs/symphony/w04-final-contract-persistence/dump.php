<?php
/** Native SHOW CREATE / full rows dump through SELECT DATABASE guard on EVERY SQL. */
require __DIR__.'/guard.php';$p=w04Pdo();w04fExclusive($p);$before=w04Measure($p);
$output=$argv[1];if(file_exists($output)||is_link($output)){throw new RuntimeException('Never overwrite retained dump.');}
$f=fopen($output,'x');chmod($output,0600);
fwrite($f,"-- Guarded native TEST dump\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
foreach($before['tables'] as $table=>$metadata){
 $q='`'.str_replace('`','``',$table).'`';$ddl=$p->query('SHOW CREATE TABLE '.$q)->fetch(PDO::FETCH_NUM)[1];
 fwrite($f,'DROP TABLE IF EXISTS '.$q.";\n".$ddl.";\n");$cols=$p->query('SHOW COLUMNS FROM '.$q)->fetchAll(PDO::FETCH_ASSOC);
 $binary=array_map(static fn($c)=>(bool)preg_match('/^(binary|varbinary|tinyblob|blob|mediumblob|longblob|bit)\b/i',$c['Type']),$cols);
 foreach($p->query('SELECT * FROM '.$q)->fetchAll(PDO::FETCH_NUM) as $row){$vs=[];
  foreach($row as $i=>$value){$vs[]=$value===null?'NULL':($binary[$i]?'0x'.bin2hex((string)$value):$p->quote((string)$value));}
  fwrite($f,'INSERT INTO '.$q.' VALUES ('.implode(',',$vs).");\n");
 }
}
fwrite($f,"SET FOREIGN_KEY_CHECKS=1;\n-- Dump completed on ".gmdate('c')."\n");fclose($f);
if($before!==w04Measure($p)){throw new RuntimeException('Dump source changed; preserve and reject.');}
echo json_encode(['native_SHOW_CREATE_full_rows'=>true,'per_statement_TEST_guard'=>true,'tables'=>$before['table_count'],'rows'=>$before['row_count'],'inventory_digest'=>w04fDigest($before),'dump_sha256'=>hash_file('sha256',$output),'dump_bytes'=>filesize($output)]).PHP_EOL;
