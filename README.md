<p align="center">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/symfony-vite.svg" alt="Symfony logo">
</p>

# ViteBundle : Symfony integration with Vite

This bundle helping you render all of the dynamic `script` and `link` tags needed.
Essentially, he provides two twig functions to load the correct scripts into your templates.

⚠️ This repository is a "subtree split": a read-only subset of that main repository [symfony-vite-dev](https://github.com/lhapaipai/symfony-vite-dev).

## Installation

Install the bundle with :

```console
composer require pentatrion/vite-bundle
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

    {# if you are using React, you have to replace with this #}
    {{ vite_entry_script_tags('app', { dependency: 'react' }) }}
{% endblock %}
```

[Read the Docs to Learn More](https://symfony-vite.pentatrion.com).

## Ecosystem

| Package                                                                 | Description               |
| ----------------------------------------------------------------------- | :------------------------ |
| [vite-bundle](https://github.com/lhapaipai/vite-bundle)                 | Symfony Bundle            |
| [vite-plugin-symfony](https://github.com/lhapaipai/vite-plugin-symfony) | Vite plugin               |
| [symfony-vite-dev](https://github.com/lhapaipai/symfony-vite-dev)       | Package for contributors  |

## License

[MIT](LICENSE).