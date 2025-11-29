# PMMP WebServer

A verion for PocketMine-MP plugins to create a simple HTTP/1.1 web server.

## What is new in version v0.6.0

- Complete overhaul of the HTTP message parsing
- Rewrite of the Routing system
- Many changes to the API, please use the [Upgrade guide](#upgrade-guide) and updated docs if you are going to upgrade
  to v0.6.0.
- Why was this needed?
    - The previous HTTP Message parsing was not very clean and became very confusing to maintain.
    - With the new routing system, a more optimized tree is used to make it a bit more efficient.
    - The old system required all data to be present when sending data, the new system makes use of streaming buffers to
      send data in chunks. This allows you to send large files without having to load them all into memory at once..

### Upgrade guide

// TODO

## Plugins
- This virion is used in my Dynmap like plugin, `PocketMap`. You can find the plugin [here](https://github.com/Hebbinkpro/PocketMap)

## How to install
- Download the latest phar build from [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/pmmp-webserver)
- or install it directly using composer: `composer require hebbinkpro/pmmp-webserver`

## Documentation

The documentation has been moved !!! They can now be found [here](docs/README.md).

## Credits
- This virion makes use of [Laravel\SerializableClosure](https://github.com/laravel/serializable-closure) for sharing the action functions given in the `Router` on the main thread with the http server thread.