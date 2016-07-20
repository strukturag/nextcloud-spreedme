# Spreed.ME ownCloud app
- This app can only be used in conjunction with a [Spreed WebRTC server](https://github.com/strukturag/spreed-webrtc).  
  You will not be able to use this app without such a server, but it's **easy to set up** one – The whole process only takes about 5-10 minutes – 2 minutes if you are fast ;).
- The latest version of this app can be found in the ownCloud app store or at [github.com/strukturag/owncloud-spreedme](https://github.com/strukturag/owncloud-spreedme)

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
   (e.g. access ownCloud at https://myowncloudserver.com:8443 and Spreed WebRTC at https://webrtc.myowncloudserver.com:8080) **-->** Then use an empty / no **basePath** for the next steps.

**Note:** We recommend using option **1.** as it's much easier to maintain. Also it seems that you can't use Screensharing in latest browsers when using option **2.**. We're working on a workaround so Screensharing also works with option **2.**, but for now option **1.** seems the way to go.

## Installation / Setup of this app
First off, here's a little encouragement:
This list of steps might seem a bit long, but it's really easy to follow – we promise. You only have to set up this app once :)

1. Place this app in the **apps/** folder of your ownCloud installation.
2. Set up a Spreed WebRTC server and continue with the next step.
   An easy-to-follow installation guideline can be found further below, see **Installation / Setup of a Spreed WebRTC server**
3. You now should have a running Spreed WebRTC server.
4. This app requires you to change some settings in the `server.conf` of the Spreed WebRTC server (`webrtc.conf` if you use the packaged version), namely:
   1. In the **[http]** section:
      - **ONLY do this step if your basePath is not empty (see Preparations above):**  
        Enable (= uncomment) **basePath** and set it to the basePath you determined above, e.g. **/webrtc/**  
        (e.g. `basePath = /webrtc/`)
   2. In the **[app]** section:
      - Enable **authorizeRoomJoin** and set it to **true**
        (`authorizeRoomJoin = true`)
      - Enable **extra** and set it to the full absolute path of the **spreedme/extra** directory in your **apps** folder of your ownCloud installation  
        (e.g. `extra = /absolute/path/to/owncloud/apps/spreedme/extra`)

      In case your Owncloud installation resides in a different origin than Spreed WebRTC, you will need to copy the entire **apps/spreedme/extra** directory from your Owncloud host to Spreed WebRTC host (e.g. **/var/www/spreedme/extra** ), and set the **extra** parameter to this location. Remember to keep synced this copied directory with the original version in your Owncloud installation.
      - Enable **plugin** and set it to **extra/static/owncloud.js**  
        (`plugin = extra/static/owncloud.js`)
   3. In the **[users]** section:
      - Enable **enabled** and set it to **true**  
        (`enabled = true`)
      - Enable **mode** and set it to **sharedsecret**  
        (`mode = sharedsecret`)
      - Enable **sharedsecret_secret** and set it to a random 64-character HEX string  
        **Please note:** Do **NOT** use the string given below. Generate your own random 64-character HEX string!
        You can generate your own 64-character HEX string by running `xxd -ps -l 32 -c 32 /dev/random` or `openssl rand -hex 32`  
        (e.g. `sharedsecret_secret = bb04fb058e2d7fd19c5bdaa129e7883195f73a9c49414a7eXXXXXXXXXXXXXXXX`)
   4. Restart the Spreed WebRTC server for it to reload its configuration
5. Head over to the **apps/spreedme/config** folder in your ownCloud installation. Copy `config.php.in` to `config.php` and adjust the constants as already done in `server.conf`:
   1. Set `SPREED_WEBRTC_ORIGIN` to the origin of your WebRTC server  
      (e.g. `const SPREED_WEBRTC_ORIGIN = 'https://webrtc.myowncloudserver.com:8443';`)  
      **NOTE:** If you chose option **1.** in the Preparations step above, you can set `SPREED_WEBRTC_ORIGIN` to an empty string: `const SPREED_WEBRTC_ORIGIN = '';`
   2. Set `SPREED_WEBRTC_BASEPATH` to the same **basePath** you already set in the `server.conf` file
   3. Set `SPREED_WEBRTC_SHAREDSECRET` to the same **sharedsecret_secret** you already set in the `server.conf` file
6. Head over to the **apps/spreedme/extra/static/config** folder in your ownCloud installation. Copy `OwnCloudConfig.js.in` to `OwnCloudConfig.js` and adjust the constants as already done in `server.conf` and `config.php`:
   1. Set `OWNCLOUD_ORIGIN` to the origin of your ownCloud server  
      (e.g. `OWNCLOUD_ORIGIN: 'https://myowncloudserver.com:8443'`)  
      **NOTE:** If you chose option **1.** in the Preparations step above, you can set `OWNCLOUD_ORIGIN` to an empty string: `OWNCLOUD_ORIGIN: ''`
7. Enable this ownCloud app by browsing to **/index.php/settings/apps**
8. **That's it.** You can now start communicating securely with your friends and family by opening **/index.php/apps/spreedme** of your ownCloud host in your browser.  
   For debugging, simply append `?debug` to that URL.

## Limiting access to this app
- By default, all users who can log in to your ownCloud installation can also use this app (and spreed-webrtc)
- If you want to limit access to this app (and spreed-webrtc) only to a selected user-group, open ownCloud's user configuration site in your browser: **/index.php/settings/users**
- Create a new group there (e.g. "spreed.me"). For sure you can also use an existing group (like "admin")
- Now go to ownCloud's app configuration page **/index.php/settings/apps**, find this app and check **Enable only for specific groups**. Then add all groups which should be able to use this app.
- All users not in a group specified in the step above, will neither be able to use this app nor spreed-webrtc.

## Access by non-ownCloud users
- If you want to use spreed-webrtc with users who do not have an ownCloud account, you can enable the "Temporary Password" feature in `config/config.php`
- This allows them to use spreed-webrtc with a "Temporary Password", which admins **[1]** can generate by clicking on the key icon in the room bar of spreed-webrtc or at **/index.php/apps/spreedme/admin/tp**  
  **[1]**: Admins are either ownCloud admins or Spreed.ME group admins. Create a group "Spreed.ME" and add users as a group admin for that group to allow them to generate "Temporary Passwords"

## Upgrading this app
1. Save the `config/config.php` and the `extra/static/config/OwnCloudConfig.js` file of your current **spreedme** apps directory
2. Download a newer version of this app
3. Replace the old folder with the new folder you just downloaded
4. Copy back the two files (step 1) to the appropriate folders
5. Consult CHANGELOG.md for changes you might need to follow

## Debugging this app
- If you're having trouble getting this app and Spreed WebRTC server to run, simply open **/index.php/apps/spreedme/admin/debug** of your ownCloud host in your browser.  
  It might contain information which can help to debug the issue.

## Installation / Setup of a Spreed WebRTC server
1. Head over to [github.com/strukturag/spreed-webrtc](https://github.com/strukturag/spreed-webrtc) and follow the instructions to install the Spreed WebRTC server.  
   For a packaged version (preferred) see [github.com/strukturag/spreed-webrtc/wiki/Ubuntu-Repository](https://github.com/strukturag/spreed-webrtc/wiki/Ubuntu-Repository)  
   **Please note**: Your Spreed WebRTC server has to be _publicly_ accessible.  
   You certainly want to adjust your [nginx](https://github.com/strukturag/spreed-webrtc/blob/master/doc/NGINX.txt) / [Apache](https://github.com/strukturag/spreed-webrtc/blob/master/doc/APACHE.txt) configuration accordingly.  
   See `./doc/example-config-nginx.txt` for an example configuration on how to run ownCloud and spreed-webrtc on the same host.
2. **Don't forget** to set your own secrets in the **[app]** section of spreed-webrtc's config file. At the very least you should change **sessionSecret** and **encryptionSecret**.
