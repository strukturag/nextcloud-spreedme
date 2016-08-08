# How to run Spreed WebRTC with Apache in subpath

To support Spreed WebRTC with an Apache HTTP Server, you require at least Apache
version 2.4.5 to be able to proxy Websocket protocol. All current distributions
should have this in their repositories.

Make sure you have the `mod_proxy_wstunnel`, `proxy`, `proxy_http` and `headers`
modules enabled. On Ubuntu, this goes like this `a2enmod proxy proxy_http proxy_wstunnel headers`.

Add the configuration section from below to your Apache virtual host to make
Spreed WebRTC available below the `/webrtc` subpath. Note that Spreed WebRTC
does require HTTPS and thus needs to be available via HTTPS.

## Apache configuration sniplet

```apacheconf
	<Location /webrtc>
		ProxyPass http://127.0.0.1:8080/webrtc
		ProxyPassReverse /webrtc
	</Location>

	<Location /webrtc/ws>
		ProxyPass ws://127.0.0.1:8080/webrtc/ws
	</Location>

	ProxyVia On
	ProxyPreserveHost On
	RequestHeader set X-Forwarded-Proto 'https' env=HTTPS
```

## Spreed WebRTC subpath configuration

To let Spreed WebRTC know that it is running in a subpath, you need to set the
`basePath` configuration in the `[http]` section of your `server.conf`. Use the
same value as for the `<Location ...>` in the Apache configuration. So if you
use the example from above, make sure you have `basePath = /webrtc` in your
Spreed WebRTC `server.conf`. Restart Spreed WebRTC after the change. Also
make sure to use the same port in the `ProxyPass` lines as they are set
in the `[http]` `listen` setting of `server.conf`.

## Done, now testing

Make sure to reload Apache. Spreed WebRTC should become available at
https://yourserver/webrtc and is ready to be used from the Nextcloud plugin.
Note: Your browser might cache redirects, so we recommend opening the URL in
an anonymous browser window.

That's it.

--
(c)2016 struktur AG
