"""W04 atomic contention (nonauthor, fixed fa552317) shared probe helpers. Derived from the earlier nonauthor w04-final-security helpers.

All secrets (bearer tokens, passwords, emails) are read from the private 0600 handoff in-process only and are scrubbed
from every record. Response bodies stay in memory; records keep status, safe headers, integer IDs, statuses, amounts
and timings. Contacts are generated in-process and never written.
"""
import hashlib, http.client, json, os, stat, subprocess, sys, threading, time, urllib.parse

REVIEW_SHA = '5783e6ba124061bdfae639cdaf9b1c14a83cdf03'
HERE = os.path.dirname(os.path.abspath(__file__))
TL, EC, BD = '/api/modules/raonslab-travel_lab', '/api/modules/sirsoft-ecommerce', '/api/modules/sirsoft-board'


class Ctx:
    def __init__(self, private_dir, out_dir, phase):
        acc = os.path.join(private_dir, 'access.json')
        for p in (acc, os.path.join(private_dir, 'database-access.json')):
            st = os.stat(p)
            assert stat.S_IMODE(st.st_mode) == 0o600, 'private file mode'
        assert stat.S_IMODE(os.stat(private_dir).st_mode) == 0o700, 'private dir mode'
        self.A = json.load(open(acc))
        assert self.A['source_sha'] == REVIEW_SHA and self.A['db'] == 'req81_travel_lab_test', 'handoff target mismatch'
        assert self.A['base_url'] == 'http://127.0.0.1:18880', 'loopback only'
        assert time.time() < time.mktime(time.strptime(self.A['expires_at'][:19], '%Y-%m-%dT%H:%M:%S')) - time.timezone - 60, 'handoff expired'
        self.private_dir, self.out_dir, self.phase = private_dir, out_dir, phase
        self.host, self.port = '127.0.0.1', 18880
        self.secrets = [v for r in ('member', 'other_member', 'admin') for k, v in self.A[r].items() if k != 'user_id' and isinstance(v, str)]
        self.tokens = {r: self.A[r]['bearer_token'] for r in ('member', 'other_member', 'admin')}
        self.uid = {r: int(self.A[r]['user_id']) for r in ('member', 'other_member', 'admin')}
        self.results = []
        self.http_checks = []
        self.lock = threading.Lock()
        os.makedirs(out_dir, mode=0o700, exist_ok=True)
        self.out = os.path.join(out_dir, f'{phase}.json')
        self.extra = {}

    def add_secret(self, v):
        if v:
            self.secrets.append(v)

    def scrub(self, x):
        s = json.dumps(x, ensure_ascii=False, default=str)
        for v in sorted(set(self.secrets), key=len, reverse=True):
            if v:
                s = s.replace(v, '[private]')
        return json.loads(s)

    def flush(self):
        counts = {s: sum(1 for r in self.results if r['status'] == s) for s in ('PASS', 'FAIL', 'OBSERVED', 'BLOCKED', 'NOT_RUN')}
        with open(self.out, 'w') as f:
            json.dump(self.scrub({'phase': self.phase, 'review_sha': REVIEW_SHA, 'base_url': self.A['base_url'], 'counts': counts,
                                  'results': self.results, 'http_checks': self.http_checks, 'actual_http_count':len(self.http_checks), **self.extra}), f, ensure_ascii=False, indent=1)
        os.chmod(self.out, 0o600)

    def req(self, method, path, role=None, body=None, headers=None, token=None, raw=None, ctype=None, timeout=120):
        h = {'Accept': 'application/json', 'Accept-Language': 'en'}
        tok = token if token is not None else (self.tokens[role] if role else None)
        if tok:
            h['Authorization'] = 'Bearer ' + tok
        data = None
        if raw is not None:
            data, h['Content-Type'] = raw, ctype
        elif body is not None:
            data, h['Content-Type'] = json.dumps(body).encode(), 'application/json'
        h.update(headers or {})
        c = http.client.HTTPConnection(self.host, self.port, timeout=timeout)
        t0 = time.time()
        c.request(method, path, body=data, headers=h)
        r = c.getresponse()
        b = r.read()
        t1 = time.time()
        hd = {k.lower(): v for k, v in r.getheaders()}
        c.close()
        try:
            j = json.loads(b or b'null')
        except ValueError:
            j = {'_non_json_bytes': len(b), '_sha256': hashlib.sha256(b).hexdigest()}
        meta = {'t0': t0, 't1': t1, 'ms': round((t1 - t0) * 1000, 1), 'limit': hd.get('x-ratelimit-limit'), 'remaining': hd.get('x-ratelimit-remaining'),
                'retry_after': hd.get('retry-after'), 'date': hd.get('date'), 'bytes': len(b)}
        with self.lock:
            self.http_checks.append({'method':method,'path':path,'actor':role,'status':r.status,'seconds':round(t1-t0,3)})
        return r.status, j, meta

    def record(self, name, status, detail):
        with self.lock:
            self.results.append({'name': name, 'status': status, 'detail': detail, 'at': time.strftime('%H:%M:%S', time.gmtime())})
            print(status, name, flush=True)
            self.flush()

    def step(self, name, fn):
        try:
            status, detail = fn()
        except AssertionError as e:
            status, detail = 'FAIL', {'assert': str(e)[:2000]}
        except Exception as e:  # noqa: BLE001 - evidence records the failure instead of aborting
            status, detail = 'FAIL', {'error': type(e).__name__ + ': ' + str(e)[:2000]}
        self.record(name, status, detail)
        return detail

    def db(self, mode, args=None):
        cmd = ['php', os.path.join(HERE, 'db_guard.php'), self.private_dir, mode, json.dumps(args or {})]
        r = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
        if r.returncode != 0:
            raise RuntimeError('db_guard ' + mode + ' exit ' + str(r.returncode) + ' ' + r.stderr.strip()[:300])
        return json.loads(r.stdout.strip().splitlines()[-1])

    def dbq(self, name, **params):
        return self.db('query', {'name': name, 'params': params})

    def deadlocks(self):
        return self.dbq('deadlocks')['innodb_deadlocks_global']

    def cache(self, prefixes, roles):
        return {(k['prefix'], k['user_id']): k for k in self.dbq('cache_keys', prefixes=prefixes, users=[self.uid[r] for r in roles])['keys']}


def check(cond, msg):
    if not cond:
        raise AssertionError(msg if isinstance(msg, str) else json.dumps(msg, default=str)[:1500])


def msg(j):
    return j.get('message') if isinstance(j, dict) else None


def data(j):
    return j.get('data') if isinstance(j, dict) else None


def rows(j):
    d = data(j)
    if isinstance(d, dict):
        for k in ('data', 'items'):
            if isinstance(d.get(k), list):
                return d[k]
    return d if isinstance(d, list) else []
