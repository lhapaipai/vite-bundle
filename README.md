<p align="center">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/symfony-vite.svg" alt="Symfony logo">
</p>

# ViteBundle : Symfony integration with Vite

This bundle helping you render all of the dynamic `script` and `link` tags needed.
Essentially, he provides two twig functions to load the correct scripts into your templates.

## Installation

Install the bundle with

```console
composer require pentatrion/vite-bundle
```

if you don't have a `package.json` file already you can execute the `pentatrion/vite-bundle` community recipe. Otherwise see [manual installation](https://github.com/lhapaipai/vite-bundle/blob/main/docs/manual-installation.md).

As long as the symfony recipe update has not yet been merged, add manually vite route to your dev Symfony app. Modify if necessary the prefix by following the `vite.config.js` `base` property without final slash.

```yaml
# config/routes/dev/pentatrion_vite.yaml
_pentatrion_vite:
    prefix: /build
    resource: "@PentatrionViteBundle/Resources/config/routing.yaml"
```


```bash
npm install

# start your vite dev server
npm run dev
```

Add this twig functions in any template or base layout where you need to include a JavaScript entry.

```twig
{% block stylesheets %}
    {{ vite_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

if you are using React, you have to add this option in order to have FastRefresh.

```twig
{{ vite_entry_script_tags('app', { dependency: 'react' }) }}
```
If you come from Webpack Encore, check the [differences between Webpack Encore Bundle and Vite Bundle](https://github.com/lhapaipai/vite-bundle/blob/main/docs/migration-webpack-encore.md).

## Bundle Configuration

If you change some properties in your `vite.config.js` file, you probably need to create a `config/packages/pentatrion_vite.yaml` file to postpone these changes. it concerns `server.host`, `server.port`, `server.https` and `build.outdir` (and also `base`).

default configuration

```yaml
# config/packages/pentatrion_vite.yaml
pentatrion_vite:
    # Base public path when served in development or production
    base: /build/
    # path to the build folder relative to the Root directory
    public_dir: /public
    # Server options
    server:
        host: localhost
        port: 3000
        https: false
```

## Vite config

For the transparency, I decided not to create an overlay of the config file `vite.config.js`. However some config properties must not be modified for the bundle to work.

```js
// vite.config.js
import {defineConfig} from "vite";
import symfonyPlugin from "vite-plugin-symfony";

/* if you're using React */
// import reactRefresh from "@vitejs/plugin-react-refresh";

export default defineConfig({
    plugins: [
        /* reactRefresh(), // if you're using React */
        symfonyPlugin(),
    ],
    root: "./assets",      /* DO NOT CHANGE */

    build: {
        rollupOptions: {
            input: {
                app: "./assets/app.ts"
            },
        },
        outDir: "../public/build/",

        manifest: true,    /* DO NOT CHANGE */
        emptyOutDir: true, /* DO NOT CHANGE */
        assetsDir: "",     /* DO NOT CHANGE */
    },

    /* your outDir prefix relative to web path */
    base: "/build/",
});
```


## Usage tips

### Dependency Pre-Bundling

Initially in a Vite project, `index.html` is the entry point to your application. When you run your dev serve, Vite will crawl your source code and automatically discover dependency imports.

Because we don't have any `index.html`, Vite can't do this Pre-bundling step when he starts but when you browse a page where he finds a package he does not already have cached. Vite will re-run the dep bundling process and reload the page.

this behavior can be annoying if you have a lot of dependencies because it creates a lot of page reloads before getting to the final render.

you can limit this by declaring in the `vite.config.js` the most common dependencies of your project.

```js
// vite.config.js

export default defineConfig({
    server: {
        //Set to true to force dependency pre-bundling.
        force: true,
    },
    // ...
    optimizeDeps: {
        include: ["my-package"],
    },
});
```
### One file by entry point

Vite try to split your js files into multiple smaller files shared between entry points. In some cases, it's not a good choise and you can prefer output one js file by entry point.

```js
// vite.config.js

export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: undefined,
      },
    },
  },
});
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


## Migration from v0.2.x to v1.x

In version v0.2.x, you have to specify your entry points in an array in your `vite.config.js` file. in v1.x you need to specify your entry points in an object.

```diff
-input: ["./assets/app.js"],
+input: {
+  app: "./assets/app.js"
+},
```

this way you need to specify the named entry point in your twig functions.

```diff
-{{ vite_entry_script_tags('app.js') }}
+{{ vite_entry_script_tags('app') }}
-{{ vite_entry_link_tags('app.js') }}
+{{ vite_entry_link_tags('app') }}
```

In v1.x, your symfonyPlugin is a **function** and come from the `vite-plugin-symfony` package.

```diff
+ import symfonyPlugin from 'vite-plugin-symfony';

    // ...
    plugins: [
        /* reactRefresh(), // if you're using React */
-       symfonyPlugin,
+       symfonyPlugin(),
    ],
```


## How this bundle works

```twig
{% block stylesheets %}
    {{ vite_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

would render in dev:

```html
<!--Nothing with vite_entry_link_tags('app') -->

<!-- vite_entry_script_tags('app') -->
<script src="http://localhost:3000/build/@vite/client" type="module"></script>
<script src="http://localhost:3000/build/app.js" type="module"></script>
```

would render in prod:

```html
<!-- vite_entry_link_tags('app') -->
<link rel="stylesheet" href="/build/app.[hash].css" />
<link rel="modulepreload" href="/build/vendor.[hash].js" />

<!-- vite_entry_script_tags('app') -->
<script src="/build/app.[hash].js" type="module"></script>
```

In development environment, the bundle also acts as a proxy by forwarding requests that are not intended for it to the Vite dev server.

