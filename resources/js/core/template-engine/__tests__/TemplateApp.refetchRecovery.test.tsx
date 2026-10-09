/**
 * Actual TemplateApp -> DataSourceManager -> engine state/render recovery.
 * Only ApiClient/fetch transport is mocked; native AuthManager.checkAuth,
 * action dispatcher, binding engine and state updates remain real.
 * Basic HTML wrappers stand in for the separately shipped admin component bundle.
 */
import React from 'react';
import fs from 'fs';
import path from 'path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, screen, waitFor } from '@testing-library/react';
import { TemplateApp } from '../../TemplateApp';
import { AuthManager } from '../../auth/AuthManager';
import { initTemplateEngine, renderTemplate, destroyTemplate, getState, updateTemplateData } from '../../template-engine';
import { initializeG7CoreGlobals } from '../G7CoreGlobals';
import { transitionManager } from '../TransitionManager';
import { ComponentRegistry } from '../ComponentRegistry';
import { TranslationEngine } from '../TranslationEngine';
import { createMockComponentRegistryWithBasics } from './utils/layoutTestUtils';
import { Logger } from '../../utils/Logger';

const { api } = vi.hoisted(() => ({ api: { get: vi.fn(), getToken: vi.fn(() => 'unit-transport-token') } }));
vi.mock('../../api/ApiClient', () => ({ getApiClient: () => api }));

const layout = JSON.parse(fs.readFileSync(path.resolve(process.cwd(), 'modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_campaigns.json'), 'utf8'));
const source = layout.data_sources[0];
const nativePages = { success: true, data: { data: [
  { id: 7, slug: 'travel-lab-campaign-autumn-escape', title: 'Autumn unit Page', published: true, current_version: 3, abilities: { can_update: true } },
  { id: 8, slug: 'travel-lab-campaign-weekend-reset', title: 'Weekend unit Page', published: false, current_version: 2, abilities: { can_update: false } },
], meta: { current_page: 1, last_page: 1, per_page: 100, total: 2 }, abilities: { can_create: false } } };
const failure = (message: string, status?: number) => Object.assign(new Error(message), status === undefined ? {} : { response: { status, data: { message } } });
const oldError = { message: 'initial transport aborted', status: undefined };

describe('TemplateApp native source-scoped refetch recovery', () => {
  let app: TemplateApp;
  let fetchBefore: typeof fetch;
  let coreBefore: any;
  let appBefore: any;
  let registryBefore: any;

  beforeEach(async () => {
    Logger.getInstance().setDebug(false);
    fetchBefore = globalThis.fetch;
    coreBefore = (window as any).G7Core;
    appBefore = (window as any).__templateApp;
    delete (window as any).G7Core;
    globalThis.fetch = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({}) }) as any;
    AuthManager.resetInstance();
    api.get.mockReset();
    api.getToken.mockReturnValue('unit-transport-token');
    api.get.mockResolvedValueOnce({ success: true, data: { id: 1, name: 'Unit administrator' } });
    expect(await AuthManager.getInstance().checkAuth('admin')).toBe(true);
    api.get.mockClear();
    app = new TemplateApp({ templateId: 'native-refetch-probe', templateType: 'admin', locale: 'ko', debug: false });
    (app as any).currentDataSources = [source];
    (app as any).currentQueryParams = new URLSearchParams('page=1');
    (window as any).__templateApp = app;
    await initTemplateEngine({ templateId: 'native-refetch-probe', locale: 'ko', debug: false });
    const registry = ComponentRegistry.getInstance() as any;
    registryBefore = registry.registry;
    const basics = createMockComponentRegistryWithBasics();
    registry.registry = Object.fromEntries(basics.getAllComponents().map(name => [name, { component: basics.getComponent(name), metadata: basics.getMetadata(name) }]));
    registry.registry.Icon = { component: ({ name }: any) => React.createElement('i', { 'data-icon': name }), metadata: { name: 'Icon', type: 'basic' } };
    initializeG7CoreGlobals({ getState, transitionManager, responsiveManager: {}, webSocketManager: {} as any });
    document.body.innerHTML = '<div id="native-recovery-root"></div>';
  });

  afterEach(async () => {
    await act(async () => destroyTemplate());
    (ComponentRegistry.getInstance() as any).registry = registryBefore;
    TranslationEngine.resetInstance();
    AuthManager.resetInstance();
    globalThis.fetch = fetchBefore;
    (window as any).G7Core = coreBefore;
    (window as any).__templateApp = appBefore;
    transitionManager.setPending(false);
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  async function mount(data: any = null, errors: any = { campaign_pages: oldError }, campaignUi = false) {
    (app as any).currentFetchedData = { campaign_pages: data };
    await act(async () => renderTemplate({ containerId: 'native-recovery-root', layoutJson: { components: campaignUi ? layout.slots.content : [] }, dataContext: { campaign_pages: data, _dataSourceErrors: errors, _global: {}, _local: {} } }));
  }
  async function refetch() {
    let result: any;
    await act(async () => { result = await app.refetchDataSource('campaign_pages', { sync: true }); });
    return result;
  }

  it('actual Retry dispatcher -> native200 removes sticky error and restores both Page titles', async () => {
    await mount(null, { campaign_pages: oldError }, true);
    expect(screen.getByTestId('campaign-pages-error')).toBeTruthy();
    api.get.mockResolvedValueOnce(nativePages);
    await act(async () => { fireEvent.click(screen.getByTestId('campaign-pages-retry')); });
    await waitFor(() => expect(screen.queryByTestId('campaign-pages-error')).toBeNull());
    expect(screen.getAllByTestId('campaign-slot-title')).toHaveLength(2);
    expect(screen.getAllByTestId('campaign-slot-edit')).toHaveLength(1);
    expect(screen.getAllByTestId('campaign-slot-detail')).toHaveLength(2);
    expect(api.get).toHaveBeenCalledWith('/modules/sirsoft-page/admin/pages', { params: { 'filters[0][field]': 'slug', 'filters[0][operator]': 'starts_with', 'filters[0][value]': 'travel-lab-campaign-', per_page: 100, page: '1' } });
  });

  it('clears only the recovered source and preserves an unrelated source error', async () => {
    const other = { message: 'independent source unavailable', status: 503 };
    await mount(null, { campaign_pages: oldError, other_source: other });
    api.get.mockResolvedValueOnce(nativePages);
    expect(await refetch()).toBe(nativePages);
    expect(getState().currentDataContext._dataSourceErrors).toEqual({ other_source: other });
    expect(getState().currentDataContext.campaign_pages).toBe(nativePages);
    expect(app.getDataSource('campaign_pages')).toBe(nativePages);
  });

  it('failed requery retains previous data and records current own error without erasing others', async () => {
    const other = { message: 'other error', status: 422 };
    await mount(nativePages, { other_source: other });
    api.get.mockRejectedValueOnce(failure('new server failure', 503));
    expect(await refetch()).toBeUndefined();
    expect(getState().currentDataContext.campaign_pages).toBe(nativePages);
    expect(app.getDataSource('campaign_pages')).toBe(nativePages);
    expect(getState().currentDataContext._dataSourceErrors).toEqual({ other_source: other, campaign_pages: { message: 'new server failure', status: 503 } });
    expect(transitionManager.getIsPending()).toBe(false);
  });

  it('repeated failed retries replace the own message/status, then real success clears it', async () => {
    await mount();
    api.get.mockRejectedValueOnce(failure('rate limited', 429));
    expect(await refetch()).toBeUndefined();
    expect(getState().currentDataContext._dataSourceErrors.campaign_pages).toEqual({ message: 'rate limited', status: 429 });
    api.get.mockRejectedValueOnce(failure('network retry failed'));
    expect(await refetch()).toBeUndefined();
    expect(getState().currentDataContext._dataSourceErrors.campaign_pages).toEqual({ message: 'network retry failed', status: undefined });
    api.get.mockResolvedValueOnce(nativePages);
    expect(await refetch()).toBe(nativePages);
    expect(getState().currentDataContext._dataSourceErrors).toBeUndefined();
  });

  it('keeps actual error visible while request is pending, then clears pending after response', async () => {
    await mount();
    let resolve!: (v: any) => void;
    api.get.mockImplementationOnce(() => new Promise(r => { resolve = r; }));
    const request = app.refetchDataSource('campaign_pages', { sync: true });
    expect(transitionManager.getIsPending()).toBe(true);
    expect(getState().currentDataContext._dataSourceErrors.campaign_pages).toEqual(oldError);
    await act(async () => { resolve(nativePages); await request; });
    expect(transitionManager.getIsPending()).toBe(false);
    expect(getState().currentDataContext._dataSourceErrors).toBeUndefined();
  });

  it('preserves another error added while the source response is in flight', async () => {
    await mount();
    let resolve!: (v: any) => void;
    api.get.mockImplementationOnce(() => new Promise(r => { resolve = r; }));
    const request = app.refetchDataSource('campaign_pages', { sync: true });
    const late = { message: 'concurrent error', status: 500 };
    await act(async () => updateTemplateData({ _dataSourceErrors: { campaign_pages: oldError, late_source: late } }, { sync: true }));
    await act(async () => { resolve(nativePages); await request; });
    expect(getState().currentDataContext._dataSourceErrors).toEqual({ late_source: late });
  });

  it('retains native initGlobal/initLocal and invalidates error bindings with source bindings', async () => {
    (app as any).currentDataSources = [{ ...source, initGlobal: { nativeCatalog: 'data' }, initLocal: 'pageForm' }];
    await mount();
    const invalidation = vi.spyOn(getState().bindingEngine!, 'invalidateCacheByKeys');
    api.get.mockResolvedValueOnce(nativePages);
    expect(await refetch()).toBe(nativePages);
    expect(app.getGlobalState().nativeCatalog).toEqual(nativePages.data.data);
    expect(getState().currentDataContext._localInit.pageForm).toEqual(nativePages.data);
    expect(invalidation).toHaveBeenCalledWith(expect.arrayContaining(['campaign_pages', '_dataSourceErrors', '_global', '_local']));
  });

  it('native no-token auth guard remains401 and skips transport rather than creating success', async () => {
    await mount();
    AuthManager.resetInstance();
    api.get.mockClear();
    expect(await refetch()).toBeUndefined();
    expect(api.get).not.toHaveBeenCalled();
    expect(getState().currentDataContext.campaign_pages).toBeNull();
    expect(getState().currentDataContext._dataSourceErrors.campaign_pages.status).toBe(401);
    expect(transitionManager.getIsPending()).toBe(false);
  });

  it.each([
    ['HTTP200 empty collection', { success: true, data: { data: [] } }],
    ['native null response data', null],
    ['Axios204 empty response body', ''],
  ])('clears recovered errors for %s without changing the native return', async (_label, payload) => {
    await mount();
    api.get.mockResolvedValueOnce(payload);
    expect(await refetch()).toBe(payload);
    expect(getState().currentDataContext.campaign_pages).toBe(payload);
    expect(getState().currentDataContext._dataSourceErrors).toBeUndefined();
    expect(transitionManager.getIsPending()).toBe(false);
  });

  it('preserves explicitly declared native fallback success semantics; campaign source has no fallback', async () => {
    expect(source.fallback).toBeUndefined();
    const fallback = { rows: [], offline: true };
    (app as any).currentDataSources = [{ ...source, fallback }];
    await mount();
    api.get.mockRejectedValueOnce(failure('declared fallback transport failure', 503));
    expect(await refetch()).toBe(fallback);
    expect(getState().currentDataContext.campaign_pages).toBe(fallback);
    expect(getState().currentDataContext._dataSourceErrors).toBeUndefined();
    expect(transitionManager.getIsPending()).toBe(false);
  });
});
