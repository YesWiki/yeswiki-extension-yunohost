# YunoHost YesWiki extension

Permits the use of Yunohost SSO in YesWiki, and provides importers for displaying yunohost apps as bazar entries.

For now, this extension must be installed on a YesWiki hosted locally on a YunoHost system.

## Installation

This plugin needs two things to be set-up:

- `"enable_yunohost_sso" => true,` in the config file `wakka.config.php`
- add a no password sudo rule **only for the tools/yunohost/private/scripts/yunohost-user-list.sh** in `/etc/sudoers.d/<user>`

```
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-user-info.sh
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-app-list.sh
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-user-list.sh
```

`<user>` being the system username running the php script.