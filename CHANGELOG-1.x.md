CHANGELOG for 1.4
=================
## v1.4.1
* [PR #102](https://github.com/yswery/PHP-DNS-SERVER/pull/102) Require `symfony/property-access` version `4.3` instead of `5.0`.
## v1.4.0
* [PR #90](https://github.com/yswery/PHP-DNS-SERVER/pull/90) Added the beginning of a cli interface for PhpDnsServer, and a filesystem config for loading all .json files from a zones directory.
* [PR #93](https://github.com/yswery/PHP-DNS-SERVER/pull/93) Change private `Server` properties to protected.
* [PR #98](https://github.com/yswery/PHP-DNS-SERVER/pull/98) Drop support for PHP 7.1.
* [PR #99](https://github.com/yswery/PHP-DNS-SERVER/pull/99) New `BindResolver` class to add support for Bind9/named style records.
* [PR #100](https://github.com/yswery/PHP-DNS-SERVER/pull/100) New logger subscriber that listens too all events.

CHANGELOG for 1.3
=================
* New specialised classes for encoding and decoding Rdata: `RdataEncoder` & `RdataDecoder`.
* Upgrade to React Socket v1.2 and greater.
* Add `ServerTerminator` subscriber.

CHANGELOG for 1.2
=================
* New event `SERVER_START_FAIL` triggered when the server fails to start.
* RData encoding and decoding methods separated into their own classess: `RdataEncoder` and `RdataDecoder`.
* It is now optional to inject an `EventDispatcher` into `Server` instance.

CHANGELOG for 1.1
=================
* Normalised RDATA naming conventions to be consistent with RFC1035.
* Tests moved into the main src directory.
* Updated PHPUnit to latest version (v7.3.*).
* Implemented PSR Logger.
* The message, header and resource records are now represented by objects.
* Ability to respond to server events via event subscriber component.
* Optionally store dns records in Yaml or XML format.
* Implemented Symfony Event dispatcher.
* Resolvers support wildcard domains.
* Additional record processing happens automatically for SRV, NS and MX records.
