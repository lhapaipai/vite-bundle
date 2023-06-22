## v3.3.1

- deprecate `public_dir` / `base`
- add `public_directory` / `build_directory`

## v3.3.0

- versionning synchronization between pentatrion/vite-bundle and vite-plugin-symfony

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