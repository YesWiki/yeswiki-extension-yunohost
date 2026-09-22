# yunohost extension

Uses the YunoHost SSO as YesWiki's identity provider, and imports yunohost apps as
bazar entries.

The wiki must be hosted on the YunoHost system itself: the extension calls the
`yunohost` command locally.

## Install

Two settings are needed.

In `wakka.config.php`:

```php
'enable_yunohost_sso' => true,
```

Then a passwordless sudo rule, limited to the extension's three scripts, in
`/etc/sudoers.d/<user>`:

```
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-user-info.sh
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-app-list.sh
<user> ALL = (root) NOPASSWD: /home/<user>/path/to/yeswiki/tools/yunohost/private/scripts/yunohost-user-list.sh
```

`<user>` is the system account running PHP.

Keep the rule limited to those three scripts rather than to all of `/usr/bin/yunohost`:
that difference decides what a flaw in the extension would let someone do as root.

## What the extension provides

| Item | Purpose |
|---|---|
| `YunohostUserField` | creates a YunoHost account from a bazar entry |
| app importer | shows yunohost apps as entries |
| SSO login | replaces the login form with YunoHost's |

## How the commands are run

`fields/YunohostUserField.php` calls `yunohost` through `proc_open` with an argument
array, so no shell is involved. Whatever someone types in the form reaches the command
as one literal argument, whatever it holds.

Commands run synchronously and their return code is read: a creation refused by
YunoHost, for instance for a password too short or too common, comes back as a message
to the person who filled the form in.
