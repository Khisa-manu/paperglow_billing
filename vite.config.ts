import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { spawn } from 'child_process';
import { defineConfig, Plugin } from 'vite';

function phpServerPlugin(): Plugin {
  let phpProc: any = null;

  return {
    name: 'php-server-launcher',
    configureServer() {
      try {
        phpProc = spawn('php', ['-S', '127.0.0.1:8088', 'router.php'], {
          cwd: path.resolve(__dirname, '.'),
          stdio: 'ignore',
        });
        phpProc.on('error', (err: any) => {
          console.error('[PHP Server Error]', err);
        });
      } catch (e) {
        console.error('[Failed to spawn PHP Server]', e);
      }
    },
  };
}

export default defineConfig(() => {
  return {
    plugins: [react(), tailwindcss(), phpServerPlugin()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, '.'),
      },
    },
    server: {
      proxy: {
        '/php': {
          target: 'http://127.0.0.1:8088',
          changeOrigin: true,
          rewrite: (p) => p.replace(/^\/php/, ''),
        },
        '/auth': 'http://127.0.0.1:8088',
        '/dashboard': 'http://127.0.0.1:8088',
        '/customers': 'http://127.0.0.1:8088',
        '/quotations': 'http://127.0.0.1:8088',
        '/invoices': 'http://127.0.0.1:8088',
        '/payments': 'http://127.0.0.1:8088',
        '/company': 'http://127.0.0.1:8088',
        '/reports': 'http://127.0.0.1:8088',
        '/assets': 'http://127.0.0.1:8088',
        '/uploads': 'http://127.0.0.1:8088',
      },
      hmr: process.env.DISABLE_HMR !== 'true',
      watch: process.env.DISABLE_HMR === 'true' ? null : {},
    },
  };
});
