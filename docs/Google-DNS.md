# Google DNS

Resolver class `GoogleDns.php` uses Google's DNS-over-HTTPS service to
resolve records. GoogleDNS resolver could be used as a drop in replacement
instead of `SystemResolver` to avoid eavesdropping on DNS requests.

Upon DNS query server will issue HTTPS request to Google service and obtain
information on query, information will further be delivered to client in form of DNS response.

Resolver at the moment supports `A` and `AAAA` type records.

For more information refer to: https://developers.google.com/speed/public-dns/docs/dns-over-https
