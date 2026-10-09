<?php
/** 독립 req_bed1c228 전용 TEST guard. 공개 APP guard/원본 소스는 변경하지 않는다. */
declare(strict_types=1);
umask(0077);
require_once dirname(__DIR__, 3).'/vendor/autoload.php';
require_once __DIR__.'/inventory.php';
const W04F_BASELINE_DIGEST = 'ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e';
function w04fRoot(): string { return dirname(__DIR__, 3); }
function w04fPrivate(string $name): string {
    if (!preg_match('/^[a-z0-9-]+$/', $name)) { throw new RuntimeException('Private label rejected.'); }
    return w04fRoot().'/storage/framework/testing/w04q-'.$name;
}
function w04fEvidence(string $name): string {
    $dir = __DIR__.'/evidence/'.$name;
    if (!is_dir($dir)) { mkdir($dir, 0700, true); }
    return $dir;
}
function w04fDigest(array $data): string { return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)); }
function w04Env(bool $cleanup = false, bool $testing = false): array {
    $root = w04fRoot();
    foreach (['.env', '.env.testing'] as $name) {
        $path = $root.'/'.$name;
        if (is_link($path) || !is_file($path) || (fileperms($path)&0777)!==0600) { throw new RuntimeException('Private TEST environment required.'); }
    }
    if (str_replace(['CACHE_STORE=database','APP_ENV=local'], ['CACHE_STORE=array','APP_ENV=testing'], file_get_contents($root.'/.env')) !== file_get_contents($root.'/.env.testing')) { throw new RuntimeException('Own TEST environment bytes differ.'); }
    $v = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    $expected = ['TRAVEL_LAB_ISOLATED'=>'1','TRAVEL_LAB_SUPPORT_PROVISIONING'=>'1','G7_ENV_PRIORITY'=>'true',
        'DB_CONNECTION'=>'mysql','DB_PREFIX'=>'g7_','DB_DATABASE'=>'req81_travel_lab_test',
        'DB_WRITE_HOST'=>'127.0.0.1','DB_READ_HOST'=>'127.0.0.1','DB_WRITE_PORT'=>'3306','DB_READ_PORT'=>'3306',
        'DB_WRITE_DATABASE'=>'req81_travel_lab_test','DB_READ_DATABASE'=>'req81_travel_lab_test',
        'DB_WRITE_USERNAME'=>'req81_travel','DB_READ_USERNAME'=>'req81_travel',
        'CACHE_STORE'=>'database','DB_CACHE_CONNECTION'=>'mysql','DB_CACHE_LOCK_CONNECTION'=>'mysql',
        'DB_CACHE_TABLE'=>'cache','DB_CACHE_LOCK_TABLE'=>'cache_locks', 'SESSION_DRIVER'=>'array',
        'MAIL_MAILER'=>'array','QUEUE_CONNECTION'=>'sync','FILESYSTEM_DISK'=>'local','SCOUT_DRIVER'=>'mysql-fulltext'];
    foreach ($expected as $k=>$value) { if (($v[$k]??null)!==$value) { throw new RuntimeException('TEST boundary failed: '.$k); } }
    if (empty($v['DB_WRITE_PASSWORD']) || $v['DB_WRITE_PASSWORD'] !== ($v['DB_READ_PASSWORD']??null)) { throw new RuntimeException('TEST credential shape failed.'); }
    $forbidden = ['DB_URL','DB_SOCKET','MYSQL_ATTR_SSL_CA','CACHE_LIMITER','REDIS_URL','MEILISEARCH_HOST','SCOUT_MEILISEARCH_HOST'];
    foreach ($forbidden as $k) { if (!empty($v[$k])) { throw new RuntimeException('Connection override rejected.'); } }
    $env = getenv();
    foreach ($env as $k=>$value) {
        $guarded = preg_match('/^(DB_|CACHE_|REDIS_|SCOUT_|MEILISEARCH_|INSTALLER_|MYSQL_|MAIL_|QUEUE_|FILESYSTEM_|HTTP_PROXY|HTTPS_PROXY|ALL_PROXY|NO_PROXY|http_proxy|https_proxy|all_proxy|no_proxy)/',$k)
            || ($k==='MYSQL_ATTR_SSL_CA') || (str_starts_with($k,'APP_') && str_ends_with($k,'_CACHE'));
        if ($guarded && (!array_key_exists($k,$v) || $v[$k]!==$value)) {
            // 내부 ordinary PHPUnit의 명시 array만 허용하며 외부 임의 override는 거부한다.
            if (!$testing || !in_array($k,['CACHE_STORE'],true) || $value!=='array') { throw new RuntimeException('Inherited override rejected: '.$k); }
        }
    }
    if (is_file($root.'/storage/installer/runtime.php') || is_link($root.'/bootstrap/cache/config.php')
        || (!$cleanup && is_file($root.'/bootstrap/cache/config.php'))) { throw new RuntimeException('Installer/config cache override rejected.'); }
    $env = array_merge($env,$v,['APP_ENV'=>$testing?'testing':'local','COMPOSER_DISABLE_NETWORK'=>'1','COMPOSER_NO_INTERACTION'=>'1']);
    if ($testing) { $env['CACHE_STORE']='array'; }
    foreach (['HTTP_PROXY','HTTPS_PROXY','ALL_PROXY','http_proxy','https_proxy','all_proxy'] as $k) {
        if (($env[$k]??null)!=='http://127.0.0.1:9') { throw new RuntimeException('Offline egress boundary failed.'); }
    }
    if (($env['NO_PROXY']??'')!=='127.0.0.1,localhost' || ($env['no_proxy']??'')!=='127.0.0.1,localhost') { throw new RuntimeException('Proxy bypass rejected.'); }
    return $env;
}
function w04fTestIds(?PDO $pdo = null): array {
    $observer = $pdo === null;
    $pdo ??= w04Pdo();
    $id = (int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
    $ids = array_map('intval', $pdo->query("SELECT ID FROM information_schema.PROCESSLIST WHERE DB = 'req81_travel_lab_test' ORDER BY ID")->fetchAll(PDO::FETCH_COLUMN));
    return $observer ? array_values(array_diff($ids, [$id])) : $ids;
}
function w04fQuiesce(float $max=0): array {
    if (w04fTestIds() !== []) { throw new RuntimeException('Competing TEST connection; preserve snapshots and BLOCK.'); }
    return ['quiet'=>true, 'waited_seconds'=>0];
}
function w04fForeignHandles(): array {
    $p=proc_open(['sudo','-n','python3','-I',__DIR__.'/process-check.py'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,w04fRoot());
    $out=stream_get_contents($pipes[1]); stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $exit=proc_close($p); $r=json_decode($out,true,flags:JSON_THROW_ON_ERROR);
    if($exit!==0 || $r['count']!==0 || $r['unreadable_pids']!==[] || $r['lock_holders']!==[]) {
        w04Save(w04fEvidence('intake').'/competing-process-'.bin2hex(random_bytes(4)).'.json',$r);
        throw new RuntimeException('Competing TEST process/FD/lease; do not kill or retry.');
    }
    return $r;
}
function w04fOwnServerAlive(): array {
    $file=w04fPrivate('server').'/pgid'; if(!is_file($file)) { return []; }
    $pgid=(int)file_get_contents($file); $pids=[];
    foreach(glob('/proc/[0-9]*/stat') as $s) {
        $raw=(string)@file_get_contents($s); $end=strrpos($raw,')'); if($end===false) { continue; }
        $f=explode(' ',substr($raw,$end+2)); if((int)($f[2]??-1)===$pgid) { $pids[]=(int)basename(dirname($s)); }
    }
    return $pids;
}
function w04fExclusive(PDO $pdo): array {
    $id=$pdo->query('SELECT DATABASE() AS db,CURRENT_USER() AS account,CONNECTION_ID() AS id')->fetch(PDO::FETCH_ASSOC);
    w04Exclusive($pdo);
    if($id['db']!=='req81_travel_lab_test' || $id['account']!=='req81_travel@127.0.0.1' || w04fTestIds($pdo)!==[(int)$id['id']]) { throw new RuntimeException('Other TEST connection or identity.'); }
    $prior=w04fForeignHandles();
    if(w04fOwnServerAlive()!==[]) { throw new RuntimeException('Own HTTP process must stop before destructive work.'); }
    return ['pid'=>getmypid(),'cwd'=>getcwd(),'argv'=>array_map(static fn($x)=>str_replace(w04fRoot().'/', '',$x),$_SERVER['argv']??[]),
        'own_connection_id'=>(int)$id['id'],'other_test_connections'=>0,'own_server_processes'=>0,'prior_request_handles'=>$prior,'utc'=>gmdate('c')];
}
function w04fPhase(string $dir,string $phase,array $extra=[]): void { w04Save($dir.'/phase.json',['phase'=>$phase,'utc'=>gmdate('c')]+$extra); }
function w04fRequireSnapshot(string $label): string {
    w04Env(); $dir=w04fPrivate($label);
    if(is_link($dir)||!is_dir($dir)||(fileperms($dir)&0777)!==0700) { throw new RuntimeException('Private snapshot required.'); }
    foreach(['before.json','manifest.json','snapshot.sql','mysql.cnf'] as $f) {
        if(is_link($dir.'/'.$f)||!is_file($dir.'/'.$f)||(fileperms($dir.'/'.$f)&0777)!==0600) { throw new RuntimeException('Private snapshot files required.'); }
    }
    $m=json_decode(file_get_contents($dir.'/manifest.json'),true,flags:JSON_THROW_ON_ERROR);
    $b=json_decode(file_get_contents($dir.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
    if($b['schema']!=='req81_travel_lab_test'||$m['stable']!==true||$m['dump_exit']!==0||hash_file('sha256',$dir.'/snapshot.sql')!==$m['dump_sha256']||w04fDigest($b)!==$m['before_digest']) { throw new RuntimeException('Snapshot integrity failed.'); }
    return $dir;
}
function w04fChild(array $command,string $log,?array $env=null): int {
    $env??=w04Env(); $start=microtime(true);
    if(is_file($log)) {
        $suffix='.history-'.bin2hex(random_bytes(4));
        foreach(['','.stderr','.result.json','.ownership.json'] as $ext) { if(is_file($log.$ext)) { rename($log.$ext,$log.$suffix.$ext); } }
    }
    $p=proc_open($command,[0=>['file','/dev/null','r'],1=>['file',$log,'w'],2=>['file',$log.'.stderr','w']],$pipes,w04fRoot(),$env);
    if(!is_resource($p)) { throw new RuntimeException('Native child unavailable.'); }
    $pid=proc_get_status($p)['pid'];
    w04Save($log.'.ownership.json',['pid'=>$pid,'cwd'=>w04fRoot(),'command'=>array_map(static fn($x)=>str_replace(w04fRoot().'/', '',$x),$command),'state'=>'running','utc'=>gmdate('c')]);
    try { $exit=proc_close($p); } finally {
        w04Env(true); $cache=w04fRoot().'/bootstrap/cache/config.php'; if(is_file($cache)) { unlink($cache); }
    }
    $result=['command'=>array_map(static fn($x)=>str_replace(w04fRoot().'/', '',$x),$command),'pid'=>$pid,'exit'=>$exit,'process_gone'=>!file_exists('/proc/'.$pid),'seconds'=>round(microtime(true)-$start,3),'utc'=>gmdate('c')];
    w04Save($log.'.result.json',$result);
    if(!$result['process_gone']) { throw new RuntimeException('Native child PID still present; no next mutation.'); }
    w04fForeignHandles();
    w04fQuiesce();
    return $exit;
}
function w04fCompleted(bool $completed): void {
    $root=w04fRoot(); $old=[]; $value=$completed?'true':'false';
    foreach(['.env','.env.testing'] as $n) { $old[$n]=file_get_contents($root.'/'.$n); }
    try {
        foreach($old as $n=>$bytes) {
            $updated=preg_replace('/^INSTALLER_COMPLETED=(true|false)$/m','INSTALLER_COMPLETED='.$value,$bytes,-1,$count);
            if($count!==1 || file_put_contents($root.'/'.$n,$updated)!==strlen($updated) || !chmod($root.'/'.$n,0600)) { throw new RuntimeException('Own completion update failed.'); }
        }
    } catch(Throwable $e) {
        foreach($old as $n=>$bytes) { file_put_contents($root.'/'.$n,$bytes);chmod($root.'/'.$n,0600); }
        throw $e;
    }
    putenv('INSTALLER_COMPLETED='.$value);$_ENV['INSTALLER_COMPLETED']=$value;$_SERVER['INSTALLER_COMPLETED']=$value;
}
/** 전체 테이블을 비교하며 명시된 native cache/audit/token delta만 허용한다. */
function w04fCompare(array $before,array $after,array $allowed=[]): array {
    $diff=[];$ddl=[];
    foreach(array_unique([...array_keys($before['tables']),...array_keys($after['tables'])]) as $table) {
        $b=$before['tables'][$table]??null;$a=$after['tables'][$table]??null;
        if($b!==$a) { $diff[$table]=['before'=>$b,'after'=>$a,'allowed_native_delta'=>in_array($table,$allowed,true)]; }
        if(($b['ddl_sha256']??null)!==($a['ddl_sha256']??null)) { $ddl[]=$table; }
    }
    return ['before_digest'=>w04fDigest($before),'after_digest'=>w04fDigest($after),'exact_all_tables'=>$before===$after,
        'table_set_equal'=>array_keys($before['tables'])===array_keys($after['tables']),
        'ddl_equal'=>$ddl===[], 'ddl_changed_tables'=>$ddl,'changed_tables'=>$diff,
        'all_nonallowed_tables_equal'=>array_diff(array_keys($diff),$allowed)===[], 'allowed_tables'=>$allowed];
}
function w04fMysqlOptions(string $path): void {
    $env=w04Env();
    $escape=static fn($v)=>'"'.str_replace(['\\','"',"\n","\r"],['\\\\','\\"','\\n','\\r'],$v).'"';
    $bytes="[client]\nprotocol=tcp\nhost=127.0.0.1\nport=3306\nuser=".$escape($env['DB_WRITE_USERNAME'])."\npassword=".$escape($env['DB_WRITE_PASSWORD'])."\n";
    if(file_put_contents($path,$bytes)!==strlen($bytes)||!chmod($path,0600)) { throw new RuntimeException('Private mysql options failed.'); }
}
