# How to run Spreed WebRTC with Nginx in subpath

To support Spreed WebRTC with an Nginx HTTP Server, you require at least Nginx
version 1.3.13 to be able to proxy Websocket protocol. All current distributions
should have this in their respositories.

Add the configuration section from below to your Nginx configuration, to make
available Spreed WebRTC below the `/webrtc` subpath. Note that Spreed WebRTC does
require HTTPS and thus it only make sense to use and TLS encrypted `server`.

## Nginx configuration sniplets

### http context

The following section, needs to go inside the `http` context of your Nginx
configuration. This enables Websocket proxy - make sure you have it only once.

```
	map $http_upgrade $connection_upgrade {
		default	upgrade;
		''		close;
	}
```

### server context

Put the following part into the `server` context of your Nginx configuration.

```
	# Spreed WebRTC
	location /webrtc {
		proxy_pass http://127.0.0.1:8080;
		proxy_http_version 1.1;
		proxy_set_header Upgrade $http_upgrade;
		proxy_set_header Connection $connection_upgrade;
		proxy_set_header X-Forwarded-Proto $scheme;
		proxy_set_header Host $http_host;
		proxy_set_header X-Real-IP $remote_addr;
		proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

		proxy_buffering				on;
		proxy_ignore_client_abort	off;
		proxy_redirect				off;
		proxy_connect_timeout		90;
		proxy_send_timeout			90;
		proxy_read_timeout			90;
		proxy_buffer_size			4k;
		proxy_buffers				4 32k;
		proxy_busy_buffers_size		64k;
		proxy_temp_file_write_size	64k;
		proxy_next_upstream			error timeout invalid_header http_502 http_503 http_504;
	}
```

## Spreed WebRTC subpath configuration

To let Spreed WebRTC know that it is running in a subpath, you need to set the
`basePath` configuration in the `[http]` section of your `server.conf`. Use the
same value as for the `<Location ...>` in the Apache configuration. So if you
use the example from above, make sure you have `basePath = /webrtc` in your
Spreed WebRTC `server.conf` and restart Spreed WebRTC after the change. Also
make sure you use the same ports for the `ProxyPass` lines in Apache as they are
set in the `server.conf` `[http]` `listen` setting.

## Done, now testing

Make sure to also reload the Apache HTTP server and the WebRTC server should
become available at https://yourserver/webrtc and is ready to be used from the
Nextcloud plugin.

That's it.

--
(c)2016 struktur AG
