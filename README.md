# Spreed.ME Nextcloud app

- This app can only be used together with a [Spreed WebRTC server](https://github.com/strukturag/spreed-webrtc) which is available as open source software (AGPL license) – just like Nextcloud.
- This app requires HTTPS.
- The latest version of this app can be found in the Nextcloud app store or at [github.com/strukturag/nextcloud-spreedme](https://github.com/strukturag/nextcloud-spreedme).

## Features

- Securely communicate with your friends and family using rich audio-, video- and text chat right from your Nextcloud installation – in your browser
- Share presentations and other documents and save them to your Nextcloud
- Share your Nextcloud presentations and other documents
- Share your computer screen
- Share YouTube videos
- ...

## Installation / Setup of this app

This set of installation steps assume that you already have set up Nextcloud using a web server like Nginx or Apache.  
Your server has to be available via HTTPS. If your Nextcloud server is not using SSL/TLS yet, you need to [enable SSL now](https://docs.nextcloud.com/server/9/admin_manual/installation/source_installation.html#enabling-ssl-label).

1. Place this app in the **apps/** folder of your Nextcloud installation. Make sure the directory of this app is named `spreedme`.
2. Set up a Spreed WebRTC server and continue with the next step.
   An easy-to-follow installation guideline can be found further below, see [Installation / Setup of a Spreed WebRTC server](#installation--setup-of-a-spreed-webrtc-server).
3. You now should have a running Spreed WebRTC server.
4. Create a new folder **extra.d** in the root directory of Spreed WebRTC (e.g. `mkdir /absolute/path/to/spreed-webrtc/extra.d`). Now symlink the Nextcloud plugin into this folder, `ln -sf /absolute/path/to/nextcloud/apps/spreedme/extra /absolute/path/to/spreed-webrtc/extra.d/spreedme`
5. This app requires you to change some settings in the `server.conf` of the Spreed WebRTC server (`webrtc.conf` if you use the packaged version), namely:
   1. In the **[http]** section:
        Enable (= uncomment) **basePath** and set it to **/webrtc/**
        (`basePath = /webrtc/`)
   2. In the **[app]** section:
      - Enable **authorizeRoomJoin** and set it to **true**
        (`authorizeRoomJoin = true`)
      - Enable **extra.d** and set it to the full absolute path to Spreed WebRTC's **extra.d** directory (the directory you created in step 4.)
        (e.g. `extra.d = /absolute/path/to/spreed-webrtc/extra.d`)
   3. In the **[users]** section:
      - Enable **enabled** and set it to **true**
        (`enabled = true`)
      - Enable **mode** and set it to **sharedsecret**
        (`mode = sharedsecret`)
      - Enable **sharedsecret_secret** and set it to a random 64-character HEX string
        **Please note:** Do **NOT** use the string given below. Generate your own random 64-character HEX string!
        You can generate your own 64-character HEX string by running `xxd -ps -l 32 -c 32 /dev/random` or `openssl rand -hex 32`
        (e.g. `sharedsecret_secret = bb04fb058e2d7fd19c5bdaa129e7883195f73a9c49414a7eXXXXXXXXXXXXXXXX`)
   4. Restart the Spreed WebRTC server to reload its configuration
6. Head over to the **apps/spreedme/config** folder in your Nextcloud installation. Copy `config.php.in` to `config.php` and adjust the constants as already done in `server.conf`:
   1. Set `SPREED_WEBRTC_SHAREDSECRET` to the same **sharedsecret_secret** you set in the `server.conf` file before
7. Head over to the **apps/spreedme/extra/static/config** folder in your Nextcloud installation. Copy `OwnCloudConfig.js.in` to `OwnCloudConfig.js`.
8. Enable this Nextcloud app by browsing to **/index.php/settings/apps**
9. **That's it.** You can now start communicating securely with your friends and family by opening the **Spreed.ME app** of your Nextcloud host in your browser.

## Limiting access to this app

- By default, all users who can log in to your Nextcloud installation can also use this app (and Spreed WebRTC).
- If you want to limit access to this app (and Spreed WebRTC) only to a selected user-group, open Nextcloud's user configuration site in your browser: **/index.php/settings/users**.
- Create a new group there (e.g. `Spreed.ME`). For sure you can also use an existing group (like `admin`).
- Now go to Nextcloud's app configuration page **/index.php/settings/apps**, find this app and check **Enable only for specific groups**. Then add all groups which should be able to use this app.
- All users not in a group specified in the step above, will neither be able to use this app nor Spreed WebRTC.

## Access by non-Nextcloud users

- If you want to use Spreed WebRTC with users who do not have an Nextcloud account, you can enable the "Temporary Password" feature in `config/config.php`.
- This allows them to use Spreed WebRTC with a "Temporary Password", which admins can generate by clicking on the key icon in the room bar of Spreed WebRTC or at **/index.php/apps/spreedme/admin/tp**. Admins are either Nextcloud admins or Spreed.ME group admins. Create a group named `Spreed.ME` and add users as a group admin for that group to allow them to generate "Temporary Passwords".

## Upgrading this app

1. Save the `config/config.php` and the `extra/static/config/OwnCloudConfig.js` file of your current **spreedme** apps directory.
2. Download a newer version of this app.
3. Replace the old folder with the new folder you just downloaded.
4. Copy back the two files (step 1) to the appropriate folders.
5. Consult CHANGELOG.md for changes you might need to follow.

## Installation / Setup of a Spreed WebRTC server

Minimum [Spreed WebRTC](https://github.com/strukturag/spreed-webrtc) version: **0.27.0**.

### Ubuntu installation with packages

If your Nextcloud runs on Ubuntu this is the way to go. See [Ubuntu Repository installation instructions](https://github.com/strukturag/spreed-webrtc/wiki/Ubuntu-Repository) on the Spreed WebRTC wiki.

tl;dr:
```sh
sudo apt-add-repository ppa:strukturag/spreed-webrtc-unstable
sudo apt-get update
sudo apt-get install spreed-webrtc
```

### Installation with Docker

If you have Docker it is also a one-liner to install Spreed WebRTC (amd64 architecture required). See [Spreed WebRTC Docker](https://hub.docker.com/r/spreed/webrtc/) on Dockerhub.

Additional Spreed WebRTC custom configuration example (spreed-webrtc-nextcloud.conf):
```
[http]
basePath = /webrtc/

[app]
authorizeRoomJoin = true
extra.d = /srv/extra/extra.d

[users]
enabled = true
mode = sharedsecret
```

tl;dr:
```sh
docker run --name my-spreed-webrtc -p 8080:8080 -p 8443:8443 \
       -v `pwd`:/srv/extra -i -t spreed/webrtc -c /srv/extra/spreed-webrtc-nextcloud.conf
```

This assumes you have stored the additional configuration `spreed-webrtc-nextcloud.conf` in the current directory and also have created the `extra.d` folder there. For real world use, it would be wise to replace `pwd` with the absolute folder where you have put this stuff.

The Docker container also automatically creates all the required secrets for you on first start. They are printed in the console / log for you. The `SHARED_SECRET` line shows the value which needs to be configured in the Spreed.ME Nextcloud app as `SPREED_WEBRTC_SHAREDSECRET` value.

### Installation from source

Of course you can always install Spreed WebRTC from source. Head over to [github.com/strukturag/spreed-webrtc](https://github.com/strukturag/spreed-webrtc) and follow the instructions to install the Spreed WebRTC server.

## Add Spreed WebRTC to your Nextcloud web server

You need to adjust your existing web server configuration to make Spreed WebRTC available in a subpath. Follow our guides for [Nginx](./doc/example-config-nginx.md) or [Apache](./doc/example-config-apache.md) on how to run Spreed WebRTC on the same host in a subpath.  
**Don't forget** to set your own secrets in the **[app]** section of Spreed WebRTC's config file. At the very least you should change **sessionSecret** and **encryptionSecret**.

## Debugging / Problems?

If you're having trouble getting this app and Spreed WebRTC server to run, simply open **/index.php/apps/spreedme/admin/debug** of your Nextcloud host in your browser. It might contain information which can help to debug the issue.

To get help and issues please use the issue tracker at [github.com/strukturag/nextcloud-spreedme/issues](https://github.com/strukturag/nextcloud-spreedme/issues).
