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
    root: "./assets",
    build: {
        rollupOptions: {
            input: {
                app: "./assets/app.js"
            },
        },
    },
};
```

```twig
{% block javascripts %}
    {{ vite_entry_script_tags("app") }}
{% endblock %}

{% block stylesheets %}
    {{ vite_entry_link_tags("app") }}
{% endblock %}
```


