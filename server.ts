/**
 * Full-Stack Bridge Server
 * Online Tourism Portal
 *
 * Spawns the PHP 8.2 built-in server (port 8080) and seamlessly proxies
 * all HTTP requests from port 3000 to the PHP application, preserving
 * sessions, POST bodies, file uploads, cookies, and redirects.
 */

import 'dotenv/config';
import express from 'express';
import type { Request, Response } from 'express';
import http from 'node:http';
import { spawn, spawnSync, execSync, ChildProcess } from 'node:child_process';
import path from 'node:path';

import fs from 'node:fs';

const app = express();

function getPort(): number {
  const portIdx = process.argv.indexOf('--port');
  if (portIdx !== -1 && process.argv[portIdx + 1]) {
    return parseInt(process.argv[portIdx + 1], 10);
  }
  // In AI Studio / Cloud Run, Nginx occupies port 8080 as ingress and forwards to port 3000
  if (process.env.PORT && process.env.PORT !== '8080') {
    return parseInt(process.env.PORT, 10);
  }
  return 3000;
}

const PORT = getPort();
// Cloud Run assigns PORT=8080 by default. PHP must listen on a distinct internal port (e.g. 8088).
const PHP_PORT = process.env.PHP_PORT
  ? parseInt(process.env.PHP_PORT, 10)
  : (PORT === 8088 ? 8089 : 8088);
const WORKSPACE_DIR = process.cwd();

// Ensure database directory exists with write access
try {
  const dbDir = path.join(WORKSPACE_DIR, 'database');
  if (!fs.existsSync(dbDir)) {
    fs.mkdirSync(dbDir, { recursive: true, mode: 0o777 });
  }
} catch (e) {
  console.warn('[Bridge] Database directory initialization notice:', e);
}

let phpProcess: ChildProcess | null = null;

function ensurePhpInstalled(): void {
  try {
    const check = spawnSync('which', ['php']);
    if (check.status !== 0) {
      console.log('[Bridge] PHP not found, auto-installing php-cli, php-sqlite3, and php-curl...');
      execSync('DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends -o Dpkg::Options::="--force-confold" php-cli php-sqlite3 php-curl', { stdio: 'inherit' });
    }
  } catch (err) {
    console.error('[Bridge] PHP pre-check warning:', err);
  }
}

function startPhpServer(): Promise<void> {
  ensurePhpInstalled();
  return new Promise((resolve, reject) => {
    console.log(`[Bridge] Starting PHP 8.2 Built-in Web Server on 127.0.0.1:${PHP_PORT}...`);

    phpProcess = spawn(
      'php',
      [
        '-d', 'display_errors=1',
        '-d', 'display_startup_errors=1',
        '-d', 'error_reporting=E_ALL',
        '-S', `127.0.0.1:${PHP_PORT}`,
        '-t', WORKSPACE_DIR,
      ],
      {
        cwd: WORKSPACE_DIR,
        stdio: ['ignore', 'pipe', 'pipe'],
      }
    );

    let started = false;

    phpProcess.stdout?.on('data', (data) => {
      const msg = data.toString();
      process.stdout.write(`[PHP] ${msg}`);
      if (!started && (msg.includes('started') || msg.includes('Development Server'))) {
        started = true;
        resolve();
      }
    });

    phpProcess.stderr?.on('data', (data) => {
      const msg = data.toString();
      process.stderr.write(`[PHP] ${msg}`);
      if (!started && (msg.includes('started') || msg.includes('Development Server'))) {
        started = true;
        resolve();
      }
    });

    phpProcess.on('error', (err) => {
      console.error('[Bridge] Failed to spawn PHP process:', err);
      if (!started) reject(err);
    });

    phpProcess.on('exit', (code, signal) => {
      console.log(`[Bridge] PHP process exited with code ${code}, signal ${signal}`);
    });

    // Fallback timeout in case output doesn't match standard strings
    setTimeout(() => {
      if (!started) {
        started = true;
        resolve();
      }
    }, 1200);
  });
}

// Health check endpoint for container diagnostics
app.get('/_health', (_req: Request, res: Response) => {
  res.json({
    status: 'ok',
    phpRunning: phpProcess !== null && !phpProcess.killed,
    port: PORT,
    phpPort: PHP_PORT,
  });
});

// Proxy all requests directly to the PHP web server
app.use((req: Request, res: Response) => {
  const proto = (req.headers['x-forwarded-proto'] as string) || 'https';
  const options: http.RequestOptions = {
    hostname: '127.0.0.1',
    port: PHP_PORT,
    path: req.url,
    method: req.method,
    headers: {
      ...req.headers,
      host: `127.0.0.1:${PHP_PORT}`,
      'x-forwarded-host': req.headers.host || 'localhost:3000',
      'x-forwarded-proto': proto,
    },
  };

  const proxyReq = http.request(options, (proxyRes) => {
    // Preserve response headers and status
    const headers = { ...proxyRes.headers };

    // Rewrite Set-Cookie headers for cross-origin iframe compatibility (SameSite=None, Secure, Partitioned)
    if (headers['set-cookie']) {
      const cookies = Array.isArray(headers['set-cookie'])
        ? headers['set-cookie']
        : [headers['set-cookie']];

      headers['set-cookie'] = cookies.map((cookieStr) => {
        let cookie = cookieStr;
        if (!/SameSite=/i.test(cookie)) {
          cookie += '; SameSite=None';
        } else {
          cookie = cookie.replace(/SameSite=(Lax|Strict)/gi, 'SameSite=None');
        }
        if (!/Secure/i.test(cookie)) {
          cookie += '; Secure';
        }
        if (!/Partitioned/i.test(cookie)) {
          cookie += '; Partitioned';
        }
        return cookie;
      });
    }

    res.writeHead(proxyRes.statusCode || 200, headers);
    proxyRes.pipe(res);
  });

  proxyReq.on('error', (err) => {
    console.error('[Bridge] Proxy request error:', err.message);
    if (!res.headersSent) {
      res.status(502).send(`
        <!DOCTYPE html>
        <html>
        <head><title>Starting Tourism Portal...</title><meta http-equiv="refresh" content="2"></head>
        <body style="font-family: sans-serif; text-align: center; padding: 50px;">
          <h2>Starting Online Tourism Portal PHP Server...</h2>
          <p>Please wait a moment while the PHP runtime initializes...</p>
        </body>
        </html>
      `);
    }
  });

  // Stream request body (POST, PUT, uploads) directly to PHP
  req.pipe(proxyReq);
});

// Start Express HTTP server immediately so Cloud Run port checks pass with zero delay
const server = app.listen(PORT, '0.0.0.0', () => {
  console.log(`[Bridge] Tourism Portal is live at http://0.0.0.0:${PORT} (proxying to PHP on 127.0.0.1:${PHP_PORT})`);
});

server.on('error', (err: NodeJS.ErrnoException) => {
  console.error('[Bridge] Fatal server listen error:', err);
  process.exit(1);
});

// Launch PHP built-in server in the background
startPhpServer().catch((err) => {
  console.error('[Bridge] Error initializing PHP runtime:', err);
});

// Clean shutdown handler
process.on('SIGINT', () => {
  if (phpProcess) phpProcess.kill('SIGINT');
  server.close(() => process.exit(0));
});

process.on('SIGTERM', () => {
  if (phpProcess) phpProcess.kill('SIGTERM');
  server.close(() => process.exit(0));
});
