---
layout: documentation
title: Installing Propel
---

# Installing Propel #

Propel is available as a [PEAR](http://pear.php.net/manual/en/installation.getting.php) package, as a clone from the official [Github repository](http://github.com/propelorm/Propel2), as a checkout from Subversion through Github and as a "traditional" [tgz](https://github.com/propelorm/Propel2/tarball/master) or [zip](https://github.com/propelorm/Propel2/zipball/master) package. Whatever installation method you may choose, getting Propel to work is pretty straightforward.

## Prerequisites ##

Propel just requires:

* [PHP 5.4](http://www.php.net/) or newer, with the DOM (libxml2) module enabled
* A supported database (MySQL, MS SQL Server, PostgreSQL, SQLite, Oracle)

Propel also uses some Symfony2 components to work properly:

* [Console](https://github.com/symfony/Console) : which manage the generators propel uses.
* [Yaml](https://github.com/symfony/Yaml)
* [Validator](https://github.com/symfony/Validator) : a way you manage validations with Propel.
* [Finder](https://github.com/symfony/Finder) : uses in the source code to manage the files.

>**Tip**<br />Propel uses the PDO and SPL components, which are bundled and enabled by default in PHP5.

## Setup ##

### Via Composer ###

We advise you to rely on [Composer](http://getcomposer.org/) to manage your projects' dependencies. If you want to install Propel via Composer, just create a new `composer.json` file at the root of your project's directory with the following content:

```json
{
    "require": {
        "propel/propel": ">= 2.0"
    }
}
```

Then you have to download Composer itself so in a terminal just type the following:

```bash
$ wget http://getcomposer.org/composer.phar
# If you haven't wget on your computer
$ curl -s http://getcomposer.org/installer | php
```

Finally, to install all your project's dependencies, type the following:

```bash
$ php composer.phar install
```

### Via Git ###

If you want, you can also setup Propel using Git cloning the Github repository:

```bash
$ git clone git://github.com/propelorm/Propel2 vendor/propel
```

Propel is well unit-tested so the cloned version should be pretty stable. If you want to update Propel, just go to the repository and pull the remote:

```bash
$ cd myproject/vendor/propel
$ git pull
```

### Global Installation Via PEAR ###

Alternatively, you can install Propel globally on your system using PEAR. All your projects will use the same Propel version - that may or may not be a good idea, depending on how often you update your projects.

Propel has its own PEAR channel, that you must "discover". Using the `pear install -a` command, you can let PEAR download and install all dependencies.

So the commands to install Propel, Phing and PEAR Log globally sum up to this:

```bash
$ pear channel-discover pear.propelorm.org
$ pear install -a propel/propel
```

Once Propel is installed globally, you can access the `propel` command from everywhere without symlink.

>**Tip**<br />If you want to install non-stable versions of Propel, change your `preferred_state` PEAR environment variable before installing the Propel packages. Valid states include 'stable', 'beta', 'alpha', and 'devel':

```bash
$ pear config-set preferred_state beta
```

#### PEAR Directory In Include Path ####

If you choose to install Propel via PEAR, and if it's your first use of PEAR, the PEAR directory may not be on your PHP `include_path`. Check the [PEAR documentation](http://pear.php.net/manual/en/installation.checking.php) for details on how to do that.

### Using a Tarball Or a Zipball ###

Alternatively, to use a tarball, type the following commands on unix platforms:

```bash
$ cd myproject/vendor
$ wget http://files.propelorm.org/propel-2.0.0.tar.gz
$ tar zxvf propel-2.0.0.tar.gz
$ mv propel-2.0.0 propel
```

Or, in Windows, download a ZIP from [files.propelorm.org](http://files.propelorm.org), unzip it under the `vendor/` directory, and rename it to `propel`.

## Propel Directory Structure ##

The root directory of the Propel library includes the following folders:

|Folders        |Explanations
|---------------|----------------------------------------------------------------------
|bin            |Contains three scripts that manage propel command line tool (depending of your operating system)
|documentation  |The Propel documentation source
|features       |Tests written with the Behat framework
|resources      |Contains some files such as the database XSD or DTD
|src            |The Propel source code. Pass over if you just want to use Propel, not to contribute.
|tests          |Propel unit tests. Ignore this if you don't want to contribute to Propel.

## Testing Propel Installation ##

The Propel generator component bundles a `propel` sh script (and a `propel.bat` script for Windows). This script makes it easy to execute build commands. You can test this component is properly installed by calling the `propel` script from the CLI:

```bash
$ cd myproject
$ vendor/propel/bin/propel
```

The command should output the propel version following by a list of the options and the available commands. We will learn to use these commands later.

>**Tip**<br />In order to allow an easier execution of the script, you can also add the propel generator's `bin/` directory to your PATH, or create a symlink. For example:

```bash
$ cd myproject
$ ln -s vendor/propel/bin/propel propel
```

Or simply edit your .bashrc or .zshrc file:

```bash
export PATH=$PATH:/path/to/vendor/bin/
```

At this point, Propel should be setup and ready to use. You can follow the steps in the [Build Guide](02-buildtime.html) to try it out.

## Troubleshooting ##

### PHP Configuration ###

Propel requires the following settings in `php.ini`:

|Variable               |Value
|-----------------------|-----
|ze1_compatibility_mode |Off
|magic_quotes_gpc       |Off
|magic_quotes_sybase    |Off

### Getting Help ###

If you can't manage to install Propel, don't hesitate to ask for help. See [Support](../support) for details on getting help.
