<p align="center">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/symfony.svg" alt="Symfony logo">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/vitejs.svg" alt="Vite logo">
</p>

# ViteBundle : Symfony integration with Vite

This bundle helping you render all of the dynamic `script` and `link` tags needed.
Essentially, he provides two twig functions to load the correct scripts into your templates.

## Installation

Install the bundle with

```console
composer require pentatrion/vite-bundle
```

if you don't have a `package.json` file already you can execute the `pentatrion/vite-bundle` community recipe. Otherwise see [manual installation](#manual-installation) at the end.

```bash
npm install

# start your vite dev server
npm run dev
```

Add this twig functions in any template or base layout where you need to include a JavaScript entry.

```twig
{% block stylesheets %}
    {# specify here your entry point relative to the assets directory #}
    {{ vite_entry_link_tags('app.js') }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app.js') }}
{% endblock %}
```

note : In your twig functions, you have to specify your entrypoint relative to your assets directory. Be careful to **put the .js extension** for both functions.

if you are using React, you have to add this option in order to have FastRefresh.

```twig
{{ vite_entry_script_tags('app.js', { dependency: 'react' }) }}
```

## Configuration

If you choose a custom configuration of your `vite.config.js` file, you probably need to create a `config/packages/pentatrion_vite.yaml` file.

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

## Usage tips

### Migration from Webpack Encore

If you come from Webpack pay attention to some differences with the name of the entry points.

```js
Encore.addEntry("app", "./assets/app.js");
```

```twig
{% block stylesheets %}
    {{ encore_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ encore_entry_script_tags('app') }}
{% endblock %}
```

will become

```js
// vite.config.js
export default {
    // ...
    build: {
        rollupOptions: {
            input: ["./assets/app.js"],
        },
    },
};
```

```twig
{% block javascripts %}
    {# 1. you need to add the extension
       2. you specify the entry point relative to the root option specified in the vite.config.js ("./assets") #}
    {{ vite_entry_script_tags("app.js") }}
{% endblock %}

{% block stylesheets %}
    {# 1. be careful it's not app.css !!
       2. you specify the entry point relative to the root option specified in the vite.config.js ("./assets") #}
    {{ vite_entry_link_tags("app.js") }}
{% endblock %}
```

### https / http in Development

By default, your Vite dev server don't use https and can cause unwanted reload if you serve your application with https. I advise you to choose between the 2 protocols and apply that same choice for your Vite dev server and Symfony local server

```console
npm run dev
symfony serve --no-tls
```

browse : `http://127.0.0.1:8000`

or

```js
// vite.config.js
export default defineConfig({
    // ...
    server: {
        https: true,
    },
});
```

```yaml
# config/packages/pentatrion_vite.yaml
pentatrion_vite:
    # Server options
    server:
        https: true
```

```console
npm run dev
symfony serve
```

browse : `https://127.0.0.1:8000`

### Dependency Pre-Bundling

Initially in a Vite project, `index.html` is the entry point to your application. When you run your dev serve, Vite will crawl your source code and automatically discover dependency imports.

Because we don't have any `index.html`, Vite can't do this Pre-bundling step when he starts but when you browse a page where he finds a package he does not already have cached. Vite will re-run the dep bundling process and reload the page.

this behavior can be annoying if you have a lot of dependencies because it creates a lot of page reloads before getting to the final render.

you can limit this by declaring in the `vite.config.js` the most common dependencies of your project.

```js
// vite.config.js

export default defineConfig({
    // ...
    optimizeDeps: {
        include: ["my-package"],
    },
});
```

## How this bundle works

```twig
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

In development environment, the bundle also acts as a proxy by forwarding requests that are not intended for it to the Vite dev server.

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

add vite route to your dev Symfony app.

```yaml
# config/routes/dev/pentatrion_vite.yaml
_pentatrion_vite:
    prefix: /build
    resource: "@PentatrionViteBundle/Resources/config/routing.yaml"
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
