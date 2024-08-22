<div>
  <p align="center">
  <img width="100" src="https://raw.githubusercontent.com/lhapaipai/vite-bundle/main/docs/symfony-vite.svg" alt="Symfony logo">
  </p>
  <p align="center">
    <img src="https://img.shields.io/packagist/v/pentatrion/vite-bundle?style=flat-square&logo=packagist">
    <img src="https://img.shields.io/github/actions/workflow/status/lhapaipai/symfony-vite-dev/vite-bundle-ci.yml?style=flat-square&label=vite-bundle%20CI&logo=github">

  </p>
</div>



# ViteBundle : Symfony integration with Vite

> [!IMPORTANT]
> This repository is a "subtree split": a read-only subset of that main repository [symfony-vite-dev](https://github.com/lhapaipai/symfony-vite-dev) which delivers to packagist only the necessary code.

> [!IMPORTANT]
> If you want to open issues, contribute, make PRs or consult examples you will have to go to the [symfony-vite-dev](https://github.com/lhapaipai/symfony-vite-dev) repository.


This bundle helps you render all the dynamic `script` and `link` tags needed.
Essentially, it provides two twig functions to load the correct scripts into your templates.

## Installation

Install the bundle with:

```console
composer require pentatrion/vite-bundle
```

```bash
npm install

# start your vite dev server
npm run dev
```

Add these twig functions in any template or base layout where you need to include a JavaScript entry:

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
| [vite-plugin-symfony](https://github.com/lhapaipai/vite-plugin-symfony) | Vite plugin (read-only)   |
| [symfony-vite-dev](https://github.com/lhapaipai/symfony-vite-dev)       | Package for contributors  |

## License

[MIT](LICENSE).
