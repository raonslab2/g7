// @vitest-environment jsdom

import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';

describe('모바일 interactive target', () => {
    it.each([360, 390, 412])('%ipx에서 back, refresh, submit target이 최소 44px다', (width) => {
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: width });
        const style = document.createElement('style');
        style.textContent = readFileSync(`${process.cwd()}/resources/css/main.css`, 'utf8');
        document.head.replaceChildren(style);
        document.body.innerHTML = '<button class="rai-back">back</button><button class="rai-quiet">refresh</button><button class="rai-primary">submit</button>';

        for (const selector of ['.rai-back', '.rai-quiet', '.rai-primary']) {
            const computed = getComputedStyle(document.querySelector(selector)!);
            expect(Number.parseFloat(computed.minHeight)).toBeGreaterThanOrEqual(44);
        }
        expect(Number.parseFloat(getComputedStyle(document.querySelector('.rai-back')!).minWidth)).toBeGreaterThanOrEqual(44);
        expect(Number.parseFloat(getComputedStyle(document.querySelector('.rai-quiet')!).minWidth)).toBeGreaterThanOrEqual(44);
    });

    it('키보드 focus와 reduced-motion 대안을 명시한다', () => {
        const css = readFileSync(`${process.cwd()}/resources/css/main.css`, 'utf8');
        expect(css).toContain(':focus-visible');
        expect(css).toContain('prefers-reduced-motion:reduce');
    });
});
