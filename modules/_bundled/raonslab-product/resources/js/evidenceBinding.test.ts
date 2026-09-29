import { createHash } from 'node:crypto';
import { readdirSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * 홈 공개 사례 이미지와 출처 기록의 결합을 고정한다.
 * 이미지 바이트·출처·검토 판정이 바뀌면 새 독립 검토(PASS_PUBLIC)를 거친 뒤 이 상수를 함께 바꾼다.
 */
const moduleRoot = resolve(__dirname, '../..');
const ASSET_DIR = resolve(moduleRoot, 'resources/assets/cases');
const FILE = 'mobile-stock-public-case-01-new-paper-account-390x844.png';
const EXPECTED = {
  sha256: '4fa8b7a705bede727aa21f4ba7a089466b2455b15051f2c660adcbeafa3e30de',
  gitBlob: '5f56e1334a18d5a2a6bc08e030f253d1927c6241',
  bytes: 197799,
  repository: 'raonslab2/mobile-stock',
  evidenceCommit: '7063bed01e287bbaeaa5b7e3f8c555b39b19ec70',
  sourcePath: `docs/evidence/screenshots/${FILE}`,
  review: 'req_82fc148ab10a4d4195d6f08eb7ad7aa1',
  caption: '모의투자 · 자체 제품 · 예시 데이터',
};

const bytes = readFileSync(resolve(ASSET_DIR, FILE));
const provenance = JSON.parse(readFileSync(resolve(ASSET_DIR, 'PROVENANCE.json'), 'utf8'));
const entry = provenance.assets.find((asset: { file: string }) => asset.file === FILE);
const layout = readFileSync(resolve(moduleRoot, 'resources/extensions/home-product.json'), 'utf8');
const ko = JSON.parse(readFileSync(resolve(moduleRoot, 'resources/lang/ko.json'), 'utf8'));
const en = JSON.parse(readFileSync(resolve(moduleRoot, 'resources/lang/en.json'), 'utf8'));

describe('MOBILE_STOCK 공개 사례 이미지 출처 결합', () => {
  it('이미지 바이트가 검토된 원본과 같다(sha256·git blob·크기)', () => {
    expect(createHash('sha256').update(bytes).digest('hex')).toBe(EXPECTED.sha256);
    const blob = createHash('sha1').update(Buffer.concat([Buffer.from(`blob ${bytes.length}\0`), bytes])).digest('hex');
    expect(blob).toBe(EXPECTED.gitBlob);
    expect(bytes.length).toBe(EXPECTED.bytes);
    // PNG 이며 메타데이터 청크 없이 IHDR/IDAT/IEND 만 가진다(검토 당시 안전 점검 조건)
    expect(bytes.subarray(0, 8).toString('hex')).toBe('89504e470d0a1a0a');
    const chunks = new Set<string>();
    for (let offset = 8; offset < bytes.length; ) {
      const length = bytes.readUInt32BE(offset);
      chunks.add(bytes.subarray(offset + 4, offset + 8).toString('ascii'));
      offset += 12 + length;
    }
    expect([...chunks].sort()).toEqual(['IDAT', 'IEND', 'IHDR']);
  });

  it('출처 기록이 원본 저장소·커밋·경로·검토 판정과 일치한다', () => {
    expect(entry).toBeDefined();
    expect(entry.sha256).toBe(EXPECTED.sha256);
    expect(entry.git_blob).toBe(EXPECTED.gitBlob);
    expect(entry.byte_size).toBe(EXPECTED.bytes);
    expect(entry.caption_ko).toBe(EXPECTED.caption);
    expect(entry.source).toMatchObject({ repository: EXPECTED.repository, evidence_commit: EXPECTED.evidenceCommit, path: EXPECTED.sourcePath, manifest_public_use: 'HOME_OR_DETAIL' });
    expect(entry.review).toMatchObject({ request: EXPECTED.review, verdict: 'PASS_PUBLIC' });
  });

  it('공개 자산 디렉토리에는 출처가 기록된 파일만 있다(손익 화면 05 등 미검토 이미지 없음)', () => {
    const files = readdirSync(ASSET_DIR).filter((name) => name !== 'PROVENANCE.json').sort();
    expect(files).toEqual(provenance.assets.map((asset: { file: string }) => asset.file).sort());
    expect(files).toEqual([FILE]);
  });

  it('홈은 이 이미지 하나만 참조하고 필수 캡션을 함께 보여 준다', () => {
    const refs = layout.match(/resources\/assets\/cases\/[\w.-]+\.(?:png|jpe?g|webp)/g) ?? [];
    expect(refs).toEqual([`resources/assets/cases/${FILE}`]);
    expect(ko.home.ms_caption).toBe(EXPECTED.caption);
    expect(layout).toContain('$t:raonslab-product.home.ms_caption');
  });

  it('MOBILE_STOCK 문구는 검토된 범위를 넘는 주장을 하지 않는다', () => {
    const copy = [ko, en].flatMap((dict) => Object.entries(dict.home).filter(([key]) => /^(proof_ms|case_ms|ms_)/.test(key) && !/^case_ms_open/.test(key)).map(([, value]) => String(value)));
    expect(copy.length).toBeGreaterThan(10);
    const text = copy.join('\n');
    // 아직 미검증 목록(case_ms_open*)은 부정 문맥이라 제외한다.
    // 금지: AI 분석 주장, 실거래·주문 연결 주장, 실제 시세, 수익·조언, 고객 납품, 배포·전체 회귀·타입 검사 통과 주장
    expect(text).not.toMatch(/AI 분석(으로|을 통해|이 가능|을 제공)|AI analy(sis|zes) (your|stocks|the market)|실시간 시세|real-time (prices|quotes)|수익률|투자 성과|returns?\b|추천 종목|고객 납품 완료|delivered to (a )?customer|배포 완료|deployed\b|전체 (브라우저|회귀) (통과|PASS)|typecheck/i);
    expect(text).not.toMatch(/실거래 (가능|지원|주문 연결)|live trading (enabled|supported)|places? real orders/i);
    // 필수: 모의투자·예시 데이터·주문 경로 없음·조회 전용·미검증 AI 분석
    expect(ko.home.proof_ms_kind).toMatch(/모의투자/);
    expect(ko.home.proof_ms_point2).toMatch(/실제 주문 경로 없음/);
    expect(ko.home.proof_ms_point3).toMatch(/조회 전용/);
    expect(ko.home.case_ms_open1).toBe('AI 분석 기능');
    expect(ko.home.case_ms_note).toMatch(/예시 데이터/);
  });

  it('화면 속 앱 이름 Symphony 는 홈 문구에서 정확히 한 번 설명된다', () => {
    for (const dict of [ko, en]) {
      const hits = Object.entries(dict.home).filter(([, value]) => /Symphony/.test(String(value)));
      expect(hits.map(([k]) => k)).toEqual(['proof_ms_kind']);
      expect(String(hits[0][1]).match(/Symphony/g)).toHaveLength(1);
    }
    expect(layout.match(/home\.proof_ms_kind\b/g)).toHaveLength(1);
  });

  it('원본 열기 링크는 같은 이미지 하나를 감싸고 새 자산 경로를 만들지 않는다', () => {
    const json = JSON.parse(layout);
    const find = (node: unknown, id: string): Record<string, unknown> | null => {
      if (Array.isArray(node)) { for (const child of node) { const hit = find(child, id); if (hit) return hit; } return null; }
      if (!node || typeof node !== 'object') return null;
      if ((node as { id?: string }).id === id) return node as Record<string, unknown>;
      for (const child of Object.values(node)) { const hit = find(child, id); if (hit) return hit; }
      return null;
    };
    const link = find(json, 'raon_home_proof_ms_shot_link') as { children?: Array<{ name?: string; props?: Record<string, unknown> }>; props?: Record<string, unknown> };
    expect(link.children).toHaveLength(1);
    expect(link.children?.[0].name).toBe('Img');
    expect(link.children?.[0].props?.['data-rh-asset']).toBe(`resources/assets/cases/${FILE}`);
    expect(JSON.stringify(link.props)).not.toMatch(/resources\/assets|https?:|\.png/);
  });
});
