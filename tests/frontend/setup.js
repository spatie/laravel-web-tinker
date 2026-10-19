import { vi } from 'vitest';

vi.stubGlobal('localStorage', jsdom.window.localStorage);

Range.prototype.getBoundingClientRect = () => ({ left: 0, right: 0, top: 0, bottom: 0 });
Range.prototype.getClientRects = () => [];
