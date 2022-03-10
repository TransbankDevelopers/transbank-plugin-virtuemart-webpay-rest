# Changelog
Todos los cambios notables a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
y este proyecto adhiere a [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2022-03-10
### Fixed
- Se corrige error en la generación de documentos delivery note e invoices.
- Se corrige nombre de método createTransaction.
- Se actualiza el logo de Webpay en el modal de información.

### Deleted
- Se elimina librería TCPDF del plugin. Desde ahora se utiliza versión de la misma librería
disponible en Joomla.

## [1.2.0] - 2021-09-12
### Added
- Se actualiza SDK de PHP a versión 2.0.8
- Se actualiza la versión de TCPDF a 6.4.2
- Soporte para PHP desde la versión  7.0.0
- Se soluciona problema que impedía mostrar el modal de diagnóstico.
- Se elimina soporte de SOAP del modal de diagnóstico.

## [1.1.0] - 2021-04-09
### Added
- Se actualiza SDK de PHP a versión 2.0, por lo que ahora se usa la API v1.2 de Transbank.

### Fixed
- Se debe resolver posible problema de perder sesión al volver del medio de pago.

## [1.0.0] - 2020-11-03
### Added
Se agrega soporte para REST. Basado en la versión 2.0.9 del plugin Virtuemart SOAP.
### Fixed
- Se arregla texto en configuración referente a API Key
