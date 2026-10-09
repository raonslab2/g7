<?php
// Own loopback PHP server on 127.0.0.1:18874 (TEST-bound, --no-reload) for real HTTP across process
// restart and own-env loss recovery (req_caef46f3). Parent APP preview 18871 is never touched.
// Only this script's own setsid process group is signalled. Logs method/path/status only.
require __DIR__.'/guard.php';
require __DIR__.'/runtime-bootstrap.php';
require_once dirname(__DIR__, 3).'/scripts/travel-lab/live-fixtures.php';
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

const W04F_PORT = 18874;
$label = $argv[1] ?? 'original';
w04fRequireSnapshot($label);
$root = w04fRoot();
$serverDir = w04fPrivate('server');
if (!is_dir($serverDir)) { mkdir($serverDir, 0700); }
$evidence = w04fEvidence('server');
$records = []; $processes = []; $result = ['status' => 'FAIL'];
$save = static function () use (&$records, &$processes, &$result, $evidence) {
    w04Save($evidence.'/http-statuses.json', ['requests' => $records]);
    w04Save($evidence.'/processes.json', ['processes' => $processes]);
    w04Save($evidence.'/result.json', $result);
};
function w04fPortOpen(): bool
{
    $s = @fsockopen('127.0.0.1', W04F_PORT, $errno, $errstr, 0.3);
    if ($s) { fclose($s); return true; }
    return false;
}
function w04fGroupPids(int $pgid): array
{
    $pids = [];
    foreach (glob('/proc/[0-9]*/stat') as $stat) {
        $raw = (string) @file_get_contents($stat);
        $after = strrpos($raw, ')');
        if ($after === false) { continue; }
        $fields = explode(' ', substr($raw, $after + 2));
        if ((int) ($fields[2] ?? -1) === $pgid) { $pids[] = (int) explode(' ', $raw)[0]; }
    }
    return $pids;
}
function w04fStart(string $why): array
{
    global $serverDir, $root, $processes;
    if (w04fPortOpen()) { throw new RuntimeException('Port 18874 already in use; refusing foreign process.'); }
    $env = w04Env(); $env['APP_ENV'] = 'local';
    $log = $serverDir.'/server-'.count($processes).'.log';
    $proc = proc_open(['setsid', PHP_BINARY, '-S', '127.0.0.1:'.W04F_PORT, '-t', $root.'/public', __DIR__.'/http-entry.php'], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes, $root, $env);
    if(!is_resource($proc)) { throw new RuntimeException('Own HTTP process unavailable.'); }
    $pid = proc_get_status($proc)['pid'];
    $GLOBALS['pendingServer']=['proc'=>$proc,'pgid'=>$pid];
    w04Save($serverDir.'/ownership.json',['pid'=>$pid,'cwd'=>$root,'argv'=>[PHP_BINARY,'-S','127.0.0.1:'.W04F_PORT,'-t',$root.'/public',__DIR__.'/http-entry.php'],'state'=>'starting','utc'=>gmdate('c')]);
    file_put_contents($serverDir.'/pgid', (string) $pid); chmod($serverDir.'/pgid', 0600);
    for ($i = 0; $i < 100 && !w04fPortOpen(); $i++) { usleep(100000); }
    if (!w04fPortOpen()) { throw new RuntimeException('Own server did not start.'); }
    $raw = (string) file_get_contents('/proc/'.$pid.'/stat');
    $fields = explode(' ', substr($raw, strrpos($raw, ')') + 2));
    $pgid = (int) $fields[2];
    if ($pgid !== $pid) { throw new RuntimeException('Own server is not its own process group.'); }
    $entry = ['event' => 'start', 'why' => $why, 'launcher_pid' => $pid, 'pgid' => $pgid, 'group_pids' => w04fGroupPids($pgid), 'utc' => gmdate('c')];
    $processes[] = $entry;
    $GLOBALS['pendingServer']=null;
    return ['proc' => $proc, 'pgid' => $pgid];
}
function w04fStop(array $server, string $why): void
{
    global $processes, $serverDir;
    $pgid = $server['pgid'];
    $before = w04fGroupPids($pgid);
    foreach($before as $pid) {
        $cwd=@readlink('/proc/'.$pid.'/cwd'); $cmd=(string)@file_get_contents('/proc/'.$pid.'/cmdline');
        if($cwd!==w04fRoot() || !str_contains($cmd,'http-entry.php')) { throw new RuntimeException('Unowned HTTP process refusing signal.'); }
    }
    posix_kill(-$pgid, SIGTERM);
    $exit = proc_close($server['proc']);
    for ($i = 0; $i < 30 && w04fGroupPids($pgid) !== []; $i++) { usleep(100000); }
    $remaining = w04fGroupPids($pgid);
    $processes[] = ['event' => 'stop', 'why' => $why, 'pgid' => $pgid, 'signalled_pids' => $before, 'remaining_pids' => $remaining, 'port_closed' => !w04fPortOpen(), 'launcher_exit' => $exit, 'utc' => gmdate('c')];
    if ($remaining !== [] || w04fPortOpen()) { throw new RuntimeException('Own server did not stop.'); }
    if(is_file($serverDir.'/pgid')) { unlink($serverDir.'/pgid'); }
    w04Save($serverDir.'/ownership.json',['pgid'=>$pgid,'state'=>'completed','remaining_pids'=>$remaining,'utc'=>gmdate('c')]);
}
function w04fHttp(string $method, string $path, array $body = [], ?string $token = null, int $expected = 200, string $phase = ''): array
{
    global $records;
    $ch = curl_init('http://127.0.0.1:'.W04F_PORT.$path);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token !== null) { $headers[] = 'Authorization: Bearer '.$token; }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*', CURLOPT_TIMEOUT => 60]);
    if ($body !== []) { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR)); }
    $content = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $records[] = ['phase' => $phase, 'method' => $method, 'path' => $path, 'status' => $status, 'expected' => $expected];
    if ($status !== $expected) { throw new RuntimeException('HTTP status mismatch '.$method.' '.$path.': '.$status); }
    return is_string($content) && $content !== '' ? (json_decode($content, true) ?? []) : [];
}
$server = null;
try {
    $app = w04App();
    $env = w04Env();
    $api = '/api/modules/raonslab-travel_lab';
    // Synthetic actors through native UserService under the installer super administrator.
    $super = User::where('email', $env['INSTALLER_ADMIN_EMAIL'])->firstOrFail();
    $run = bin2hex(random_bytes(6));
    $actors = []; $passwords = [];
    foreach (['owner' => 'user', 'admin' => 'admin'] as $who => $roleName) {
        Auth::forgetGuards(); Auth::setUser($super);
        $passwords[$who] = bin2hex(random_bytes(18));
        $actors[$who] = app(App\Services\UserService::class)->createUser(['name' => 'W04F server '.$who, 'email' => 'w04f-srv-'.$run.'-'.$who.'@example.invalid', 'password' => $passwords[$who], 'email_verified_at' => now(), 'language' => 'ko', 'role_ids' => [Role::where('identifier', $roleName)->value('id')]]);
    }
    travelLabCheck(!$actors['admin']->is_super, 'Server admin unexpectedly super.');
    Auth::forgetGuards(); Auth::setUser($super);
    $departure = DB::transaction(fn () => travelLabDeparture('srv'.$run, 3));
    Auth::forgetGuards();
    $state = static function () use (&$departure, &$inquiryId) {
        $inq = $inquiryId ? Modules\Raonslab\TravelLab\Models\Inquiry::find($inquiryId) : null;
        return ['inquiry_status' => $inq?->status instanceof BackedEnum ? $inq->status->value : $inq?->status, 'inquiry_total' => $inq ? (int) $inq->total_amount : null, 'reserved' => (int) $departure->fresh()->reserved,
            'owner_tokens' => (int) DB::table('personal_access_tokens')->where('tokenable_id', $GLOBALS['actors']['owner']->id)->count()];
    };
    $inquiryId = null;
    DB::purge();

    // Phase A: first own server process.
    $server = w04fStart('initial');
    $owner = w04fHttp('POST', '/api/auth/login', ['email' => $actors['owner']->email, 'password' => $passwords['owner']], null, 200, 'A')['data']['token'];
    $admin = w04fHttp('POST', '/api/auth/admin/login', ['email' => $actors['admin']->email, 'password' => $passwords['admin']], null, 200, 'A')['data']['token'];
    w04fHttp('GET', $api.'/cart', [], null, 401, 'A');
    $cart = w04fHttp('POST', $api.'/cart', ['departure_id' => $departure->id, 'quantity' => 2], $owner, 201, 'A');
    travelLabCheck((int) $cart['data']['totals']['final_amount'] === 24000, 'Server price differs.');
    $body = ['cart_ids' => [$cart['data']['items'][0]['id']], 'contact' => ['name' => 'W04F synthetic traveler'], 'idempotency_key' => 'w04f-srv-'.$run];
    $created = w04fHttp('POST', $api.'/inquiries', $body, $owner, 201, 'A');
    $inquiryId = $created['data']['id'];
    travelLabCheck($created['data']['status'] === 'TEST_INQUIRY' && (int) $created['data']['total_amount'] === 24000, 'Inquiry create differs.');
    $replay = w04fHttp('POST', $api.'/inquiries', $body, $owner, 200, 'A');
    travelLabCheck($replay['data']['id'] === $inquiryId, 'Replay differs.');
    w04fHttp('PATCH', $api.'/admin/inquiries/'.$inquiryId, ['status' => 'UNDER_REVIEW'], $admin, 200, 'A');
    w04fHttp('PATCH', $api.'/admin/inquiries/'.$inquiryId, ['status' => 'TEST_ACCEPTED'], $admin, 200, 'A');
    w04fHttp('POST', '/api/auth/logout', [], $owner, 200, 'A');
    w04fHttp('GET', $api.'/inquiries/'.$inquiryId, [], $owner, 401, 'A');
    $stateA = $state(); DB::purge();
    travelLabCheck($stateA['inquiry_status'] === 'TEST_ACCEPTED' && $stateA['reserved'] === 2 && $stateA['inquiry_total'] === 24000, 'Phase A state differs.');
    w04fStop($server, 'process restart'); $server = null;

    $fullAfterA=w04Measure(w04Pdo());
    w04Save($evidence.'/after-A.json',$fullAfterA);
    // Phase B: new own server process; persisted state and tokens.
    $server = w04fStart('restart');
    $fullRestart=w04Measure(w04Pdo());
    $restartComparison=w04fCompare($fullAfterA,$fullRestart,['g7_cache']);
    travelLabCheck($restartComparison['all_nonallowed_tables_equal'] && $restartComparison['ddl_equal'],'Restart changed persistent domain records.');
    w04Save($evidence.'/restart-comparison.json',$restartComparison);
    w04fHttp('GET', $api.'/inquiries/'.$inquiryId, [], $owner, 401, 'B');
    w04fHttp('GET', '/api/admin/auth/user', [], $admin, 200, 'B');
    $owner = w04fHttp('POST', '/api/auth/login', ['email' => $actors['owner']->email, 'password' => $passwords['owner']], null, 200, 'B')['data']['token'];
    $seen = w04fHttp('GET', $api.'/inquiries/'.$inquiryId, [], $owner, 200, 'B');
    travelLabCheck($seen['data']['status'] === 'TEST_ACCEPTED' && (int) $seen['data']['total_amount'] === 24000, 'Persisted inquiry differs after restart.');
    $stateB = $state(); DB::purge();
    travelLabCheck($stateB['inquiry_status'] === 'TEST_ACCEPTED' && $stateB['reserved'] === 2, 'Phase B state differs.');
    w04fStop($server, 'own env-loss recovery exercise'); $server = null;

    $fullBeforeLoss=w04Measure(w04Pdo());
    w04Save($evidence.'/before-env-loss.json',$fullBeforeLoss);
    // Phase C: own env loss. Preserve exact 0600 copies privately, remove only own generated env files.
    $envCopy = w04fPrivate('env-copy');
    if (file_exists($envCopy)) { throw new RuntimeException('Env copy exists.'); }
    mkdir($envCopy, 0700);
    $hashes = [];
    foreach (['.env', '.env.testing'] as $name) {
        $hashes[$name] = hash_file('sha256', $root.'/'.$name);
        copy($root.'/'.$name, $envCopy.'/'.$name); chmod($envCopy.'/'.$name, 0600);
        travelLabCheck(hash_file('sha256', $envCopy.'/'.$name) === $hashes[$name], 'Private env copy differs.');
    }
    $stateBeforeLoss = $state(); DB::purge();
    foreach (['.env', '.env.testing'] as $name) { unlink($root.'/'.$name); }
    $refused = [];
    foreach (['guard' => [PHP_BINARY, '-r', 'require "'.__DIR__.'/guard.php"; w04Env();'], 'guarded-artisan' => [PHP_BINARY, __DIR__.'/guarded-artisan.php', 'about']] as $what => $cmd) {
        $p = proc_open($cmd, [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $root, getenv());
        $refused[$what] = proc_close($p);
    }
    $startRefused = false;
    try { w04fStart('must refuse without env'); } catch (Throwable $e) { $startRefused = true; }
    travelLabCheck($refused['guard'] !== 0 && $refused['guarded-artisan'] !== 0 && $startRefused && !w04fPortOpen(), 'Unsafe boot was not refused.');
    foreach (['.env', '.env.testing'] as $name) {
        copy($envCopy.'/'.$name, $root.'/'.$name); chmod($root.'/'.$name, 0600);
    }
    $restored = [];
    foreach (['.env', '.env.testing'] as $name) { $restored[$name] = hash_file('sha256', $root.'/'.$name) === $hashes[$name] && (fileperms($root.'/'.$name) & 0777) === 0600; }
    travelLabCheck(!in_array(false, $restored, true), 'Env restoration differs.');
    w04Env();
    $fullAfterLoss=w04Measure(w04Pdo());
    travelLabCheck($fullBeforeLoss===$fullAfterLoss,'Env loss/recreation changed full database inventory.');
    w04Save($evidence.'/env-loss-comparison.json',w04fCompare($fullBeforeLoss,$fullAfterLoss));
    $server = w04fStart('after own env recovery');
    $owner2 = w04fHttp('POST', '/api/auth/login', ['email' => $actors['owner']->email, 'password' => $passwords['owner']], null, 200, 'C');
    w04fHttp('GET', $api.'/inquiries/'.$inquiryId, [], $owner, 200, 'C');
    $cancel = w04fHttp('POST', $api.'/inquiries/'.$inquiryId.'/cancel', [], $owner, 200, 'C');
    travelLabCheck($cancel['data']['status'] === 'CANCELLED', 'Cancel after recovery failed.');
    w04fHttp('POST', '/api/auth/logout', [], $owner2['data']['token'], 200, 'C');
    w04fHttp('POST', '/api/admin/auth/logout', [], $admin, 200, 'C');
    w04fHttp('GET', '/api/admin/auth/user', [], $admin, 401, 'C');
    $stateC = $state(); DB::purge();
    travelLabCheck($stateC['inquiry_status'] === 'CANCELLED' && $stateC['reserved'] === 0, 'Phase C state differs.');
    w04fStop($server, 'final'); $server = null;
    foreach (glob($envCopy.'/*') as $f) { unlink($f); } rmdir($envCopy);
    $fullFinal=w04Measure(w04Pdo());
    w04Save($evidence.'/after-C.json',$fullFinal);
    copy($serverDir.'/last-runtime-proof.json',$evidence.'/runtime-cache-proof.json');
    $source=json_decode(file_get_contents(__DIR__.'/evidence/intake/source.json'),true);
    $sourceEqual=true;foreach($source['files'] as $path=>$hash) { $sourceEqual=$sourceEqual && hash_file('sha256',$root.'/'.$path)===$hash; }
    travelLabCheck($sourceEqual,'Source/bundles changed across restart/env recovery.');
    w04Save($evidence.'/source-bundle-persistence.json',['source_sha'=>$source['tested_sha'],'tree'=>$source['tested_tree'],'file_count'=>$source['count'],'fixed_source_bundle_hashes_equal'=>$sourceEqual,'database_full_comparisons'=>['restart'=>$restartComparison,'env_loss'=>w04fCompare($fullBeforeLoss,$fullAfterLoss)],'intended_HTTP_deltas'=>'login/logout tokens and last_used_at; native cache/admission counters; native inquiry/capacity/event/audit changes']);
    $result = ['status' => 'PASS', 'port' => W04F_PORT, 'requests' => count($records), 'server_starts' => count(array_filter($processes, fn ($p) => $p['event'] === 'start')),
        'state_after_A' => $stateA, 'state_after_restart_B' => $stateB, 'state_before_env_loss' => $stateBeforeLoss, 'state_after_env_recovery_C' => $stateC,
        'env_loss' => ['own_files_removed' => ['.env', '.env.testing'], 'guard_exit' => $refused['guard'], 'guarded_artisan_exit' => $refused['guarded-artisan'], 'server_start_refused' => $startRefused, 'restored_exact_0600' => $restored, 'private_copy_removed_after_verification' => true],
        'server_price' => 24000, 'parent_preview_18871_untouched' => true];
    $save();
    echo 'PASS own loopback server HTTP requests='.count($records)."\n";
    exit(0);
} catch (Throwable $e) {
    $cleanupServer=$server ?? ($GLOBALS['pendingServer']??null);
    if ($cleanupServer !== null) { try { w04fStop($cleanupServer, 'failure cleanup'); } catch (Throwable) {} }
    if(isset($envCopy) && is_dir($envCopy)) {
        foreach(['.env','.env.testing'] as $n) {
            if(is_file($envCopy.'/'.$n) && !is_link($root.'/'.$n)) { copy($envCopy.'/'.$n,$root.'/'.$n);chmod($root.'/'.$n,0600); }
        }
    }
    file_put_contents($root.'/storage/framework/testing/w04f-private/server-private-error.txt', $e->getMessage()."\n".$e->getTraceAsString());
    $result = ['status' => 'FAIL', 'class' => get_class($e), 'requests_completed' => count($records)];
    $save();
    fwrite(STDERR, 'FAIL own server probe ('.get_class($e).")\n");
    exit(1);
}
