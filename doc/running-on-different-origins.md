# How to run Spreed WebRTC and Nextcloud on different origins

Running Spreed WebRTC and Nextcloud on different origins is not recommended,
as you will not be able to use the Screensharing feature due to browser restrictions.

If you still prefer to run them on different origins, please read on.

## Different origins?

»What do you mean by that? Does this affect me?«

An "origin" consists of the protocol (e.g. `https`), the domain name (e.g. `mynextcloudserver.com`),
and an optional port (e.g. `:8443`). It does _not_ include a path (e.g. `/nextcloud`).

Some examples:

- https://myowncloudserver.com (protocol + domain)
- https://webrtc.mynextcloudserver.com:8443 (protocol + domain + port)

So, if you want to run Spreed WebRTC on https://webrtc.mynextcloudserver.com:8443 and
Nextcloud on https://mynextcloudserver.com (notice the different domain and port), you are indeed affected.
Please read on.

## How to, please!

You need to make some adjustments to the configuration of this app.
If you have configured this app via the Nextcloud web interface (**/index.php/settings/admin#goto-spreed.me**),
please tweak the settings there. If you have configured this app via `config/config.php`, please make the adjustments there.

- Set `SPREED_WEBRTC_ORIGIN` to the origin of your Spreed WebRTC server
  (e.g. https://webrtc.mynextcloudserver.com:8443)

From your command line, head over to the **apps/spreedme/extra/static/config** folder of your Nextcloud installation.
Copy `OwnCloudConfig.js.in` to `OwnCloudConfig.js` and adjust the constants:

- Set `OWNCLOUD_ORIGIN` to the origin of your Nextcloud server  
  (e.g. `OWNCLOUD_ORIGIN: 'https://myowncloudserver.com'`)

That's it.

--
(c)2016 struktur AG
