import { describe, expect, it } from 'vitest';
import {
  buildPayload,
  canSubmit,
  classifyConfigLoad,
  classifySubmitResponse,
  DEFAULT_SERVICE_OPTIONS,
  EMPTY_DRAFT,
  parseIntakeConfig,
  parseRetryAfter,
  payloadFingerprint,
  planRecovery,
  resolveIdempotencyKey,
  safePolicyUrl,
  validateDraft,
} from './consultation';

const ORIGIN = 'https://hub.example.test';
const OPEN = { data: { enabled: true, consent_version: '2026-09-v1', privacy_copy: '수집 항목: 이름, 이메일' } };

describe('상담 접수 설정(fail-closed)', () => {
  it('접수가 열려 있고 동의 문안·버전이 모두 있을 때만 설정을 돌려준다', () => {
    const config = parseIntakeConfig(OPEN);
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
    expect(parseIntakeConfig(body)).toBeNull();
  });

  it('privacy 하위 객체와 서비스 옵션 선언을 읽는다', () => {
    const config = parseIntakeConfig({
      data: {
        enabled: true,
        privacy: { privacy_consent_version: 'v2', copy: '문안', policy_url: '/page/privacy', retention_notice: '1년 보관' },
        service_interests: [{ value: 'pilot', label: '실증' }, 'build', { id: '' }],
      },
    });
    expect(config?.consentVersion).toBe('v2');
    // 상대 경로는 서버와 같은 규칙으로 링크를 만들지 않는다(https 절대 주소만).
    expect(config?.privacyPolicyUrl).toBeNull();
    expect(config?.retentionNotice).toBe('1년 보관');
    expect(config?.serviceOptions).toEqual([{ value: 'pilot', label: '실증' }, { value: 'build', label: null }]);
  });

  it('정책 링크는 https 절대 주소만 허용한다', () => {
    expect(safePolicyUrl('https://elsewhere.example/privacy')).toBe('https://elsewhere.example/privacy');
    expect(safePolicyUrl('HTTPS://elsewhere.example/privacy')).toBe('https://elsewhere.example/privacy');
    for (const rejected of [
      '',
      'javascript:alert(1)',
      'JavaScript://x%0aalert(1)',
      'data:text/html,<b>x</b>',
      'http://elsewhere.example/privacy',
      '/page/privacy',
      '//elsewhere.example/privacy',
      `${ORIGIN.replace('https:', 'http:')}/privacy`,
      'https://user@elsewhere.example/privacy',
      ' https://elsewhere.example/privacy',
      'https:\\\\elsewhere.example/privacy',
    ]) {
      expect(safePolicyUrl(rejected), rejected).toBeNull();
    }
  });

  it('config 조회 결과를 열림/닫힘 확인/확인 실패로 구분한다', () => {
    expect(classifyConfigLoad(true, OPEN)).toMatchObject({ state: 'open' });
    expect(classifyConfigLoad(true, { data: { intake_enabled: false } })).toEqual({ state: 'closed' });
    expect(classifyConfigLoad(false, OPEN)).toEqual({ state: 'error' });
    expect(classifyConfigLoad(true, null)).toEqual({ state: 'error' });
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

  it('503 은 서버가 접수 닫힘 사유를 명시할 때만 닫힘이다', () => {
    const disabled = { success: false, errors: { reason: 'intake_disabled', retryable: false } };
    expect(classifySubmitResponse(503, disabled, null)).toEqual({ kind: 'disabled' });
    // 사유 없는 503(유지보수 등)·일시 장애 500 은 재시도 가능한 서버 오류다.
    expect(classifySubmitResponse(503, { success: false }, null)).toEqual({ kind: 'server' });
    const temporary = { success: false, errors: { reason: 'temporary_failure', retryable: true, incident_id: '01TEST' } };
    expect(classifySubmitResponse(500, temporary, null)).toEqual({ kind: 'server' });
    expect(classifySubmitResponse(500, disabled, null)).toEqual({ kind: 'server' });
  });

  it.each([
    [409, { kind: 'duplicate' }],
    [503, { kind: 'server' }],
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

describe('불확실한 응답 뒤 입력·키 보존', () => {
  it.each(['network', 'server', 'throttle', 'validation'] as const)('%s 뒤에는 입력과 같은 키를 지킨다', (kind) => {
    expect(planRecovery(kind)).toEqual({ keepDraft: true, keepKey: true, closeForm: false });
  });

  it('닫힘 응답이라도 config 재확인이 실패하거나 열려 있으면 입력과 키를 지킨다', () => {
    expect(planRecovery('disabled', 'error')).toEqual({ keepDraft: true, keepKey: true, closeForm: false });
    expect(planRecovery('disabled', 'open')).toEqual({ keepDraft: true, keepKey: true, closeForm: false });
    expect(planRecovery('disabled', null)).toEqual({ keepDraft: true, keepKey: true, closeForm: false });
  });

  it('닫힘이 확인된 경우에만 입력 화면을 거두고 비운다', () => {
    expect(planRecovery('disabled', 'closed')).toEqual({ keepDraft: false, keepKey: false, closeForm: true });
  });

  it('성공은 비우고, 키 충돌은 입력을 지키되 새 키를 쓴다', () => {
    expect(planRecovery('success')).toEqual({ keepDraft: false, keepKey: false, closeForm: false });
    expect(planRecovery('duplicate')).toEqual({ keepDraft: true, keepKey: false, closeForm: false });
  });

  it('재시도는 같은 키를 다시 쓴다(서버가 이미 저장했다면 200 으로 수렴)', () => {
    const fingerprint = payloadFingerprint({ contact_name: '홍', email: 'a@b.co' });
    const first = resolveIdempotencyKey(null, fingerprint, () => 'first-key-0000000001');
    const plan = planRecovery('server');
    const kept = plan.keepKey ? first : null;
    expect(resolveIdempotencyKey(kept, fingerprint, () => 'second-key-000000001').key).toBe('first-key-0000000001');
  });
});

describe('연속 클릭 방지', () => {
  const ready = { submitting: false, view: 'form', hasConfig: true, throttleUntil: 0 };

  it('전송 중에는 다음 전송을 시작하지 않는다', () => {
    expect(canSubmit(ready, 1_000)).toBe(true);
    expect(canSubmit({ ...ready, submitting: true }, 1_000)).toBe(false);
  });

  it('접수 불가 화면·설정 없음·대기 시간 중에도 막는다', () => {
    expect(canSubmit({ ...ready, view: 'unavailable' }, 1_000)).toBe(false);
    expect(canSubmit({ ...ready, hasConfig: false }, 1_000)).toBe(false);
    expect(canSubmit({ ...ready, throttleUntil: 2_000 }, 1_000)).toBe(false);
    expect(canSubmit({ ...ready, throttleUntil: 2_000 }, 2_000)).toBe(true);
  });
});
