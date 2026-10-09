"""W04 common-binding recheck: private question attachment probe (independent NONAUTHOR, fixed fc54b6ae).

Derived from docs/symphony/w04-private-attachment/probe.py (992f9a65 reviewer); old results preserved there unchanged.
Adds: nonimage preview 400 per actor and locale (ko/en), translated message, wrong-board / nonexistent-hash 404 controls,
supplied-token unchanged read.

Usage: python3 -I probe.py <private_handoff_dir> <private_state_dir> <public_evidence_dir> <parent_runtime_root>

Secrets (emails, passwords, bearer tokens) are read in-process from the 0600 handoff and scrubbed from every record.
The synthetic filenames and file bytes are recorded only as sha256. The parent runtime is read-only (stat + sha256 of
this probe's own uploaded files). No SQL, no settings, no cache, no service operations.
"""
import hashlib, http.client, json, os, stat, struct, sys, time, uuid, zlib, glob, re

REVIEW_SHA = 'fc54b6ae091cd6cef2d0fabc48d2ec4fe4fdba8c'
TL, BD = '/api/modules/raonslab-travel_lab', '/api/modules/sirsoft-board'
SLUG, OTHER_SLUG = 'travel-lab-questions', 'travel-lab-notices'
PRIV, STATE, OUT, PARENT = sys.argv[1:5]

acc = os.path.join(PRIV, 'access.json')
assert stat.S_IMODE(os.stat(acc).st_mode) == 0o600 and stat.S_IMODE(os.stat(PRIV).st_mode) == 0o700, 'handoff mode'
A = json.load(open(acc))
assert A['base_url'] == 'http://127.0.0.1:18871', 'loopback only'
exp = time.mktime(time.strptime(A['expires_at'][:19], '%Y-%m-%dT%H:%M:%S')) - time.timezone
assert time.time() < exp - 300, 'handoff near expiry'
SECRETS = [v for r in ('member', 'other_member', 'admin') for k, v in A[r].items() if k != 'user_id' and isinstance(v, str) and v]
RUN = 'w04cb-' + uuid.uuid4().hex[:10]
RESULTS, STATE_D = [], {'run': RUN, 'tokens_issued': {}, 'attachments': [], 'question': None}
os.makedirs(STATE, mode=0o700, exist_ok=True)


def scrub(x):
    s = json.dumps(x, ensure_ascii=False, default=str)
    for v in sorted(set(SECRETS), key=len, reverse=True):
        s = s.replace(v, '[private]')
    return json.loads(s)


def flush():
    counts = {k: sum(1 for r in RESULTS if r['status'] == k) for k in ('PASS', 'FAIL', 'OBSERVED', 'BLOCKED', 'NOT_RUN')}
    with open(os.path.join(OUT, 'probe-results.json'), 'w') as f:
        json.dump(scrub({'run': RUN, 'review_sha': REVIEW_SHA, 'base_url': A['base_url'], 'counts': counts, 'results': RESULTS}), f, ensure_ascii=False, indent=1)
    with open(os.path.join(STATE, 'state.json'), 'w') as f:
        json.dump(STATE_D, f)
    os.chmod(os.path.join(STATE, 'state.json'), 0o600)


def req(method, path, token=None, body=None, raw=None, ctype=None, accept='application/json', lang='en'):
    h = {'Accept': accept, 'Accept-Language': lang}
    if token:
        h['Authorization'] = 'Bearer ' + token
    data = None
    if raw is not None:
        data, h['Content-Type'] = raw, ctype
    elif body is not None:
        data, h['Content-Type'] = json.dumps(body).encode(), 'application/json'
    c = http.client.HTTPConnection('127.0.0.1', 18871, timeout=60)
    t0 = time.time()
    c.request(method, path, body=data, headers=h)
    r = c.getresponse()
    b = r.read()
    ms = round((time.time() - t0) * 1000, 1)
    hd = {k.lower(): v for k, v in r.getheaders()}
    c.close()
    try:
        j = json.loads(b or b'null')
    except ValueError:
        j = None
    return {'status': r.status, 'json': j, 'bytes': b, 'ctype': hd.get('content-type'), 'disp': hd.get('content-disposition'), 'ms': ms}


def summary(r, fixtures=()):
    """Safe record: status, type, length, sha256 and leak flags (never the body)."""
    b = r['bytes']
    leaks = {name: (needle in b) for name, needle in fixtures if needle}
    return {'status': r['status'], 'ctype': (r['ctype'] or '').split(';')[0], 'len': len(b), 'sha256': hashlib.sha256(b).hexdigest(),
            'message': (r['json'] or {}).get('message') if isinstance(r['json'], dict) else None,
            'disposition_has_filename': bool(r['disp'] and 'filename' in r['disp']), 'leaks': leaks, 'ms': r['ms']}


def record(name, status, detail):
    RESULTS.append({'name': name, 'status': status, 'detail': detail, 'at': time.strftime('%H:%M:%S', time.gmtime())})
    print(status, name, flush=True)
    flush()


def step(name, fn):
    try:
        status, detail = fn()
    except AssertionError as e:
        status, detail = 'FAIL', {'assert': str(e)[:2500]}
    except Exception as e:  # noqa: BLE001
        status, detail = 'FAIL', {'error': type(e).__name__ + ': ' + str(e)[:2500]}
    record(name, status, detail)
    return detail


def check(c, d):
    if not c:
        raise AssertionError(json.dumps(scrub(d), default=str)[:2500])


def multipart(fields, fname, content, ctype):
    bnd = 'w04pa' + uuid.uuid4().hex
    parts = []
    for k, v in fields.items():
        parts.append(f'--{bnd}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
    parts.append(f'--{bnd}\r\nContent-Disposition: form-data; name="file"; filename="{fname}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + content + b'\r\n')
    parts.append(f'--{bnd}--\r\n'.encode())
    return b''.join(parts), 'multipart/form-data; boundary=' + bnd


def png(w=8, h=8):
    def chunk(t, d):
        return struct.pack('>I', len(d)) + t + d + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
    rawrows = b''.join(b'\x00' + bytes([(x * 30) % 256, (y * 30) % 256, 128] * 1)[:3] * 1 for y in range(h) for x in range(1)) if False else b''
    rows = b''.join(b'\x00' + b''.join(bytes([(x * 31) % 256, (y * 31) % 256, 200]) for x in range(w)) for y in range(h))
    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', w, h, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(rows)) + chunk(b'IEND', b'')


def sha(b):
    return hashlib.sha256(b).hexdigest()


def find_parent_file(stored):
    hits = glob.glob(os.path.join(PARENT, 'storage', 'app', '**', stored), recursive=True)
    return [{'rel': os.path.relpath(p, PARENT).replace(stored, '<stored_uuid_name>'), 'size': os.path.getsize(p), 'sha256': sha(open(p, 'rb').read()),
             'mode': oct(stat.S_IMODE(os.stat(p).st_mode))} for p in hits]


TOK = {}
record('preflight', 'PASS', {'handoff_source_sha_matches_review': A.get('source_sha') == REVIEW_SHA, 'handoff_source_sha_is_fa552_inherited': A.get('source_sha') == 'fa5523175ac494cfbd13bbf89bf06b3ec91835a6', 'expires_at': A['expires_at'],
                             'actors': {r: A[r]['user_id'] for r in ('member', 'other_member', 'admin')}, 'run': RUN})


def s_login():
    out = {}
    for r in ('member', 'other_member', 'admin'):
        x = req('POST', '/api/auth/login', body={'email': A[r]['email'], 'password': A[r]['password']})
        t = ((x['json'] or {}).get('data') or {}).get('token') if x['status'] == 200 else None
        if t:
            SECRETS.append(t)
            TOK[r] = t
            STATE_D['tokens_issued'][r] = True
        me = req('GET', '/api/auth/user', token=t) if t else {'status': None}
        uid = (((me.get('json') or {}).get('data') or {}).get('id')) if t else None
        out[r] = {'login': x['status'], 'token_issued': bool(t), 'me': me['status'], 'me_user_id_matches': uid == A[r]['user_id']}
    check(all(v['login'] == 200 and v['token_issued'] for v in out.values()), out)
    return 'PASS', out


step('native login issues own tokens for member / other_member / admin', s_login)
if len(TOK) != 3:
    sys.exit(2)
M, O, AD = TOK['member'], TOK['other_member'], TOK['admin']
Q = TL + '/support/questions'
BODY_MARK = 'W04CB synthetic body ' + RUN
TXT = ('W04CB inert synthetic text fixture ' + RUN + '\n').encode() * 4
PNG = png()
FN_TXT, FN_PNG = f'{RUN}-note.txt', f'{RUN}-pixel.png'
LEAKS = [('txt_bytes', TXT), ('png_bytes', PNG[8:]), ('txt_filename', FN_TXT.encode()), ('png_filename', FN_PNG.encode()), ('question_body', BODY_MARK.encode())]


def s_question():
    x = req('POST', Q, token=M, body={'title': '[합성] W04CB 첨부 재검증 ' + RUN[-6:], 'content': BODY_MARK})
    d = (x['json'] or {}).get('data') or {}
    check(x['status'] == 201 and d.get('is_secret') is True and d.get('is_mine') is True, summary(x))
    STATE_D['question'] = d['id']
    return 'PASS', {'status': 201, 'question_id': d['id'], 'is_secret': True, 'has_attachments_key': 'attachments' in d}


step('member creates own private synthetic question via support API', s_question)
QID = STATE_D['question']
if not QID:
    sys.exit(3)


def upload(fname, content, ctype, label):
    raw, ct = multipart({'post_id': QID}, fname, content, ctype)
    x = req('POST', f'{BD}/admin/board/{SLUG}/attachments', token=AD, raw=raw, ctype=ct)
    d = (((x['json'] or {}).get('data') or {}).get('data')) or {}
    if x['status'] != 201:
        return 'BLOCKED', {'admin_upload': summary(x), 'errors_keys': list(((x['json'] or {}).get('errors') or {}).keys())}
    att = {'label': label, 'id': d['id'], 'hash': d['hash'], 'mime': d['mime_type'], 'size': d['size'], 'upload_sha256': sha(content),
           'stored': d['stored_filename'], 'original_filename_echo_matches': d.get('original_filename') == fname,
           'url_is_gated_route': bool(re.search(r'/api/modules/sirsoft-board/boards/' + SLUG + r'/attachment/[A-Za-z0-9]{12}$', d.get('url') or ''))}
    SECRETS.append(d['stored_filename'])
    STATE_D['attachments'].append(att)
    on_disk = find_parent_file(d['stored_filename'])
    att['disk'] = on_disk
    pub = {k: v for k, v in att.items() if k != 'stored'}
    check(len(on_disk) == 1 and '/public/' not in on_disk[0]['rel'], pub)
    return 'PASS', pub


step('admin attaches inert text file to own question via native admin attachment API', lambda: upload(FN_TXT, TXT, 'text/plain', 'txt'))
step('admin attaches inert 8x8 PNG to own question via native admin attachment API', lambda: upload(FN_PNG, PNG, 'image/png', 'png'))
ATT = {a['label']: a for a in STATE_D['attachments']}


def disk_sha(a):
    return a['disk'][0]['sha256'] if a.get('disk') else None


def file_ok(x, a):
    return x['status'] == 200 and sha(x['bytes']) == disk_sha(a)


def pos():
    out, ok = {}, True
    for lab, a in ATT.items():
        h = a['hash']
        ad = req('GET', f'{BD}/admin/board/{SLUG}/attachments/download/{h}', token=AD, accept='*/*')
        out[f'{lab}.admin_download'] = summary(ad) | {'bytes_equal_disk': file_ok(ad, a), 'bytes_equal_upload': sha(ad['bytes']) == a['upload_sha256']}
        ok &= file_ok(ad, a)
        ud = req('GET', f'{BD}/boards/{SLUG}/attachment/{h}', token=AD, accept='*/*')
        out[f'{lab}.admin_token_user_download'] = summary(ud) | {'bytes_equal_disk': file_ok(ud, a)}
        md = req('GET', f'{BD}/boards/{SLUG}/attachment/{h}', token=M, accept='*/*')
        out[f'{lab}.owner_user_download'] = summary(md, LEAKS) | {'bytes_equal_disk': file_ok(md, a)}
        mad = req('GET', f'{BD}/admin/board/{SLUG}/attachments/download/{h}', token=M, accept='*/*')
        out[f'{lab}.owner_admin_download'] = summary(mad, LEAKS)
        if lab == 'png':
            for who, t in (('admin', AD), ('owner', M)):
                pv = req('GET', f'{BD}/boards/{SLUG}/attachment/{h}/preview', token=t, accept='*/*')
                out[f'png.{who}_preview'] = summary(pv) | {'bytes_equal_disk': file_ok(pv, a)}
            ok &= out['png.owner_preview']['bytes_equal_disk']
    show = req('GET', f'{BD}/admin/board/{SLUG}/posts/{QID}', token=AD)
    atts = (((show['json'] or {}).get('data') or {}).get('attachments')) or []
    out['admin_post_show'] = {'status': show['status'], 'attachment_ids': sorted(x.get('id') for x in atts),
                              'png_preview_url_signed': any('signature=' in (x.get('preview_url') or '') for x in atts if x.get('is_image'))}
    for x in atts:
        if x.get('is_image') and x.get('preview_url'):
            STATE_D['signed_preview'] = x['preview_url']
    sup = req('GET', f'{Q}/{QID}', token=M)
    sd = (sup['json'] or {}).get('data') or {}
    out['owner_support_show'] = {'status': sup['status'], 'attachments_key_present': 'attachments' in sd, 'leaks': {k: (v in sup['bytes']) for k, v in LEAKS if k != 'question_body'}}
    ok &= show['status'] == 200 and sorted(x.get('id') for x in atts) == sorted(a['id'] for a in ATT.values())
    check(ok, out)
    return 'PASS', out


if len(ATT) == 2:
    step('positive controls: admin native download bytes == on-disk sha; owner image preview bytes == on-disk sha; admin listing includes both', pos)
else:
    record('positive and foreign attachment checks', 'BLOCKED', {'reason': 'admin upload did not create both fixtures; see upload records'})


def foreign():
    out, bad = {}, []
    actors = (('other_member', O), ('guest', None))
    for lab, a in ATT.items():
        h = a['hash']
        routes = {'user_download': f'{BD}/boards/{SLUG}/attachment/{h}', 'preview': f'{BD}/boards/{SLUG}/attachment/{h}/preview',
                  'admin_download': f'{BD}/admin/board/{SLUG}/attachments/download/{h}',
                  'wrong_slug_preview': f'{BD}/boards/{OTHER_SLUG}/attachment/{h}/preview', 'wrong_slug_download': f'{BD}/boards/{OTHER_SLUG}/attachment/{h}'}
        for who, t in actors:
            for rn, p in routes.items():
                x = req('GET', p, token=t, accept='*/*')
                s = summary(x, LEAKS)
                out[f'{lab}.{who}.{rn}'] = s
                if x['status'] < 400 or any(s['leaks'].values()) or s['disposition_has_filename']:
                    bad.append(f'{lab}.{who}.{rn}')
        for who, t in actors:
            for rn, (m, p) in {'admin_delete_attachment': ('DELETE', f'{BD}/admin/board/{SLUG}/attachments/{a["id"]}'),
                               'user_delete_attachment': ('DELETE', f'{BD}/boards/{SLUG}/attachments/{a["id"]}')}.items():
                x = req(m, p, token=t)
                out[f'{lab}.{who}.{rn}'] = summary(x, LEAKS)
                if x['status'] < 400:
                    bad.append(f'{lab}.{who}.{rn}')
    for who, t in actors:
        for rn, p in {'support_show': f'{Q}/{QID}', 'support_list': Q, 'user_post_show': f'{BD}/boards/{SLUG}/posts/{QID}',
                      'user_post_list': f'{BD}/boards/{SLUG}/posts', 'admin_post_show': f'{BD}/admin/board/{SLUG}/posts/{QID}',
                      'admin_post_list': f'{BD}/admin/board/{SLUG}/posts'}.items():
            x = req('GET', p, token=t)
            s = summary(x, LEAKS)
            ids = []
            if x['status'] == 200 and isinstance(x['json'], dict):
                d = x['json'].get('data')
                lst = d.get('data') if isinstance(d, dict) else d
                ids = [i.get('id') for i in lst] if isinstance(lst, list) else []
                s['contains_question'] = QID in ids
                s['contains_attachment_hash'] = any(a['hash'].encode() in x['bytes'] for a in ATT.values())
            out[f'{who}.{rn}'] = s
            if (x['status'] == 200 and rn != 'support_list') or any(s['leaks'].values()) or s.get('contains_question') or s.get('contains_attachment_hash'):
                bad.append(f'{who}.{rn}')
    check(not bad, {'bad': bad, 'out': out})
    return 'PASS', out


if len(ATT) == 2:
    step('foreign: other_member and guest denied on every native attachment / listing route for the existing real file; no bytes, filename or body', foreign)


def nonimage():
    a = ATT['txt']
    out, bad = {}, []
    expect_msg = {}
    for lang in ('ko', 'en'):
        for who, t in (('admin', AD), ('owner', M), ('other_member', O), ('guest', None)):
            x = req('GET', f'{BD}/boards/{SLUG}/attachment/{a["hash"]}/preview', token=t, accept='application/json', lang=lang)
            s = summary(x, LEAKS)
            msg = s['message'] or ''
            s['message_is_raw_key'] = msg.startswith('sirsoft-board::') or msg.startswith('attachment.') or 'not_image' in msg
            s['message_is_preview_failed'] = 'preview' in msg.lower() and 'fail' in msg.lower()
            s['body_mentions_stored_path'] = b'attachments/' in x['bytes'] or b'storage' in x['bytes']
            out[f'{lang}.{who}'] = s
            expect_msg.setdefault(lang, set()).add(msg)
            if x['status'] != 400 or any(s['leaks'].values()) or s['disposition_has_filename'] or s['message_is_raw_key'] or s['body_mentions_stored_path'] or not msg:
                bad.append(f'{lang}.{who}')
    out['distinct_messages_per_locale'] = {k: sorted(v) for k, v in expect_msg.items()}
    locales_differ = expect_msg.get('ko') != expect_msg.get('en')
    out['ko_en_messages_differ'] = locales_differ
    check(not bad and locales_differ, {'bad': bad, 'out': out})
    return 'PASS', out


def notfound_controls():
    out = {}
    a = ATT['png']
    fake = 'Zz' + uuid.uuid4().hex[:10]
    for who, t in (('admin', AD), ('owner', M), ('other_member', O), ('guest', None)):
        for rn, p in {'wrong_board_preview': f'{BD}/boards/{OTHER_SLUG}/attachment/{a["hash"]}/preview',
                      'nonexistent_hash_preview': f'{BD}/boards/{SLUG}/attachment/{fake}/preview',
                      'nonexistent_board_preview': f'{BD}/boards/w04-no-such-board-{RUN[-4:]}/attachment/{a["hash"]}/preview'}.items():
            out[f'{who}.{rn}'] = summary(req('GET', p, token=t, accept='*/*'), LEAKS)
    # the same real PNG through the correct board returns bytes to admin (shows 404s are not a dead hash)
    ok = req('GET', f'{BD}/boards/{SLUG}/attachment/{a["hash"]}/preview', token=AD, accept='*/*')
    out['admin.correct_board_preview_bytes_equal_disk'] = file_ok(ok, a)
    bad = [k for k, v in out.items() if isinstance(v, dict) and (v['status'] != 404 or any(v['leaks'].values()))]
    check(not bad and out['admin.correct_board_preview_bytes_equal_disk'], {'bad': bad, 'out': out})
    return 'PASS', out


if len(ATT) == 2:
    step('nonimage preview: real text attachment returns translated 400 for admin/owner/other/guest in ko and en; no bytes/name/path', nonimage)
    step('distinct 404 controls: wrong board / nonexistent hash / nonexistent board vs the same real PNG returning bytes on the right board', notfound_controls)


def signed():
    u = STATE_D.get('signed_preview')
    if not u:
        return 'NOT_RUN', {'reason': 'admin post show issued no signed preview URL'}
    p = re.sub(r'^https?://[^/]+', '', u)
    a = ATT['png']
    out = {}
    g = req('GET', p, token=None, accept='*/*')
    out['guest_with_admin_issued_signed_url'] = summary(g) | {'bytes_equal_disk': file_ok(g, a)}
    tam = re.sub(r'signature=([0-9a-f])', lambda m: 'signature=' + ('0' if m.group(1) != '0' else '1'), p)
    t = req('GET', tam, token=None, accept='*/*')
    out['guest_tampered_signature'] = summary(t, LEAKS)
    nosig = req('GET', p.split('?')[0], token=None, accept='*/*')
    out['guest_unsigned'] = summary(nosig, LEAKS)
    exp_m = re.search(r'expires=(\d+)', p)
    out['signed_ttl_seconds'] = int(exp_m.group(1)) - int(time.time()) if exp_m else None
    check(t['status'] == 403 and nosig['status'] == 403 and not any(out['guest_tampered_signature']['leaks'].values()), out)
    return 'OBSERVED', out


if len(ATT) == 2:
    step('signed preview delegation (contract): admin-issued time-limited URL is a bearer capability; tampered/unsigned denied', signed)


def storage_urls():
    out = {}
    for lab, a in ATT.items():
        rel = a['disk'][0]['rel'] if a.get('disk') else ''
        tail = rel.replace('<stored_uuid_name>', a['stored'])
        after_app = tail.split('storage/app/', 1)[-1]
        cands = ['/storage/' + after_app, '/storage/' + after_app.split('/', 1)[-1], '/' + after_app, '/storage/app/' + after_app]
        m = re.search(r'attachments/(.+)$', after_app)
        if m:
            cands += ['/storage/' + m.group(1), '/storage/attachments/' + m.group(1)]
        for i, p in enumerate(dict.fromkeys(cands)):
            for who, t in (('guest', None), ('admin', AD)):
                x = req('GET', p, token=t, accept='*/*')
                s = summary(x, LEAKS)
                s['bytes_equal_disk'] = sha(x['bytes']) == disk_sha(a)
                out[f'{lab}.cand{i}.{who}'] = s
    bad = [k for k, v in out.items() if v['bytes_equal_disk'] or any(v['leaks'].values())]
    pubstorage = os.path.lexists(os.path.join(PARENT, 'public', 'storage'))
    check(not bad, {'bad': bad, 'out': out})
    return 'PASS', {'parent_public_storage_link_exists': pubstorage, 'candidates': out}


if len(ATT) == 2:
    step('native storage paths do not bypass the guard (direct path candidates return no file bytes)', storage_urls)


def del_txt():
    a = ATT['txt']
    d = req('DELETE', f'{BD}/admin/board/{SLUG}/attachments/{a["id"]}', token=AD)
    out = {'admin_delete': summary(d)}
    for who, t in (('admin', AD), ('owner', M), ('other_member', O), ('guest', None)):
        for rn, p in {'admin_download': f'{BD}/admin/board/{SLUG}/attachments/download/{a["hash"]}', 'user_download': f'{BD}/boards/{SLUG}/attachment/{a["hash"]}', 'preview': f'{BD}/boards/{SLUG}/attachment/{a["hash"]}/preview'}.items():
            out[f'{who}.{rn}'] = summary(req('GET', p, token=t, accept='*/*'), LEAKS)
    show = req('GET', f'{BD}/admin/board/{SLUG}/posts/{QID}', token=AD)
    atts = (((show['json'] or {}).get('data') or {}).get('attachments')) or []
    out['admin_show_attachment_ids'] = sorted(x.get('id') for x in atts)
    out['physical_file_after'] = find_parent_file(a['stored'])
    check(d['status'] == 200 and all(v['status'] >= 400 for k, v in out.items() if isinstance(v, dict) and 'status' in v and k != 'admin_delete')
          and a['id'] not in out['admin_show_attachment_ids'], out)
    return 'PASS', out


def del_question():
    a = ATT['png']
    d = req('DELETE', f'{BD}/admin/board/{SLUG}/posts/{QID}', token=AD)
    out = {'admin_delete_question': summary(d)}
    for who, t in (('admin', AD), ('owner', M), ('other_member', O), ('guest', None)):
        for rn, p in {'admin_download': f'{BD}/admin/board/{SLUG}/attachments/download/{a["hash"]}', 'user_download': f'{BD}/boards/{SLUG}/attachment/{a["hash"]}',
                      'preview': f'{BD}/boards/{SLUG}/attachment/{a["hash"]}/preview'}.items():
            out[f'{who}.{rn}'] = summary(req('GET', p, token=t, accept='*/*'), LEAKS)
    for who, t in (('owner', M), ('other_member', O)):
        out[f'{who}.support_show'] = req('GET', f'{Q}/{QID}', token=t)['status']
        lst = req('GET', Q, token=t)
        out[f'{who}.support_list_contains'] = QID in [i.get('id') for i in ((((lst['json'] or {}).get('data') or {}).get('data')) or [])]
    show = req('GET', f'{BD}/admin/board/{SLUG}/posts/{QID}', token=AD)
    sd = (show['json'] or {}).get('data') or {}
    out['admin_show_trashed'] = {'status': show['status'], 'deleted_at_set': bool(sd.get('deleted_at')),
                                 'attachment_ids': sorted(x.get('id') for x in (sd.get('attachments') or []))}
    out['physical_file_after'] = find_parent_file(a['stored'])
    out['txt_physical_file_after'] = find_parent_file(ATT['txt']['stored'])
    dl = [v for k, v in out.items() if isinstance(v, dict) and 'leaks' in v and k != 'admin_delete_question']
    check(d['status'] == 200 and all(v['status'] >= 400 and not any(v['leaks'].values()) for v in dl)
          and out['owner.support_show'] == 404 and not out['owner.support_list_contains'] and not out['other_member.support_list_contains'], out)
    return 'PASS', out


if len(ATT) == 2:
    step('cleanup 1: native admin attachment DELETE (txt) then every actor/route denied; physical file state recorded', del_txt)
    step('cleanup 2 / deleted-question boundary: native admin question DELETE cascades png; every actor/route denied; lists exclude', del_question)
elif QID:
    d = req('DELETE', f'{BD}/admin/board/{SLUG}/posts/{QID}', token=AD)
    record('cleanup (blocked path): native admin question DELETE', 'PASS' if d['status'] == 200 else 'FAIL', summary(d))


def s_logout():
    out = {}
    for r, t in TOK.items():
        lo = req('POST', '/api/auth/logout', token=t)
        after = req('GET', '/api/auth/user', token=t)
        out[r] = {'logout': lo['status'], 'after': after['status']}
    check(all(v['logout'] == 200 and v['after'] == 401 for v in out.values()), out)
    return 'PASS', out


step('revoke only own issued tokens via native logout (supplied handoff tokens untouched)', s_logout)


def s_supplied():
    out = {r: req('GET', '/api/auth/user', token=A[r]['bearer_token'])['status'] for r in ('member', 'other_member', 'admin')}
    check(all(v == 200 for v in out.values()), out)
    return 'PASS', out


step('supplied handoff tokens still valid (read only, never logged out)', s_supplied)
