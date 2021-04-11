# ViteBundle : Symfony integration with Vite

This bundle helping you render all of the dynamic `script` and `link` tags needed.

Install the bundle with

```
composer require lhapaipai/vite-bundle
```

create a directory structure for your js/css files:
```
├──assets
│ ├──app.js
│ ├──app.css
│...
├──public
├──composer.json
├──package.json
├──vite.config.js
```

create or complete your `package.json`
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
  },
  "devDependencies": {
    "vite": "^2.1.5"
  }
}
```

create a `vite.config.js` file on your project root directory.
the symfonyPlugin and the `manifest: true` are required for the bundle to work. when you run the `npm run dev` the plugin remove the manifest.json file so ViteBundle know that he must return the served files.
when you run the `npm run build` the manifest.json is constructed and ViteBundle read his content to return the build files.
```js
// vite.config.js
import { resolve } from 'path';
import { unlinkSync, existsSync } from 'fs';


const symfonyPlugin = {
  name: 'symfony',
  configResolved(config) {
    if (config.env.DEV && config.build.manifest) {
      let buildDir = resolve(config.root, config.build.outDir, 'manifest.json')
      existsSync(buildDir) && unlinkSync(buildDir);
    }
  },
  configureServer(devServer) {
    let { watcher, ws } = devServer;
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

export default {
  plugins: [symfonyPlugin],
  server: {
    watch: {
      disableGlobbing: false
    }
  },
  root: './assets',
  base: '/build/',
  build: {
    manifest: true,
    emptyOutDir: true,
    assetsDir: '',
    outDir: '../public/build/',
    rollupOptions: {
      input: [
        './assets/app.js'
      ]
    }
  }
};
```


## Configuration

default configuration

```yaml
# config/packages/lhapaipai_vite.yaml
lhapaipai_vite:
  # Base public path when served in development or production
  base: /build/

  # Server options
  server:
    host: localhost
    port: 3000
    https: false

```


## Usage

```twig
{# any template or base layout where you need to include a JavaScript entry #}

{% block javascripts %}
    {{ parent() }}

    {{ vite_entry_script_tags('app.js') }}
{% endblock %}

{% block stylesheets %}
    {{ parent() }}

    {{ vite_entry_link_tags('app.js') }}
{% endblock %}
```