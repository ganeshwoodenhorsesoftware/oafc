# Ontario Association of Fire Chiefs

Composer based Drupal 9 project. Runs natively (no Docker / Lando required).

## Requirements

- PHP 7.4–8.1 (match whatever version is currently in use — check with `php -v`)
- Composer
- MySQL or MariaDB
- Apache or Nginx (or PHP's built-in server for local development)
- Node.js + npm (for theme compilation)
- Apache Solr (see Search section below)

## Getting started

1. Copy `example.auth.json` to `auth.json`.
2. Add your GitLab [personal access token](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html#creating-a-personal-access-token) to authenticate with GitLab to be able to pull private packages.
3. Add your GitHub [personal access token](https://github.com/settings/tokens/new?scopes=repo&description=Composer) to authenticate with GitHub to be able to pull packages.
4. Install PHP dependencies:
   ```
   composer install
   ```
5. Create a local database and user:
   ```
   sudo mysql
   CREATE DATABASE oafc_drupal;
   CREATE USER 'oafc'@'localhost' IDENTIFIED BY 'your_chosen_password';
   GRANT ALL PRIVILEGES ON oafc_drupal.* TO 'oafc'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```
6. Configure database credentials in `web/sites/default/settings.php` (or `settings.local.php`):
   ```php
   $databases['default']['default'] = [
     'database' => 'oafc_drupal',
     'username' => 'oafc',
     'password' => 'your_chosen_password',
     'host' => 'localhost',
     'port' => '3306',
     'driver' => 'mysql',
     'prefix' => '',
   ];
   ```
7. Import an existing database dump (see Database import below), or install Drupal fresh:
   ```
   vendor/bin/drush site-install --existing-config
   ```
8. Start the site. For local development, PHP's built-in server is sufficient:
   ```
   cd web
   php -S localhost:8000
   ```
   For anything closer to staging/production, configure a proper Apache or Nginx virtual host with the document root pointed at `web/`, backed by PHP-FPM.

### Database import

```
mysql -u oafc -p oafc_drupal < oafc_[date].sql
```

If the dump is gzipped:
```
gunzip oafc_[date].sql.gz
mysql -u oafc -p oafc_drupal < oafc_[date].sql
```

## Local Tooling

- Check code standards:
  ```
  vendor/bin/phpcs
  ```
- Theme Tooling (run from the theme directory):
  - Install dependencies: `npm install`
  - Compile: `gulp`
  - Watch: `gulp watch`

## Workflow

Working with this project you should follow Drupal 8's configuration management best practices.

Drupal configuration gets exported to `\config\sync`.

Guidelines when starting work on the project:

```
$ git pull

# Import config.
$ vendor/bin/drush cim -y

# Run Drupal update to make sure everything is updated.
$ vendor/bin/drush updatedb
```

Guidelines when working/pushing to the project:

```
# Export config.
$ vendor/bin/drush cex

# Commit changes.
$ git commit -am "New site information config blah blah"

# Merging work from any other collaborators and resolve any merging conflicts.
$ git pull

# After merging always import config.
$ vendor/bin/drush cim --preview=diff

# Run Drupal update to make sure everything is updated.
$ vendor/bin/drush updatedb

# Push changes.
$ git push
```

## Site Configuration

There's some configuration that is set to be ignored since microsites create webforms and menus on each node creation.

This is accomplished using the [Config Ignore](https://www.drupal.org/project/config_ignore) and [Config Split](https://www.drupal.org/project/config_split) modules.

Configuration set to be ignored:
```
system.menu.course--*
system.menu.event--*
webform.webform.course__*
webform.webform.event__*
```

You can manage the settings at:

```
/admin/config/development/configuration/config-split
/admin/config/development/configuration/ignore
```

## Search (Apache Solr)

This project uses Search API with a Solr backend.

1. Download and run Solr locally (see Apache Solr documentation for the version matching this project's `search_api_solr` module requirements).
2. Create a core matching the project's expected core name:
   ```
   ./solr create -c oafc
   ```
3. In Drupal, go to `/admin/config/search/search-api/server/default/edit` and confirm the Solr connection settings:
   - Solr host: `localhost`
   - Solr port: `8983`
   - Solr path: `/`
   - Solr core: `oafc`
4. Generate the Drupal-specific Solr config:
   - From the server's View page, click **Get config.zip**.
   - Extract it and recreate the core using that config:
     ```
     ./solr delete -c oafc
     ./solr create -c oafc -d /path/to/extracted/config
     ```
5. Index content:
   ```
   vendor/bin/drush search-api:index
   ```

## Troubleshooting

You may need to create a new database user since database dumps can have the
wrong definer for views or stored procedures. See
https://stackoverflow.com/a/19707173. For example, locally there may be
no `oafc@localhost` user but the database dump from another server uses
that. That causes fatal errors when trying to create a contact. A work-around is
to create the user directly in MySQL:

```
$ sudo mysql
> GRANT ALL ON drupal.* TO 'oafc'@'localhost' IDENTIFIED BY 'foobar';
> FLUSH PRIVILEGES;
```

If the site returns a fatal error referencing a missing `vendor/autoload.php`,
the Composer dependencies are missing or incomplete. Reinstall them:
```
composer install
```

### Sass
- Configured to compile using Gulp.
- Compile to CSS by running the following commands:
```
$ npm install
$ gulp
```
- The CSS will be compiled to: `css/style.css`

## Composer Usage

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
You might need to replace `composer` with `php composer.phar` (or similar)
for your setup.

With `composer require ...` you can download new dependencies to your
installation.

```
composer require drupal/devel:~1.0
```

The `composer create-project` command passes ownership of all files to the
project that is created. You should create a new git repository, and commit
all files not excluded by the .gitignore file.

## What does the template do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `web/sites/default/files`-directory.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the
project [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold)
is used to ensure that your scaffold files are updated every time drupal/core is
updated. If you customize any of the "scaffolding" files (commonly .htaccess),
you may need to merge conflicts if any of your modified files are updated in a
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update drupal/core --with-dependencies` to update Drupal Core and its dependencies.
2. Run `git diff` to determine if any of the scaffolding files have changed.
   Review the files for any changes and restore any customizations to
   `.htaccess` or `robots.txt`.
3. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
4. In the event that there are non-trivial conflicts in step 2, you may wish
   to perform these steps on a branch, and use `git merge` to combine the
   updated core files with your customized files. This facilitates the use
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple;
   keeping all of your modifications at the beginning or end of the file is a
   good strategy to keep merges easy.

## Generate composer.json from existing project

With using [the "Composer Generate" drush extension](https://www.drupal.org/project/composer_generate)
you can now generate a basic `composer.json` file from an existing project. Note
that the generated `composer.json` might differ from this project's file.

## FAQ

### Should I commit the contrib modules I download?

Composer recommends **no**. They provide [argumentation against but also
workarounds if a project decides to do it anyway](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

### Should I commit the scaffolding files?

The [drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) plugin can download the scaffold files (like
index.php, update.php, …) to the web/ directory of your project. If you have not customized those files you could choose
to not check them into your version control system (e.g. git). If that is the case for your project it might be
convenient to automatically run the drupal-scaffold plugin after every install or update of your project. You can
achieve that by registering `@drupal-scaffold` as post-install and post-update command in your composer.json:

```json
"scripts": {
    "drupal-scaffold": "DrupalComposer\\DrupalScaffold\\Plugin::scaffold",
    "post-install-cmd": [
        "@drupal-scaffold",
        "..."
    ],
    "post-update-cmd": [
        "@drupal-scaffold",
        "..."
    ]
},
```

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL to patch"
        }
    }
}
```

### How do I switch from packagist.drupal-composer.org to packages.drupal.org?

Follow the instructions in the [documentation on drupal.org](https://www.drupal.org/docs/develop/using-composer/using-packagesdrupalorg).
