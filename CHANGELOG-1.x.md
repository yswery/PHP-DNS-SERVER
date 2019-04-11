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
