---
layout: documentation
title: Installing Propel
---

# Installing Propel #

Propel is available as a [PEAR](http://pear.php.net/manual/en/installation.getting.php) package, as a clone from the official [Github repository](http://github.com/propelorm/Propel), as a checkout from Subversion through Github and as a "traditional" [tgz](https://github.com/propelorm/Propel/tarball/master) or [zip](https://github.com/propelorm/Propel/zipball/master) package. Whatever installation method you may choose, getting Propel to work is pretty straightforward.

## Prerequisites ##

Propel runs on most PHP platforms. It just requires:

* [PHP 5.2.4](http://www.php.net/) or newer, with the DOM (libxml2) module enabled
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
git clone https://github.com/propelorm/Propel.git propel
{% endhighlight %}

This will export the propel library to a local `myproject/vendor/propel/` directory.

Alternatively, to use a tarball, type the following commands on unix platforms:

{% highlight bash %}
cd myproject/vendor
wget http://files.propelorm.org/propel-1.6.0.tar.gz
tar zxvf propel-1.6.0.tar.gz
mv propel-1.6.0 propel
{% endhighlight %}

Or, in Windows, download a ZIP from [files.propelorm.org](http://files.propelorm.org), unzip it under the `vendor/` directory, and rename it to `propel`.

## Propel Directory Structure ##

The root directory of the Propel library includes the following folders:

|Folders        |Explanations
|---------------|----------------------------------------------------------------------
|generator      |Contains the classes required to run Propel in the command line. Propel commands can build the object model, compile configuration files, execute migrations, etc.
|runtime        |Contains the classes required to access Propel models and the database. Typically, applications using a web server will only access the `runtime` directory and not the `generator`.
|tests          |Propel unit tests. Ignore this if you don't want to contribute to Propel.

Usually, both the generator and the runtime components are installed on development environments, while the actual test or production servers may need only the runtime component installed.

## Installing Dependencies ##

The Propel generator uses [Phing 2.4.5](http://phing.info/) to manage command line tasks; both the generator and the runtime classes use [PEAR Log](http://pear.php.net/package/Log/) to log events.

To install these packages, use the PEAR command as follows:

{% highlight bash %}
pear channel-discover pear.phing.info
pear install phing/phing
pear install Log
{% endhighlight %}

Refer to their respective websites for alternative installation strategies for Phing and PEAR Log.

## Testing Propel Installation ##

The Propel generator component bundles a `propel-gen` sh script (and a `propel-gen.bat` script for Windows). This script makes it easy to execute build commands. You can test this component is properly installed by calling the `propel-gen` script from the CLI:

{% highlight bash %}
cd myproject
vendor/propel/generator/bin/propel-gen
{% endhighlight %}

The script should output a welcome message, followed by a 'BUILD FAILED' message, which is normal - you haven't defined a model to build yet.

>**Tip**<br />In order to allow an easier execution the script, you can also add the propel generator's `bin/` directory to your PATH, or create a symlink. For example:

{% highlight bash %}
cd myproject
ln -s vendor/propel/generator/bin/propel-gen propel-gen
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

### Phing Version ###

Phing versions 2.4.3 and 2.4.4 are incompatible with Propel. Check your Phing version by calling:

{% highlight bash %}
phing -v
{% endhighlight %}

In case you're using a version less than 2.4.5, upgrade to the latest stable version:

{% highlight bash %}
pear upgrade phing/phing
{% endhighlight %}

### Getting Help ###

If you can't manage to install Propel, don't hesitate to ask for help. See [Support](../support) for details on getting help.
