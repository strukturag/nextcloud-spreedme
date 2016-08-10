# How to upgrade from a version pre-v0.2.0

If you want to upgrade this app from a version pre-v0.2.0 (e.g. v0.1.6, check
/index.php/apps/spreedme/admin/debug to see the version you currently have installed),
please follow these steps:

1. Upgrade [Spreed WebRTC](https://github.com/strukturag/spreed-webrtc) to a at least version 0.27.0.
2. Create a new folder **extra.d** in the root directory of Spreed WebRTC (e.g. `mkdir /absolute/path/to/spreed-webrtc/extra.d`). Now symlink the Nextcloud plugin into this folder (e.g. `ln -sf /absolute/path/to/owncloud/apps/spreedme/extra /absolute/path/to/spreed-webrtc/extra.d/spreedme`)
3. In Spreed WebRTC's `server.conf` (`webrtc.conf` if you use the packaged version):
   1. In the **[app]** section:
      1. Comment out **extra**
         (e.g. `; extra = /some/path/doesnt/matter`)
      2. Enable **extra.d** and set it to the full absolute path to Spreed WebRTC's **extra.d** directory (the directory you created in step 2.)
         (e.g. `extra.d = /absolute/path/to/spreed-webrtc/extra.d`)
3. That's it. Reload Spreed WebRTC now.
