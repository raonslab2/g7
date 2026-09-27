/** 저장된 선택이 없을 때만 제품 기본 다크 모드를 적용합니다. */
export function shouldApplyDarkDefault(savedScheme: string | null): boolean {
  return savedScheme === null;
}
