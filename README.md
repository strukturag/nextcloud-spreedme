# Spreed WebRTC ownCloud app
- This ownCloud app can only be used in conjunction with a [Spreed WebRTC server](https://github.com/strukturag/spreed-webrtc).
- You will not be able to use this app without such a server, but it's **easy to set** one – Only takes about 5 minutes.

## Features
- When set up, you can securely communicate with your friends and family using rich audio-, video- and text chat, share presentations and other documents, share your computer screen, share YouTube videos and much more
- Only allow registered ownCloud users to use the WebRTC server
- Easily share your ownCloud presentations, photos, and other documents

## Preparations
Before setting up this app (+ Spreed WebRTC) you need to ask yourself a few questions:

1. Do you want to run ownCloud and Spreed WebRTC on the same origin (i.e. on the same domain+port combination)?  
   (e.g. access ownCloud at https://myowncloudserver.com:8443 and Spreed WebRTC at https://myowncloudserver.com:8443/webrtc/) **-->** Use **/webrtc/** as your **basePath** for the next steps.
2. Do you want to run ownCloud and Spreed WebRTC on two different origins?  
   (e.g. access ownCloud at https://myowncloudserver.com:8443 and Spreed WebRTC at https://webrtc.myowncloudserver.com:8443) **-->** Use an empty / no **basePath** for the next steps.

## Installation / Setup of this app
1. Place this app in the **apps/** folder of your ownCloud installation.
2. Set up a Spreed WebRTC server and continue with the next step.
   An easy-to-follow installation guideline can be found further below ("**Installation / Setup of a Spreed WebRTC server**").
3. You now should have a running Spreed WebRTC server.
4. This app requires you to change some settings in the `server.conf` of the Spreed WebRTC server, namely:
   1. In the **[http]** section:
      - **ONLY do this step when your basePath should not be empty (see Preparations above):**  
        Enable **basePath** and set it to e.g. **/webrtc/**  
        (e.g. `basePath = /webrtc/`)
   2. In the **[app]** section:
      - Enable **authorizeRoomJoin** and set it to **true**
        (`authorizeRoomJoin = true`)
      - Enable **extra** and set it to the full absolute path of the **spreedwebrtc/extra** directory in your **apps** folder of your ownCloud installation  
        (e.g. `extra = /absolute/path/to/owncloud/apps/spreedwebrtc/extra`)
      - Enable **plugin** and set it to **extra/static/owncloud.js**  
        (`plugin = extra/static/owncloud.js`)
   3. In the **[users]** section:
      - Enable **enabled** and set it to **true**  
        (``enabled = true``)
      - Enable **mode** and set it to **sharedsecret**  
        (`mode = sharedsecret`)
      - Enable **sharedsecret_secret** and set it to a random 64-character HEX string  
        **Please note:** Do **NOT** use the string given below. Generate your own random 64-character HEX string
        (e.g. `sharedsecret_secret = bb04fb058e2d7fd19c5bdaa129e7883195f73a9c49414a7eXXXXXXXXXXXXXXXX`)  
        You can generate your own 64-character HEX string by running `openssl rand -hex 32`
   4. Restart the Spreed WebRTC server for it to reload its configuration
5. Head over to the **apps/spreedwebrtc/config** folder in your ownCloud installation. Copy `config.php.in` to `config.php` and adjust the constants as already done in `server.conf`:
   1. Set `SPREED_WEBRTC_ORIGIN` to the origin of your WebRTC server  
      (e.g. `const SPREED_WEBRTC_ORIGIN = 'https://webrtc.myowncloudserver.com:8443';`)
   2. Set `SPREED_WEBRTC_BASEPATH` to the same **basePath** you already set in the `server.conf` file
   3. Set `SPREED_WEBRTC_SHAREDSECRET` to the same **sharedsecret_secret** you already set in the `server.conf` file
6. Head over to the **apps/spreedwebrtc/extra/static/config** folder in your ownCloud installation. Copy `OwnCloudConfig.js.in` to `OwnCloudConfig.js` and adjust the constants as already done in `server.conf` and `config.php`:
   1. Set `OWNCLOUD_ORIGIN` to the origin of your ownCloud server  
      (e.g. `OWNCLOUD_ORIGIN: 'https://myowncloudserver.com:8443`)
7. Enable this ownCloud app by browsing to **/index.php/settings/apps** with your browser
8. **That's it.** You can now start communicating securely with your friends and family by opening **/index.php/apps/spreedwebrtc** of your ownCloud host in your browser.  
   For debugging, simply append `?debug` to that URL.

## Installation / Setup of a Spreed WebRTC server
1. Head over to [github.com/strukturag/spreed-webrtc](https://github.com/strukturag/spreed-webrtc) and follow the instructions to install the Spreed WebRTC server.





## Publish to App Store

First get an account for the [App Store](http://apps.owncloud.com/) then run:

    make appstore_package

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.

## Running tests
After [Installing PHPUnit](http://phpunit.de/getting-started.html) run:

    phpunit -c phpunit.xml
