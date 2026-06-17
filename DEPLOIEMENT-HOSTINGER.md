# Déploiement sur Hostinger

Guide complet pour migrer le site WordPress local (Docker) vers un hébergement Hostinger.

---

## Vue d'ensemble

```
Local (Docker)                          Hostinger
──────────────────────────────          ─────────────────────────────
localhost:8080                    →     tondomaine.com
MySQL : exampledb                 →     MySQL Hostinger (hPanel)
wp-content/ (thème + plugin)      →     FTP / File Manager
```

---

## Étape 1 — Exporter la base de données locale

Ouvre un terminal dans le dossier du projet.

```bash
# Dump complet de la base de données locale
docker compose exec db mysqldump \
  -u exampleuser -pexamplepass exampledb \
  > export_local.sql
```

Le fichier `export_local.sql` contient tout : articles, réservations, réglages, menus, options du Customizer.

---

## Étape 2 — Préparer les fichiers WordPress

Le thème et le plugin sont en volume Docker dans `wp-content/`.  
Les **uploads** (images ajoutées via l'admin) sont dans le volume Docker `wordpress` — il faut les extraire.

```bash
# Copier les uploads depuis le container vers le dossier local
docker compose cp wordpress:/var/www/html/wp-content/uploads ./wp-content/uploads
```

Tu auras maintenant dans `wp-content/` :
```
wp-content/
├── themes/
│   └── restaurant-theme/       ← ton thème
├── plugins/
│   └── restaurant-reservation/ ← ton plugin
├── mu-plugins/                 ← mu-plugins (SMTP local, à adapter)
└── uploads/                    ← images du site
```

> **Important** : le fichier `mu-plugins/smtp-mailpit.php` configure le SMTP vers Mailpit (local uniquement). Il faudra le supprimer ou le remplacer côté Hostinger.

---

## Étape 3 — Créer l'hébergement sur Hostinger

1. Connecte-toi à **hPanel** → [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Va dans **Hébergement** → sélectionne ton plan
3. Note les informations de connexion :
   - **FTP** : hôte, nom d'utilisateur, mot de passe (dans *Fichiers > Comptes FTP*)
   - **Base de données** : sera créée à l'étape suivante

---

## Étape 4 — Créer la base de données MySQL sur Hostinger

Dans hPanel → **Bases de données** → **MySQL** :

1. Clique **Créer une nouvelle base de données**
2. Note les informations générées :
   - **Nom de la base** : ex. `u123456789_nanou`
   - **Utilisateur** : ex. `u123456789_nanou`
   - **Mot de passe** : celui que tu choisis
   - **Hôte** : `localhost` (toujours sur Hostinger)

---

## Étape 5 — Importer la base de données

Dans hPanel → **Bases de données** → **phpMyAdmin** :

1. Sélectionne ta base de données dans la colonne gauche
2. Clique sur l'onglet **Importer**
3. Choisis le fichier `export_local.sql`
4. Clique **Exécuter**

---

## Étape 6 — Remplacer les URLs dans la base de données

La base exportée contient encore `http://localhost:8080` partout (options WP, contenu, métadonnées). Il faut remplacer par le vrai domaine.

Dans phpMyAdmin, clique sur l'onglet **SQL** et exécute :

```sql
-- Remplace localhost:8080 par ton vrai domaine (sans slash final)
UPDATE wp_options
SET option_value = REPLACE(option_value, 'http://localhost:8080', 'https://tondomaine.com')
WHERE option_name IN ('siteurl', 'home');

-- Remplace dans tout le contenu (articles, pages, métadonnées)
UPDATE wp_posts
SET post_content = REPLACE(post_content, 'http://localhost:8080', 'https://tondomaine.com');

UPDATE wp_posts
SET guid = REPLACE(guid, 'http://localhost:8080', 'https://tondomaine.com');

UPDATE wp_postmeta
SET meta_value = REPLACE(meta_value, 'http://localhost:8080', 'https://tondomaine.com');
```

> Si ton domaine n'est pas encore en HTTPS, utilise `http://` pour l'instant et change après activation du certificat SSL.

---

## Étape 7 — Uploader les fichiers via FTP

Utilise **FileZilla** (gratuit) ou le **File Manager** de hPanel.

### Avec FileZilla

- Hôte : celui fourni par Hostinger
- Identifiant / Mot de passe : credentials FTP de hPanel
- Port : `21`

### Ce qu'il faut uploader dans `public_html/`

Si WordPress n'est pas encore installé sur Hostinger, installe-le d'abord via hPanel → **WordPress** → **Installer**.

Ensuite, remplace uniquement le dossier `wp-content/` :

```
public_html/
└── wp-content/
    ├── themes/
    │   └── restaurant-theme/       ← uploader
    ├── plugins/
    │   └── restaurant-reservation/ ← uploader
    └── uploads/                    ← uploader
```

> Ne touche pas aux fichiers WordPress core (`wp-admin/`, `wp-includes/`, `wp-login.php`, etc.).  
> Ne remplace pas le `wp-config.php` de Hostinger — il contient déjà les bons identifiants de base de données.

---

## Étape 8 — Vérifier wp-config.php sur Hostinger

Le `wp-config.php` généré par Hostinger devrait déjà avoir les bonnes valeurs. Vérifie via le File Manager de hPanel que ces lignes correspondent à ce que tu as créé à l'étape 4 :

```php
define( 'DB_NAME',     'u123456789_nanou' );
define( 'DB_USER',     'u123456789_nanou' );
define( 'DB_PASSWORD', 'ton_mot_de_passe' );
define( 'DB_HOST',     'localhost' );
```

---

## Étape 9 — Configurer l'email (SMTP)

En local, les emails partaient vers **Mailpit**. Sur Hostinger, tu dois configurer un vrai SMTP.

### Option A — Utiliser l'email Hostinger (recommandé)

1. hPanel → **Emails** → **Comptes email** → crée `contact@tondomaine.com`
2. Dans WordPress : installe le plugin **WP Mail SMTP** ou **FluentSMTP**
3. Configure avec les paramètres SMTP Hostinger :
   - Hôte : `smtp.hostinger.com`
   - Port : `587` (TLS) ou `465` (SSL)
   - Identifiant : `contact@tondomaine.com`
   - Mot de passe : celui du compte email

### Option B — Supprimer le mu-plugin local

Supprime ou vide le fichier `public_html/wp-content/mu-plugins/smtp-mailpit.php` sur Hostinger pour que WordPress utilise sa configuration email par défaut (PHP mail).

---

## Étape 10 — Activer le SSL (HTTPS)

1. hPanel → **SSL** → **Certificat SSL** → active **Let's Encrypt** (gratuit)
2. Active la **Redirection HTTPS** (hPanel → **SSL** → toggle)
3. Dans WordPress Admin → **Réglages** → **Général** : vérifie que les URLs commencent par `https://`

---

## Étape 11 — Vérifications finales

| Vérification | Où |
|---|---|
| Site accessible sur le domaine | Navigateur |
| Admin accessible sur `/wp-admin/` | Navigateur |
| Thème et plugin actifs | WP Admin → Apparence / Extensions |
| Images affichées correctement | Frontend |
| Formulaire de réservation fonctionnel | Page frontend |
| Email de confirmation reçu | Boîte mail cliente (test) |
| Email de notification admin reçu | `nanoulounge@outlook.com` |
| Customizer → réglages conservés | Apparence → Personnaliser |

---

## Récapitulatif des commandes

```bash
# 1. Exporter la base de données
docker compose exec db mysqldump -u exampleuser -pexamplepass exampledb > export_local.sql

# 2. Récupérer les uploads
docker compose cp wordpress:/var/www/html/wp-content/uploads ./wp-content/uploads
```

Ensuite tout se fait via hPanel (interface web Hostinger) et FileZilla.

---

## En cas de problème

| Symptôme | Cause probable | Solution |
|---|---|---|
| Page blanche | Erreur PHP | Activer `WP_DEBUG` dans wp-config.php, vérifier les logs |
| "Erreur de connexion à la base de données" | Mauvais identifiants DB | Vérifier wp-config.php |
| Images manquantes | Uploads non uploadés | Uploader `wp-content/uploads/` |
| URLs en localhost dans le contenu | Search/replace incomplet | Relancer les UPDATE SQL de l'étape 6 |
| Emails non reçus | SMTP Mailpit encore actif | Supprimer/vider `mu-plugins/smtp-mailpit.php` |
| Boucle de redirection | URLs mixtes http/https | Harmoniser siteurl et home dans wp_options |
