# Changelog

## [1.0.1](https://github.com/iv0ks/deskmint/compare/v1.0.0...v1.0.1) (2026-11-11)


### Bug Fixes

* drop composer builder stage and vendor copy (no runtime deps) ([891de63](https://github.com/iv0ks/deskmint/commit/891de6387a5dd57a43919be8fa3d26b8b70ebc2f))

## [1.0.0](https://github.com/iv0ks/deskmint/compare/v1.0.0...v1.0.0) (2026-11-11)


### Features

* add helpdesk ticket data and decoy attachments ([618eec0](https://github.com/iv0ks/deskmint/commit/618eec00879e3bee3dbc723b7f3f91f2c6aec378))
* add helpdesk ui with ticket list, ticket view and favicon ([f4771e3](https://github.com/iv0ks/deskmint/commit/f4771e327e45c68ad0bdebca9ea1122f53face69))
* add vulnerable php attachment endpoint (non-recursive filter) ([ab92ecc](https://github.com/iv0ks/deskmint/commit/ab92ecc8b18a8dbb6f1afc9c565dce255f017670))


### Refactoring

* replace nginx+php-fpm with a single php:8.3-apache image ([6c0f6c0](https://github.com/iv0ks/deskmint/commit/6c0f6c02082678b1ece13801e5ec2ab7fef5267f))


### Build System

* add nginx + php-fpm docker setup and compose with traefik ([a6c3836](https://github.com/iv0ks/deskmint/commit/a6c383617bcb2d8db81ce85bfa79b49e51d07000))
* **deps-dev:** update phpstan/phpstan requirement from ^1.12 to ^2.3 ([#1](https://github.com/iv0ks/deskmint/issues/1)) ([5c08907](https://github.com/iv0ks/deskmint/commit/5c08907caf7ac9d6ac0dc816165bea51816ec603))
* **deps-dev:** update phpunit/phpunit requirement from ^10.5 to ^13.4 ([#2](https://github.com/iv0ks/deskmint/issues/2)) ([8d91e3a](https://github.com/iv0ks/deskmint/commit/8d91e3a349678dad29e8d15b8a9598039badb0e9))
* simplify compose to a single apache service ([a8d6b04](https://github.com/iv0ks/deskmint/commit/a8d6b04f14bd819c1049f3f7affcdd977c16b2d0))
