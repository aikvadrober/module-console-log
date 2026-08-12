# AiKvadrober_ConsoleLog

## What it does

Captures frontend JavaScript console output and writes it to a dedicated Magento log file for debugging.

On the frontend, the module intercepts `console.log`, `console.warn`, `console.error`, `console.info`, and `console.debug` calls, buffers them, and sends the payload as JSON to a Magento controller. The controller logs the payload to `var/log/frontend-console.log` via Monolog.

## How it works

### Frontend (JavaScript)

- Injected on all frontend pages via `view/frontend/layout/default.xml`
- Wraps native console methods; original behavior is preserved
- Buffers log entries (level, timestamp, URL, arguments) in memory
- Sends the buffer to `/consolelog/logger/save` via `fetch()` with `keepalive: true`
- Exposes `window.flushConsoleLogs()` for manual flushing
- Currently disabled by default: the `allowed` flag is `false` and all auto-flush timers are commented out

### Backend (PHP)

- Route: `POST /consolelog/logger/save`
- Controller: `AiKvadrober\ConsoleLog\Controller\Logger\Save`
- Accepts raw JSON payload, unserializes it, and logs via a custom `FrontendLogger`
- CSRF validation is disabled for this endpoint
- Logs are written to `var/log/frontend-console.log` through a custom Monolog handler

## Files

| File | Purpose |
|------|---------|
| `registration.php` | Module registration |
| `etc/module.xml` | Module declaration: `AiKvadrober_ConsoleLog` |
| `etc/frontend/routes.xml` | Frontend route: `consolelog` |
| `etc/di.xml` | DI configuration for logger and controller |
| `Controller/Logger/Save.php` | Endpoint controller |
| `Logger/FrontendLogger.php` | Custom Monolog logger |
| `Logger/Handler.php` | Writes to `var/log/frontend-console.log` |
| `view/frontend/layout/default.xml` | Injects JS on all pages |
| `view/frontend/web/js/console-log.js` | Console interception and batching logic |

## Dependencies

- Magento 2
- `magento/framework`
- `monolog/monolog` (provided by Magento)
