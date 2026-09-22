# Extension yunohost

Utilise le SSO YunoHost comme fournisseur d'identité de YesWiki, et importe les
applications yunohost sous forme de fiches bazar.

Le wiki doit être hébergé sur le système YunoHost lui-même : l'extension appelle la
commande `yunohost` en local.

## Installation

Deux réglages sont nécessaires.

Dans `wakka.config.php` :

```php
'enable_yunohost_sso' => true,
```

Puis une règle sudo sans mot de passe, limitée aux trois scripts de l'extension, dans
`/etc/sudoers.d/<utilisateur>` :

```
<user> ALL = (root) NOPASSWD: /home/<user>/chemin/vers/yeswiki/tools/yunohost/private/scripts/yunohost-user-info.sh
<user> ALL = (root) NOPASSWD: /home/<user>/chemin/vers/yeswiki/tools/yunohost/private/scripts/yunohost-app-list.sh
<user> ALL = (root) NOPASSWD: /home/<user>/chemin/vers/yeswiki/tools/yunohost/private/scripts/yunohost-user-list.sh
```

`<user>` est le compte système qui exécute PHP.

Limiter la règle à ces trois scripts, et non à `/usr/bin/yunohost` en entier : la
différence décide de ce qu'un défaut de l'extension permettrait de faire en root.

## Ce que fournit l'extension

| Élément | Rôle |
|---|---|
| champ `YunohostUserField` | crée un compte YunoHost depuis une fiche bazar |
| importeur d'applications | affiche les applications yunohost comme fiches |
| connexion SSO | remplace le formulaire de connexion par celui de YunoHost |

## Comment les commandes sont lancées

`fields/YunohostUserField.php` appelle `yunohost` par `proc_open` avec un tableau
d'arguments, donc sans passer par un shell. Ce que quelqu'un saisit dans le formulaire
arrive à la commande comme un argument littéral, quoi qu'il contienne.

Les commandes sont lancées de façon synchrone et leur code de retour est lu : une
création refusée par YunoHost, par exemple pour un mot de passe trop court ou trop
courant, remonte comme un message à la personne qui a rempli le formulaire.
