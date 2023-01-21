### Migration from Webpack Encore

## Install

WebpackEncoreBundle is linked with a Symfony Recipe. Before remove this bundle, backup your `assets` content and `package.json`/`package-lock.json` in another location. They will be deleted when you'll remove the bundle.

```console
mv assets assets.bak
mv package.json package.json.bak
mv package-lock.json package-lock.json.bak
composer remove symfony/webpack-encore-bundle
```

You can safely rename your backup and install the ViteBundle
```console
mv assets.bak assets
mv package.json.bak package.json
mv package-lock.json.bak package-lock.json
composer require pentatrion/vite-bundle
```

You need to add manually the `vite` and `vite-plugin-symfony` packages and scripts in your existant `package.json`. check the [package.json](https://github.com/lhapaipai/vite-bundle/blob/main/install/package.json) reference file.


## Configuration

There is some minor differences with the twig functions


```diff
// webpack.config.js
-Encore.addEntry("app", "./assets/app.js");
```

```diff
// vite.config.js
+export default {
+    // ...
+    plugins: [
+      symfonyPlugin()
+    ],
+    build: {
+        rollupOptions: {
+            input: {
+                app: "./assets/app.js"
+            },
+        },
+    },
+};
```


```diff
{% block stylesheets %}
-    {{ encore_entry_link_tags('app') }}
+    {{ vite_entry_link_tags("app") }}
{% endblock %}

{% block javascripts %}
-    {{ encore_entry_script_tags('app') }}
+    {{ vite_entry_script_tags("app") }}
{% endblock %}
```


