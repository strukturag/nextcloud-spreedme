# Spreed WebRTC ownCloud app
- This ownCloud app can only be used in conjunction with a [Spreed WebRTC server](https://github.com/strukturag/spreed-webrtc).
- You will not be able to use this app without such a server, but it's **easy to set** one – Only takes about 5 minutes.

## Features
- When set up, you can securely communicate with your friends and family using rich audio-, video- and text chat, share presentations and other documents, share your computer screen, share YouTube videos and much more
- Only allow registered ownCloud users to use the WebRTC server
- Easily share your ownCloud presentations, photos, and other documents

## Installation / Setup of this app
1. Place this app in the **apps/** folder of your ownCloud installation.
2. Set up a Spreed WebRTC server and continue with the next step.
   An easy-to-follow installation guideline can be found further below ("**Installation / Setup of a Spreed WebRTC server**").
3. You now should have a running Spreed WebRTC server.
4. This app requires you to change some settings in the `server.conf` of the Spreed WebRTC server, namely:
   1. In the **[http]** section:
      - Enable **basePath** and adjust to e.g. **/webrtc/**  
        (e.g. `basePath = /webrtc/`)
   2. In the **[app]** section:
      - Enable **authorizeRoomJoin** and set it to **true**
        (`authorizeRoomJoin = true`)
      - Enable **extra** and set it to the full path of the **spreedwebrtc/extra** directory in your **apps** folder of your ownCloud installation  
        (e.g. `extra = /absolute/path/to/owncloud/apps/spreedwebrtc/extra`)
      - Enable **plugin** and set it to **extra/static/owncloud.js**  
        (`plugin = extra/static/owncloud.js`)
   3. In the **[users]** section:
      - Enable **enabled** and set it to **true**  
        (``enabled = true``)
      - Enable **mode** and set it to **sharedsecret**  
        (`mode = sharedsecret`)
      - Enable **sharedsecret_secret** and set it to a random 64-character HEX string.  
        **Please note:** Do **NOT** use the string given below. Generate your own random 64-character HEX string.  
        (e.g. `sharedsecret_secret = bb04fb058e2d7fd19c5bdaa129e7883195f73a9c49414a7eXXXXXXXXXXXXXXXX`)
   4. Restart the Spreed WebRTC server for it to reload its configuration
5. Head over to the **apps/spreedwebrtc/config** folder in your ownCloud installation. Copy `config.php.in` to `config.php` and adjust the constants **SPREED_WEBRTC_BASEPATH** and **SPREED_WEBRTC_SHAREDSECRET** as already done in `server.conf`.
6. **That's it.** You can now start communicating securely with your friends and family by opening **/index.php/apps/spreedwebrtc** in your browser.

## Installation / Setup of a Spreed WebRTC server
1. Head over to [github.com/strukturag/spreed-webrtc](https://github.com/strukturag/spreed-webrtc) and follow the instructions to install the Spreed WebRTC server.





## Publish to App Store

First get an account for the [App Store](http://apps.owncloud.com/) then run:

    make appstore_package

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.

## Running tests
After [Installing PHPUnit](http://phpunit.de/getting-started.html) run:

    phpunit -c phpunit.xml
