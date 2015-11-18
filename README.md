# Spreed WebRTC ownCloud app
- This ownCloud app can only be used in conjunction with a [Spreed WebRTC server](https://github.com/strukturag/spreed-webrtc).
- You will not be able to use this app without such a server, but it's **easy to set** one – The whole process only takes about 5 minutes.

## Features
- Securely communicate with your friends and family using rich audio-, video- and text chat right from your ownCloud installation – in your browser
- Share presentations and other documents and save them to your ownCloud
- Share your ownCloud presentations and other documents
- Share your computer screen
- Share YouTube videos
- Much, much more :)

## Preparations
Before setting up this app (+ Spreed WebRTC) you need to ask yourself a few questions:

1. Do you want to run ownCloud and Spreed WebRTC on the same origin (i.e. on the same domain+port combination)?  
   (e.g. access ownCloud at https://myowncloudserver.com:8443 and Spreed WebRTC at https://myowncloudserver.com:8443/webrtc/) **-->** Then use **/webrtc/** as your **basePath** for the next steps.
2. Do you want to run ownCloud and Spreed WebRTC on two different origins?  
   (e.g. access ownCloud at https://myowncloudserver.com:8443 and Spreed WebRTC at https://webrtc.myowncloudserver.com:8443) **-->** Then use an empty / no **basePath** for the next steps.

## Installation / Setup of this app
1. Place this app in the **apps/** folder of your ownCloud installation.
2. Set up a Spreed WebRTC server and continue with the next step.
   An easy-to-follow installation guideline can be found further below, see **Installation / Setup of a Spreed WebRTC server**
3. You now should have a running Spreed WebRTC server.
4. This app requires you to change some settings in the `server.conf` of the Spreed WebRTC server, namely:
   1. In the **[http]** section:
      - **ONLY do this step if your basePath is not empty (see Preparations above):**  
        Enable (= uncomment) **basePath** and set it to the basePath you determined above, e.g. **/webrtc/**  
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
        (`enabled = true`)
      - Enable **mode** and set it to **sharedsecret**  
        (`mode = sharedsecret`)
      - Enable **sharedsecret_secret** and set it to a random 64-character HEX string  
        **Please note:** Do **NOT** use the string given below. Generate your own random 64-character HEX string!
        You can generate your own 64-character HEX string by running `openssl rand -hex 32`
        (e.g. `sharedsecret_secret = bb04fb058e2d7fd19c5bdaa129e7883195f73a9c49414a7eXXXXXXXXXXXXXXXX`)  
   4. Restart the Spreed WebRTC server for it to reload its configuration
5. Head over to the **apps/spreedwebrtc/config** folder in your ownCloud installation. Copy `config.php.in` to `config.php` and adjust the constants as already done in `server.conf`:
   1. Set `SPREED_WEBRTC_ORIGIN` to the origin of your WebRTC server  
      (e.g. `const SPREED_WEBRTC_ORIGIN = 'https://webrtc.myowncloudserver.com:8443';`)
   2. Set `SPREED_WEBRTC_BASEPATH` to the same **basePath** you already set in the `server.conf` file
   3. Set `SPREED_WEBRTC_SHAREDSECRET` to the same **sharedsecret_secret** you already set in the `server.conf` file
6. Head over to the **apps/spreedwebrtc/extra/static/config** folder in your ownCloud installation. Copy `OwnCloudConfig.js.in` to `OwnCloudConfig.js` and adjust the constants as already done in `server.conf` and `config.php`:
   1. Set `OWNCLOUD_ORIGIN` to the origin of your ownCloud server  
      (e.g. `OWNCLOUD_ORIGIN: 'https://myowncloudserver.com:8443`)
7. Enable this ownCloud app by browsing to **/index.php/settings/apps**
8. **That's it.** You can now start communicating securely with your friends and family by opening **/index.php/apps/spreedwebrtc** of your ownCloud host in your browser.  
   For debugging, simply append `?debug` to that URL.

## Upgrading this app
1. Save the `config/config.php` and the `extra/static/config/OwnCloudConfig.js` file of your current **spreedwebrtc** apps directory
2. Download a newer version of this app
3. Replace the old folder with the new folder you just downloaded
4. Copy back the two files (step 1) to the appropriate folders
5. Consult CHANGELOG.md for changes you might need to follow

## Installation / Setup of a Spreed WebRTC server
1. Head over to [github.com/strukturag/spreed-webrtc](https://github.com/strukturag/spreed-webrtc) and follow the instructions to install the Spreed WebRTC server.  
   **Please note**: Your Spreed WebRTC server has to be _publicly_ accessible.  
   You certainly want to adjust your [nginx](https://github.com/strukturag/spreed-webrtc/blob/master/doc/NGINX.txt) / [Apache](https://github.com/strukturag/spreed-webrtc/blob/master/doc/APACHE.txt) configuration accordingly.
