---
layout: default
title: Init A Symfony Project With Propel As Default ORM - The Git Way
---

# Init A Symfony Project With Propel As Default ORM - The Git Way #

Since this summer (2011) `Propel` ORM has a new `symfony` integration plugin `sfPropelORMPlugin` replacing the old one `sfPropel15Plugin`.

The old `sfPropel15Plugin` caused [some confusion at each new Propel's version](http://propel.posterous.com/sfpropel16plugin-is-already-there-didnt-you-k).
Now `sfPropelORMPlugin` will always integrate the last `Propel`'s version to `Symfony 1.4`.

You'll learn how to set up a new `symfony 1.4` project with all necessary libraries as git submodules.

First, set up a new project:

{% highlight bash %}
mkdir propel_project
cd propel_project
git init
{% endhighlight %}

Install `symfony 1.4` as a git submodule:

{% highlight bash %}
git submodule add git://github.com/vjousse/symfony-1.4.git lib/vendor
{% endhighlight %}

Generate a symfony project:

{% highlight bash %}
php lib/vendor/data/bin/symfony generate:project propel
{% endhighlight %}

Add the `sfPropelORMPlugin` plugin:

{% highlight bash %}
git submodule add git://github.com/propelorm/sfPropelORMPlugin plugins/sfPropelORMPlugin
{% endhighlight %}

Get Propel and Phing bundled with the plugin:

{% highlight bash %}
cd plugins/sfPropelORMPlugin
git submodule update --init
{% endhighlight %}

You should add a `.gitignore` file with the following content:

{% highlight bash %}
config/databases.yml
cache/*
log/*
data/sql/*
lib/filter/base/*
lib/form/base/*
lib/model/map/*
lib/model/om/*
{% endhighlight %}

Now, enable `sfPropelORMPlugin` in `config/ProjectConfiguration.class.php`:

{% highlight php %}
<?php

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
     $this->enablePlugins('sfPropelORMPlugin');
  }
}
{% endhighlight %}

Publish assets:

{% highlight bash %}
php symfony plugin:publish-assets
{% endhighlight %}

Copy the `propel.ini` default file in your project:

{% highlight bash %}
cp plugins/sfPropelORMPlugin/config/skeleton/config/propel.ini config/propel.ini
{% endhighlight %}

Verify behaviors lines look like:

{% highlight ini %}
// config/propel.ini

propel.behavior.symfony.class                  = plugins.sfPropelORMPlugin.lib.behavior.SfPropelBehaviorSymfony
propel.behavior.symfony_i18n.class             = plugins.sfPropelORMPlugin.lib.behavior.SfPropelBehaviorI18n
propel.behavior.symfony_i18n_translation.class = plugins.sfPropelORMPlugin.lib.behavior.SfPropelBehaviorI18nTranslation
propel.behavior.symfony_behaviors.class        = plugins.sfPropelORMPlugin.lib.behavior.SfPropelBehaviorSymfonyBehaviors
propel.behavior.symfony_timestampable.class    = plugins.sfPropelORMPlugin.lib.behavior.SfPropelBehaviorTimestampable
{% endhighlight %}
                 
Adapt your `databases.yml` or copy the model in your project:

{% highlight bash %}
cp plugins/sfPropelORMPlugin/config/skeleton/config/databases.yml config/databases.yml
{% endhighlight %}

It has to look like this:

{% highlight yaml %}
# You can find more information about this file on the symfony website:
# http://www.symfony-project.org/reference/1_4/en/07-Databases

dev:
  propel:
    param:
      classname:  DebugPDO
      debug:
        realmemoryusage: true
        details:
          time:       { enabled: true }
          slow:       { enabled: true, threshold: 0.1 }
          mem:        { enabled: true }
          mempeak:    { enabled: true }
          memdelta:   { enabled: true }

test:
  propel:
    param:
      classname:  DebugPDO

all:
  propel:
    class:        sfPropelDatabase
    param:
      classname:  PropelPDO
      dsn:        mysql:dbname=test;host=localhost
      username:   root
      password:   
      encoding:   utf8
      persistent: true
      pooling:    true
{% endhighlight %}

>**Warning**<br/>If your PHP version is under 5.3.6 you won't be allowed to set the `encoding` parameter due to a security issue in PHP.

You're now ready for writing a `schema.xml` and building your project.