---
layout: documentation
title: Installing Propel
---

# Installing Propel #

Propel is available as a [PEAR](http://pear.php.net/manual/en/installation.getting.php) package, as a clone from the official [Github repository](http://github.com/propelorm/Propel2), as a checkout from Subversion through Github and as a "traditional" [tgz](https://github.com/propelorm/Propel2/tarball/master) or [zip](https://github.com/propelorm/Propel/zipball/master) package. Whatever installation method you may choose, getting Propel to work is pretty straightforward.

## Prerequisites ##

Propel runs on most PHP platforms. It just requires:

* [PHP 5.3.2](http://www.php.net/) or newer, with the DOM (libxml2) module enabled
* A supported database (MySQL, MS SQL Server, PostgreSQL, SQLite, Oracle)

>**Tip**<br />Propel uses the PDO and SPL components, which are bundled and enabled by default in PHP5.

## Project-Local Installation ##

For a quick start, the best choice is to install Propel inside a project directory structure, typically under a `vendor/` subdirectory:

{% highlight bash %}
myproject/
  ...
  vendor/ <= This is where third-party libraries usually go
{% endhighlight %}

To install Propel there using Git, type:

{% highlight bash %}
cd myproject/vendor
git clone https://github.com/propelorm/Propel2.git propel
{% endhighlight %}

This will export the propel library to a local `myproject/vendor/propel/` directory.

Alternatively, to use a tarball, type the following commands on unix platforms:

{% highlight bash %}
cd myproject/vendor
wget http://files.propelorm.org/propel-2.0.0.tar.gz
tar zxvf propel-2.0.0.tar.gz
mv propel-2.0.0 propel
{% endhighlight %}

Or, in Windows, download a ZIP from [files.propelorm.org](http://files.propelorm.org), unzip it under the `vendor/` directory, and rename it to `propel`.

## Propel Directory Structure ##

The root directory of the Propel library includes the following folders:

|Folders        |Explanations
|---------------|----------------------------------------------------------------------
|bin            |Contains three scripts that manage propel command line tool (depending of your operating system)
|src            |The propel source code. Pass over if you just want to use Propel, not to contribute.
|tools          |Contains files that Propel uses to manage pear package and so on like DTD, XSD, etc.
|tests          |Propel unit tests. Ignore this if you don't want to contribute to Propel.

## Installing Dependencies ##

Propel uses the some Symfony2 components to work properly :

- [Console](https://github.com/symfony/Console) : which manage the generators propel uses.
- [Yaml](https://github.com/symfony/Yaml)
- [Validator](https://github.com/symfony/Validator) : a way yo manage validations with Propel.
- [Finder](https://github.com/symfony/Finder) : uses in the source code to manage the files.

To install these packages, we advise you to use Composer. Check out the [Composer's site](https://getcomposer.org) and then add the following to you composer.json file :

{% highlight json %}
{
    "require": {
        "symfony/yaml": ">=2.0",
        "symfony/console": ">=2.0",
        "monolog/monolog": ">=1.0.2",
        "symfony/finder": ">=2.0",
        "symfony/validator": ">=2.0"
    }
}
{% endhighlight %}

Then, to install all of the dependencies, run in a terminal :
{% highlight bash %}
php composer.phar install
{% endhighlight %}

_Note_ : The composer.phar file must be at the same directory level of the composer.json file.

## Testing Propel Installation ##

The Propel generator component bundles a `propel` sh script (and a `propel.bat` script for Windows). This script makes it easy to execute build commands. You can test this component is properly installed by calling the `propel` script from the CLI:

{% highlight bash %}
cd myproject
vendor/propel/bin/propel
{% endhighlight %}

The command should output the propel version following by a list of the options and the available commands. We will learn to use these commands later.

>**Tip**<br />In order to allow an easier execution of the script, you can also add the propel generator's `bin/` directory to your PATH, or create a symlink. For example:

{% highlight bash %}
cd myproject
ln -s vendor/propel/bin/propel propel
{% endhighlight %}

Or simply edit your .bashrc or .zshrc file : 

{% higlight bash %}
export PATH=$PATH:/path/to/propel/bin
{% endhighlight %}

At this point, Propel should be setup and ready to use. You can follow the steps in the [Build Guide](02-buildtime.html) to try it out.

## Alternative: Global Installation Using PEAR ##

Alternatively, you can install Propel globally on your system using PEAR. All your projects will use the same Propel version - that may or may not be a good idea, depending on how often you update your projects.

Propel has its own PEAR channel, that you must "discover". Using the `pear install -a` command, you can let PEAR download and install all dependencies (Phing and PEAR Log).

So the commands to install Propel, Phing and PEAR Log globally sum up to this:

{% highlight bash %}
pear channel-discover pear.propelorm.org
pear install -a propel/propel_generator
pear install -a propel/propel_runtime
{% endhighlight %}

Once Propel is installed globally, you can access the `propel-gen` command from everywhere without symlink.

>**Tip**<br />If you want to install non-stable versions of Propel, change your `preferred_state` PEAR environment variable before installing the Propel packages. Valid states include 'stable', 'beta', 'alpha', and 'devel':

{% highlight bash %}
pear config-set preferred_state beta
{% endhighlight %}

## Troubleshooting ##

### PHP Configuration ###

Propel requires the following settings in `php.ini`:

|Variable               |Value
|-----------------------|-----
|ze1_compatibility_mode |Off
|magic_quotes_gpc       |Off
|magic_quotes_sybase    |Off

### PEAR Directory In Include Path ###

If you choose to install Propel via PEAR, and if it's your first use of PEAR, the PEAR directory may not be on your PHP `include_path`. Check the [PEAR documentation](http://pear.php.net/manual/en/installation.checking.php) for details on how to do that.

### Getting Help ###

If you can't manage to install Propel, don't hesitate to ask for help. See [Support](../support) for details on getting help.
