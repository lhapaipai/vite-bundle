# `pentatrion/vite-bundle` / `vite-plugin-symfony` Changelog

## v8.2.3

- Add support for Symfony 8 ([@skmedix](https://github.com/skmedix))

## v8.2.2

- vite-plugin-symfony: Support use modernPolyfills option in @vitejs/plugin-legacy ([@twodogwang](https://github.com/twodogwang))

## v8.2.1

- fix #83 asset twig method not using entrypoints baseUrl ([@micheh](https://github.com/micheh))

## v8.2.0

- add vite 7 support

## v8.1.1

- vite-plugin-symfony fix #75 allow users to override server.watch.ignored ([@robinsimonklein](https://github.com/robinsimonklein))

## v8.1.0

stimulus with svelte : update to svelte 5 ([@faldor20](https://github.com/faldor20))

## v8.0.2

- vite-bundle fix #62 can't use environment variables for default_config

## v8.0.1

- vite-plugin-symfony fix #60 move postinstall hook to pre-dev.

## v8.0.0

- stimulus fix hmr option from `VitePluginSymfonyStimulusOptions`
- stimulus fix hmr with lazy loaded controllers
- stimulus prevent hmr when controller is not already registered (#56)
- stimulus add `controllersDir` option to prevent analyse Stimulus meta for other files.

## v7.1.0

- allow Vite 6 as peer dependency ([@skmedix](https://github.com/skmedix))

## v7.0.5

- add origin to internal tags ([@seggewiss](https://github.com/seggewiss))

## v7.0.4

- fix use `proxy_origin` in Debugger if configured (@andyexeter)

## v7.0.3

- stimulus fix import.meta regex to support comments

## v7.0.2

- stimulus plugin check module entrypoint inside controllers.json
- fix vite-plugin-symfony partial options TypeScript type.

## v7.0.1

- fix Symfony try to register twice `TypeExtension`.

## v7.0.0

- new Profiler
- change crossorin default value
- better `PreloadAssetsEventListener`
- stimulus refactorisation

## v6.5.3

- fix vite-plugin-symfony tsup export when package is ESM.

## v6.5.2

- fix dummy-non-existing-folder to be created when used with vitest UI.

## v6.5.1

- fix overriding types from '@hotwired/stimulus'

## v6.5.0

- move v6.4.7 to 6.5.0 : flex recipes accept only minor version number (not patch).

## v6.4.7

- vite-bundle : prepare v7 flex recipe add pentatrion_vite.yaml route file into install directory 

## v6.4.6

- vite-bundle : add throw_on_missing_asset option

## v6.4.5

- vite-bundle : fix Crossorigin attribute needs adding to Link headers (@andyexeter)
- vite-bundle : Skip devServer lookup if proxy is defined (@Blackskyliner)
- vite-bundle : fix typo in error message when outDir is outside project root (@acran)

## v6.4.4

- vite-plugin-symfony : fix typo in error message when outDir is outside project root (@acran)
- vite-plugin-symfony : revert emptying `outDir` in dev mode (thanks @nlemoine)

## v6.4.3

- vite-bundle : fix deprecation warning with `configs` key in multiple config.

## v6.4.2

- doc add https tip with symfony cli certificate. (@nlemoine)
- fixed symfony/ux-react inability to load tsx components (@vladcos)

## v6.4.1

- fix import.meta in cjs env
- vite-plugin-symfony : fix Displaying the statuses of Stimulus controllers in production https://github.com/lhapaipai/vite-plugin-symfony/issues/38

## v6.4.0

- vite-plugin-symfony : add exposedEnvVars option
- vite-plugin-symfony : fix enforcePluginOrderingPosition https://github.com/lhapaipai/vite-bundle/issues/80
## v6.3.6

- fix crossorigin attribute to Link header for scripts with type=module (@andyexeter)

## v6.3.5

- fix vite-plugin-symfony support having externals dependencies.
- increase vite-bundle php minimum compatibility to 8.0
  no major version because the bundle was unusable with php 7.4 because of mixed type.

## v6.3.4

- Use Request::getUriForPath to build absolute URLs (@andyexeter)
- Formatting fix in vite printUrls output (@andyexeter)

## v6.3.3

- Fix dark mode issue with background
- Fix worker mode (kernel.reset)

## v6.3.2

- Moving package manager to pnpm

## v6.3.1

- Fix React/Vue/Svelte dependencies with Stimulus helper (@santos-pierre) 
- vite-plugin-symfony Update dependencies

## v6.3.0

- stimulus HMR
- fix bug : stimulus restart vite dev server when controllers.json is updated
- split vite-plugin-symfony into 2 plugins `vite-plugin-symfony-entrypoints` and `vite-plugin-symfony-stimulus`.
- add new tests to vite-plugin-symfony
- doc : add mermaid charts

## v6.2.0

- fix #77 support Vite 5.x

## v6.1.3

- fix #34 set warning when setting a build directory outside of your project

## v6.1.2

- stimulus lazy controllers enhancement
- Fix : prevent virtual controllers.json prebundling
- Fix : Change dependency to the non-internal ServiceLocator class (@NanoSector)
- Fix : Carelessly setting the outDir folder leads to recursive deletion (@Huppys)

## v6.1.0

- add Stimulus and Symfony UX Integration

## v6.0.1

- add `enforceServerOriginAfterListening`

## v6.0.0

- make services privates.
- add tests for EntrypointRenderer, EntrypointsLookup and TagRenderer.
- add preload option (symfony/web-link)
- add cache option
- add crossorigin option
- add preload_attributes option
- change default_build/builds to default_config/configs
- fix baseUrl to files #67
- refactor RenderAssetTagEvent 

## v5.0.1

- remove deprecated options
- fix `absolute_url` error in `shouldUseAbsoluteURL`.

## v5.0.0

- change `entrypoints.json` property `isProd` to `isBuild` because you can be in dev env and want to build your js files.

## v4.3.2

- fix #26 TypeError when no root option (@andyexeter) 

## v4.3.1

- add vendor, var and public to ignored directory for file watcher.

## v4.3.0

- add `absolute_url` bundle option.
- add `absolute_url` twig option. (@drazik)

## v4.2.0

- add enforcePluginOrderingPosition option
- fix Integrity hash issue
- add `vite_mode` twig function

## v4.1.0

- add `originOverride` (@elliason)
- deprecate `viteDevServerHostname`

## v4.0.2

- fix #24 normalized path

## v4.0.1

- fix conditional imports generate modulepreloads for everything

## v4.0.0

- add `sriAlgorithm`
- fix react refresh when vite client is returned
- add CDN feature

## v3.3.2

- fix #16 entrypoints outside vite root directory

## v3.3.1

- fix circular reference with imports.
- deprecate `public_dir` / `base`
- add `public_directory` / `build_directory`

## v3.3.0

- add tests
- versionning synchronization between pentatrion/vite-bundle and vite-plugin-symfony

---

before version 3.3 the versions of ViteBundle and vite-plugin-symfony were not synchronized


# `pentatrion/vite-bundle` Changelog

## v3.2.0

- add throw_on_missing_entry option (@Magiczne)

## v3.1.4

- add proxy_origin option (@FluffyDiscord)

## v3.1.0

- allow vite multiple configuration files

## v3.0.0

- Add vite 4 compatibility

## v2.2.1

- the choice of the vite dev server port is no longer strict, if it is already used the application will use the next available port.

## v2.2.0

- add extra attributes to script/link tags

## v2.1.1

- update documentation, update with vite-plugin-symfony v0.6.0

## v2.1.0

- add CSS Entrypoints management to prevent FOUC.

## v1.1.4

- add EntrypointsLookup / EntrypointsRenderer as a service.

## v1.1.0

- Add public_dir conf

## v1.0.2

- fix vite.config path error with windows

## v1.0.1 

- fix exception when entrypoints.json is missing

## v1.0.0

- Twig functions refer to named entry points not js file
- Add vite-plugin-symfony

## v0.2.0

Add proxy Controller


---

# `vite-plugin-symfony` changelog

## v0.6.3

- takes into account vite legacy plugin.

## v0.6.2

- add `viteDevServerHost` plugin option

## v0.6.1

- remove `strictPort: true`

## v0.6.0

- add `publicDirectory`, `buildDirectory`, `refresh`, `verbose` plugin option
- add `dev-server-404.html` page

## v0.5.2

- add `servePublic` plugin option
