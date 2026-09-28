import { describe, expect, it } from 'vitest';
import {
  buildPayload,
  classifySubmitResponse,
  DEFAULT_SERVICE_OPTIONS,
  EMPTY_DRAFT,
  parseIntakeConfig,
  parseRetryAfter,
  payloadFingerprint,
  resolveIdempotencyKey,
  safePolicyUrl,
  validateDraft,
} from './consultation';

const ORIGIN = 'https://hub.example.test';
const OPEN = { data: { enabled: true, consent_version: '2026-09-v1', privacy_copy: '수집 항목: 이름, 이메일' } };

describe('상담 접수 설정(fail-closed)', () => {
  it('접수가 열려 있고 동의 문안·버전이 모두 있을 때만 설정을 돌려준다', () => {
    const config = parseIntakeConfig(OPEN, ORIGIN);
    expect(config?.consentVersion).toBe('2026-09-v1');
    expect(config?.privacyCopy).toBe('수집 항목: 이름, 이메일');
    expect(config?.serviceOptions).toEqual(DEFAULT_SERVICE_OPTIONS);
  });

  it.each([
    ['본문 없음', null],
    ['배열 본문', []],
    ['비활성', { data: { ...OPEN.data, enabled: false } }],
    ['문자열 true 는 열림으로 보지 않음', { data: { ...OPEN.data, enabled: 'true' } }],
    ['enabled 누락', { data: { consent_version: 'v1', privacy_copy: 'x' } }],
    ['동의 버전 누락', { data: { enabled: true, privacy_copy: 'x' } }],
    ['동의 문안 누락', { data: { enabled: true, consent_version: 'v1' } }],
    ['공백 문안', { data: { enabled: true, consent_version: 'v1', privacy_copy: '   ' } }],
  ])('%s → 접수 불가', (_label, body) => {
    expect(parseIntakeConfig(body, ORIGIN)).toBeNull();
  });

  it('privacy 하위 객체와 서비스 옵션 선언을 읽는다', () => {
    const config = parseIntakeConfig({
      data: {
        enabled: true,
        privacy: { privacy_consent_version: 'v2', copy: '문안', policy_url: '/page/privacy', retention_notice: '1년 보관' },
        service_interests: [{ value: 'pilot', label: '실증' }, 'build', { id: '' }],
      },
    }, ORIGIN);
    expect(config?.consentVersion).toBe('v2');
    expect(config?.privacyPolicyUrl).toBe(`${ORIGIN}/page/privacy`);
    expect(config?.retentionNotice).toBe('1년 보관');
    expect(config?.serviceOptions).toEqual([{ value: 'pilot', label: '실증' }, { value: 'build', label: null }]);
  });

  it('정책 링크는 같은 origin 또는 https 만 허용한다', () => {
    expect(safePolicyUrl('javascript:alert(1)', ORIGIN)).toBeNull();
    expect(safePolicyUrl('http://elsewhere.example/privacy', ORIGIN)).toBeNull();
    expect(safePolicyUrl('https://elsewhere.example/privacy', ORIGIN)).toBe('https://elsewhere.example/privacy');
    expect(safePolicyUrl('', ORIGIN)).toBeNull();
  });
});

describe('입력 검증과 전송 본문', () => {
  it('필수값·이메일 형식·동의를 확인한다', () => {
    expect(validateDraft(EMPTY_DRAFT)).toEqual({ contact_name: 'required', email: 'required', message: 'required', privacy_consent: 'consent' });
    expect(validateDraft({ ...EMPTY_DRAFT, contact_name: '홍', email: 'not-an-email', message: 'x', privacy_consent: true })).toEqual({ email: 'email' });
    expect(validateDraft({ ...EMPTY_DRAFT, contact_name: '홍', email: 'a@b.co', message: 'x', privacy_consent: true })).toEqual({});
  });

  it('선택 항목은 비어 있으면 보내지 않고 동의 버전을 함께 보낸다', () => {
    const payload = buildPayload({ ...EMPTY_DRAFT, contact_name: ' 홍길동 ', email: 'a@b.co', message: ' 문의 ', privacy_consent: true, phone: '  ' }, 'v1');
    expect(payload).toEqual({ contact_name: '홍길동', email: 'a@b.co', message: '문의', privacy_consent: true, privacy_consent_version: 'v1' });
  });
});

describe('Idempotency-Key 재사용 규칙', () => {
  const keys = ['k1', 'k2', 'k3'];
  const gen = () => keys.shift() as string;

  it('같은 내용 재시도는 같은 키, 내용이 바뀌면 새 키를 쓴다', () => {
    const a = payloadFingerprint({ email: 'a@b.co', contact_name: '홍' });
    const aReordered = payloadFingerprint({ contact_name: '홍', email: 'a@b.co' });
    expect(a).toBe(aReordered);
    const first = resolveIdempotencyKey(null, a, gen);
    expect(resolveIdempotencyKey(first, aReordered, gen)).toBe(first);
    const changed = resolveIdempotencyKey(first, payloadFingerprint({ contact_name: '홍', email: 'c@d.co' }), gen);
    expect(changed.key).toBe('k2');
  });
});

describe('POST 응답 분류', () => {
  const receipt = { data: { reference: 'RC-1', status: 'received', received_at: '2026-09-28T01:02:03Z' } };

  it('201 은 신규 접수, 200 은 같은 요청 재확인', () => {
    expect(classifySubmitResponse(201, receipt, null)).toEqual({ kind: 'success', replay: false, receipt: { reference: 'RC-1', status: 'received', receivedAt: '2026-09-28T01:02:03Z' } });
    expect(classifySubmitResponse(200, receipt, null)).toMatchObject({ kind: 'success', replay: true });
  });

  it('접수 번호 없는 2xx 는 성공으로 보지 않는다', () => {
    expect(classifySubmitResponse(201, { data: {} }, null)).toEqual({ kind: 'server' });
  });

  it('422 필드 오류를 입력 항목에 매핑한다', () => {
    const outcome = classifySubmitResponse(422, { message: '입력 오류', errors: { email: ['이메일 형식'], privacy_consent_version: ['버전 불일치'], unknown: ['x'] } }, null);
    expect(outcome).toEqual({ kind: 'validation', message: '입력 오류', errors: { email: '이메일 형식', privacy_consent: '버전 불일치' } });
  });

  it.each([
    [409, { kind: 'duplicate' }],
    [503, { kind: 'disabled' }],
    [500, { kind: 'server' }],
    [403, { kind: 'server' }],
    [404, { kind: 'server' }],
  ])('%i 상태 분류', (status, expected) => {
    expect(classifySubmitResponse(status, null, null)).toEqual(expected);
  });

  it('429 는 Retry-After 를 초 단위로 읽는다(상한 600)', () => {
    expect(classifySubmitResponse(429, null, '12')).toEqual({ kind: 'throttle', retryAfterSeconds: 12 });
    expect(parseRetryAfter('99999')).toBe(600);
    expect(parseRetryAfter(null)).toBeNull();
    expect(parseRetryAfter('soon')).toBeNull();
  });
});
