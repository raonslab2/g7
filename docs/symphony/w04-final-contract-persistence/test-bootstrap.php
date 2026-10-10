<?php
/** TEST-only native entrypoint: retain all original tests/bootstrap.php guards. */
require_once __DIR__.'/guard.php';
$w04pEnv=w04Env(false,true); travelLabApplyEnvironment($w04pEnv);
$w04pRoot=w04fRoot(); $w04pEnvPath=$w04pRoot.'/.env'; $w04pBytes=file_get_contents($w04pEnvPath);
$w04pFixture=Dotenv\Dotenv::parse(file_get_contents($w04pRoot.'/.env.testing'));
if($w04pFixture['CACHE_STORE']!=='array' || $w04pFixture['DB_WRITE_DATABASE']!=='req81_travel_lab_test') { throw new RuntimeException('Ordinary fixture boundary.'); }
$w04pRestore=static function() use($w04pEnvPath,$w04pBytes): void {
    if(is_link($w04pEnvPath)) { throw new RuntimeException('Own env symlink refused.'); }
    clearstatcache(true,$w04pEnvPath);
    if(!is_file($w04pEnvPath)||file_get_contents($w04pEnvPath)!==$w04pBytes) { if(file_put_contents($w04pEnvPath,$w04pBytes)!==strlen($w04pBytes)) { throw new RuntimeException('Own env short restoration refused.'); } }
    if(!chmod($w04pEnvPath,0600) || file_get_contents($w04pEnvPath)!==$w04pBytes) { throw new RuntimeException('Own env restore failed.'); }
};
register_shutdown_function($w04pRestore);
if(!unlink($w04pEnvPath)) { throw new RuntimeException('Own TEST-only env hide failed.'); }
try { require $w04pRoot.'/tests/bootstrap.php'; } finally { $w04pRestore(); }
clearstatcache();
if(file_get_contents($w04pEnvPath)!==$w04pBytes || (fileperms($w04pEnvPath)&0777)!==0600 || (fileperms($w04pRoot.'/.env.testing')&0777)!==0600) { throw new RuntimeException('Pre-Laravel restored files failed.'); }
// Root bootstrap chooses its native test cache file aliases; apply no DB/cache driver override here.
echo "W04P native TEST bootstrap: own local env restored; ordinary array fixture; original guards retained.\n";

Illuminate\Database\Connection::resolverFor('mysql',static fn($pdo,$database,$prefix,$config)=>new W04NativeMysqlConnection($pdo,$database,$prefix,$config));
