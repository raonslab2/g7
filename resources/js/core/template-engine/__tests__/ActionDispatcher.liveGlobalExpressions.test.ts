/**
 * W04 native browser diagnosis: state.get() returns global CONTENT, not {_global: ...}.
 * Use actual TemplateApp storage + initialized native G7Core state API; retain stale
 * rendered context intentionally and inspect the real dispatcher's outgoing payload.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { TemplateApp } from '../../TemplateApp';
import { ActionDispatcher, type ActionDefinition } from '../ActionDispatcher';
import { initializeG7CoreGlobals } from '../G7CoreGlobals';
import { DataBindingEngine } from '../DataBindingEngine';
import { TranslationEngine } from '../TranslationEngine';
import { Logger } from '../../utils/Logger';

vi.mock('../../api/ApiClient', () => ({ getApiClient: () => ({ getToken: () => null }) }));

describe('ActionDispatcher live global expression payloads', () => {
  let app: TemplateApp;
  let dispatcher: ActionDispatcher;
  let fetchMock: ReturnType<typeof vi.fn>;
  let originalFetch: typeof fetch;
  let originalCore: unknown;
  let originalApp: unknown;
  let originalActionContext: unknown;

  beforeEach(() => {
    originalCore = (window as any).G7Core;
    originalApp = (window as any).__templateApp;
    originalActionContext = (window as any).__g7ActionContext;
    delete (window as any).G7Core;
    app = new TemplateApp({ templateId: 'native-probe', templateType: 'admin', locale: 'ko', debug: false });
    (window as any).__templateApp = app;
    dispatcher = new ActionDispatcher({ navigate: vi.fn() });
    initializeG7CoreGlobals({
      getState: () => ({
        translationEngine: new TranslationEngine(),
        translationContext: { templateId: 'native-probe', locale: 'ko' },
        bindingEngine: new DataBindingEngine(),
        actionDispatcher: dispatcher,
        templateMetadata: { locales: ['ko'] },
      }),
      transitionManager: { getIsPending: () => false, subscribe: () => () => {} },
      responsiveManager: {},
      webSocketManager: {} as any,
    });
    // Keep actual storage writes; intentionally do not refresh rendered context.
    dispatcher.setGlobalStateUpdater(updates => app.setGlobalState(updates, { render: false }));
    originalFetch = globalThis.fetch;
    fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, data: {} }) });
    globalThis.fetch = fetchMock as unknown as typeof fetch;
    Logger.getInstance().setDebug(false);
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
    (window as any).G7Core = originalCore;
    (window as any).__templateApp = originalApp;
    (window as any).__g7ActionContext = originalActionContext;
    Logger.getInstance().setDebug(false);
  });

  const event = () => new Event('click');
  const action = (body: Record<string, unknown>): ActionDefinition => ({
    handler: 'apiCall', type: 'click', target: '/api/native-expression-probe', auth_mode: 'none',
    params: { method: 'POST', body },
  });
  const sentBody = () => {
    const call = fetchMock.mock.calls.find(([url]) => url === '/api/native-expression-probe');
    expect(call).toBeDefined();
    return JSON.parse(String((call![1] as RequestInit).body));
  };
  const capture = () => ({ _global: app.getGlobalState(), _local: {}, row: { id: 9 } });

  it('uses latest false boolean after handler creation without waiting for render', async () => {
    app.setGlobalState({ departure: { is_active: true, capacity: 4 } }, { render: false });
    const handler = dispatcher.createHandler(action({ is_active: '{{_global.departure.is_active}}' }), capture());
    app.setGlobalState({ departure: { is_active: false, capacity: 4 } }, { render: false });
    expect((window as any).G7Core.state.get().departure.is_active).toBe(false);
    expect((window as any).G7Core.state.get()._global).toBeUndefined();
    await handler(event());
    expect(sentBody()).toEqual({ is_active: false });
  });

  it('refreshes complex interpolation from current global content while preserving row context', async () => {
    app.setGlobalState({ departure: { id: 1, capacity: 4 } }, { render: false });
    const handler = dispatcher.createHandler(action({ label: 'trip-{{_global.departure.id}}-row-{{row.id}}-capacity-{{_global.departure.capacity}}' }), capture());
    app.setGlobalState({ departure: { id: 2, capacity: 7 } }, { render: false });
    await handler(event());
    expect(sentBody()).toEqual({ label: 'trip-2-row-9-capacity-7' });
  });

  it('preserves number and object types from a single live expression', async () => {
    app.setGlobalState({ departure: { capacity: 4 } }, { render: false });
    const handler = dispatcher.createHandler(action({ capacity: '{{_global.departure.capacity}}', selected: '{{_global.departure}}' }), capture());
    app.setGlobalState({ departure: { capacity: 7, is_active: false } }, { render: false });
    await handler(event());
    expect(sentBody()).toEqual({ capacity: 7, selected: { capacity: 7, is_active: false } });
  });

  it('does not resurrect a deleted global key from captured context', async () => {
    app.setGlobalState({ departure: { is_active: true } }, { render: false });
    const handler = dispatcher.createHandler(action({ value: '{{_global.departure?.is_active ?? null}}' }), capture());
    app.setGlobalState(() => ({ sidebarOpen: false }), { render: false });
    await handler(event());
    expect(sentBody()).toEqual({ value: null });
  });

  it('reads live state updated within the same native sequence', async () => {
    app.setGlobalState({ departure: { is_active: true, capacity: 4 } }, { render: false });
    const handler = dispatcher.createHandler({ handler: 'sequence', type: 'click', actions: [
      { handler: 'setState', params: { target: 'global', 'departure.is_active': false } },
      action({ is_active: '{{_global.departure.is_active}}', label: 'capacity-{{_global.departure.capacity}}' }),
    ] }, capture());
    await handler(event());
    expect(sentBody()).toEqual({ is_active: false, label: 'capacity-4' });
  });

  it.each([
    ['single', '{{_global.departure.is_active}}', true],
    ['complex', 'active-{{_global.departure.is_active}}', 'active-true'],
  ])('retains %s context fallback when native state API is unavailable', async (_name, expression, expected) => {
    const handler = dispatcher.createHandler(action({ value: expression }), { _global: { departure: { is_active: true } } });
    delete (window as any).G7Core;
    await handler(event());
    expect(sentBody()).toEqual({ value: expected });
  });

  it('does not override explicit local/row expressions while refreshing global expressions', async () => {
    app.setGlobalState({ enabled: false }, { render: false });
    await dispatcher.createHandler(action({ value: '{{_local.count}}', id: '{{row.id}}', enabled: '{{_global.enabled}}' }), {
      _global: { enabled: true }, _local: { count: 7 }, row: { id: 9 },
    }, { state: { count: 7 }, setState: vi.fn() })(event());
    expect(sentBody()).toEqual({ value: 7, id: 9, enabled: false });
  });
});
