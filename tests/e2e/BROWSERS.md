# Playwright Browser Notes

## Current Configuration
- **Playwright version**: 1.62.1
- **Package requirement**: `^1.50.0`
- **Browser**: Chromium (bundled, not system Chrome)
- **Channel**: none (uses Playwright's bundled Chromium)
- **Headless**: false (headed mode)

## Currently Used Browser
| Browser | Version | Build | Path |
|---------|---------|-------|------|
| Chromium | 151.0.7922.34 | v1234 | `chromium-1234` |

## Installed but Unused
| Browser | Version | Installed | Path |
|---------|---------|-----------|------|
| Chromium | v1232 | Aug 1 21:37 | `chromium-1232` |
| Chromium | v1228 | Jun 16 | `chromium-1228` |
| Chromium Headless Shell | v1232 | Aug 1 21:37 | `chromium_headless_shell-1232` |
| Chromium Headless Shell | v1228 | Jun 16 | `chromium_headless_shell-1228` |
| Firefox | 153.0 (v1532) | Jun 16 | `firefox-1532` |
| WebKit | 26.5 (v2311) | Jun 16 | `webkit-2311` |

## Notes
- `chromium-1232` was installed by another opencode session at 21:37 on Aug 1
- To clean up old versions: `npx playwright install --clean`
- Cache location: `~/Library/Caches/ms-playwright/`
