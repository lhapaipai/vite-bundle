<p align="center">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/symfony.svg" alt="Symfony logo">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/vitejs.svg" alt="Vite logo">
</p>

# ViteBundle : Symfony integration with Vite

This bundle helping you render all of the dynamic `script` and `link` tags needed.
Essentially, he provide two twig functions to load the correct scripts into your templates.

```twig
{# any template or base layout where you need to include a JavaScript entry #}

{% block stylesheets %}
    {# specify here your entry point relative to the assets directory #}
    {{ vite_entry_link_tags('app.js') }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app.js') }}
{% endblock %}
```

would render in dev:

```html
<!--Nothing with vite_entry_link_tags('app.js') -->

<!-- vite_entry_script_tags('app.js') -->
<script src="http://localhost:3000/build/@vite/client" type="module"></script>
<script src="http://localhost:3000/build/app.js" type="module"></script>
```

would render in prod:

```html
<!-- vite_entry_link_tags('app.js') -->
<link rel="stylesheet" href="/build/app.[hash].css" />
<link rel="modulepreload" href="/build/vendor.[hash].js" />

<!-- vite_entry_script_tags('app.js') -->
<script src="/build/app.[hash].js" type="module"></script>
```

if you are using React, you have to add this option in order to have FastRefresh.

```twig
{{ vite_entry_script_tags('app.js', { dependency: 'react' }) }}
```

## Installation

Install the bundle with

```console
composer require pentatrion/vite-bundle
```

and it's over if you activate pentatrion/vite-bundle community recipe. Otherwise see manual installation at the end

## Configuration

default configuration

```yaml
# config/packages/pentatrion_vite.yaml
pentatrion_vite:
    # Base public path when served in development or production
    base: /build/

    # Server options
    server:
        host: localhost
        port: 3000
        https: false
```

## Manual installation

```console
composer require pentatrion/vite-bundle
```

if you do not want to use the recipe or want to see in depth what is modified by it, create a directory structure for your js/css files:

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
        "build": "vite build"
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
import { resolve } from "path";
import { unlinkSync, existsSync } from "fs";

/* if you're using React */
// import reactRefresh from "@vitejs/plugin-react-refresh";

const symfonyPlugin = {
    name: "symfony",
    configResolved(config) {
        if (config.env.DEV && config.build.manifest) {
            let buildDir = resolve(
                config.root,
                config.build.outDir,
                "manifest.json"
            );
            existsSync(buildDir) && unlinkSync(buildDir);
        }
    },
    configureServer(devServer) {
        let { watcher, ws } = devServer;
        watcher.add(resolve("templates/**/*.twig"));
        watcher.on("change", function (path) {
            if (path.endsWith(".twig")) {
                ws.send({
                    type: "full-reload",
                });
            }
        });
    },
};

export default {
    plugins: [
        /* reactRefresh(), // if you're using React */
        symfonyPlugin,
    ],
    server: {
        watch: {
            disableGlobbing: false,
        },
    },
    root: "./assets",
    base: "/build/",
    build: {
        manifest: true,
        emptyOutDir: true,
        assetsDir: "",
        outDir: "../public/build/",
        rollupOptions: {
            input: ["./assets/app.js"],
        },
    },
};
```
