# Transbank Virtuemart 3.x Webpay REST Plugin

## Descripción

Este plugin de Virtuemart 3.x implementa el [SDK REST de PHP de Webpay](https://github.com/TransbankDevelopers/transbank-sdk-php) en modalidad checkout (REST). 

## Dependencias

* transbank/transbank-sdk

## Nota  
- La versión del sdk de php se encuentra en el archivo `composer.json`

## Preparar el proyecto para bajar dependencias

    ./config.sh

## Crear una versión del plugin empaquetado 

    ./package.sh

## Instalación del plugin para un comercio

El manual de instalación para el usuario final se encuentra disponible [acá](docs/INSTALLATION.md) o en PDF [acá](https://github.com/TransbankDevelopers/transbank-plugin-virtuemart-webpay/raw/master/docs/INSTALLATION.pdf
)

## Desarrollo

Para apoyar el levantamiento rápido de un ambiente de desarrollo, hemos creado un [Dev Container](https://containers.dev/)
que levanta Joomla 3 + VirtueMart 3.x junto a un contenedor de trabajo con PHP, Composer y Node.

Para usarlo, abre el proyecto en VS Code y selecciona "Reopen in Container". Ver el detalle en [.devcontainer/README.md](.devcontainer/README.md).

## Generar una nueva versión

Para generar una nueva versión, se debe crear un PR (con un título "Prepare release X.Y.Z" con los valores que correspondan para `X`, `Y` y `Z`). Se debe seguir el estándar semver para determinar si se incrementa el valor de `X` (si hay cambios no retrocompatibles), `Y` (para mejoras retrocompatibles) o `Z` (si sólo hubo correcciones a bugs).

En ese PR deben incluirse los siguientes cambios:

1. Modificar el archivo CHANGELOG.md para incluir una nueva entrada (al comienzo) para `X.Y.Z` que explique en español los cambios.

Luego de obtener aprobación del pull request, debes mezclar a master e inmediatamente generar un release en GitHub con el tag `X.Y.Z`. En la descripción del release debes poner lo mismo que agregaste al changelog.

Con eso, el workflow de GitHub Actions [`release.yml`](.github/workflows/release.yml) generará automáticamente el paquete del plugin y lo adjuntará al Release de GitHub como archivo `.zip`.
