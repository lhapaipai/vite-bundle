import { resolve } from 'path';
import { unlinkSync, existsSync } from 'fs';

const twigRefreshPlugin = {
  name: 'twig-refresh',
  configureServer(devServer) {
    let { watcher, ws } = devServer;
    // console.log(devServer);
    watcher.add(resolve('templates/**/*.twig'));
    watcher.on('change', function (path) {
      if (path.endsWith('.twig')) {
        ws.send({
          type: 'full-reload'
        });
      }
    })
  }
};

const manifestPlugin = {
  name: 'manifest-plugin',
  configureServer({ config }) {
    if (config.env.DEV && config.build.manifest) {
      let manifestPath = resolve(config.root, config.build.outDir, 'manifest.json')
      existsSync(manifestPath) && unlinkSync(manifestPath);
    }
  }
}

module.exports = {
  plugins: [twigRefreshPlugin, manifestPlugin],
  server: {
    watch: {
      disableGlobbing: false
    }
  },
  root: './assets',
  base: '/assets/',
  build: {
    manifest: true,
    assetsDir: '',
    outDir: '../public/assets/',
    rollupOptions: {
      input: [
        './assets/app.js'
      ]
    }
  }
};
