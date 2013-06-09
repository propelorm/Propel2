---
layout: default
title: Download Propel
---

# Download Propel #

For a full installation tutorial, check the [Installation documentation](documentation/01-installation). The following options allow you to download the Propel code and documentation.

## Composer ##

You can download Propel using Composer. First, download the `composer.phar` file using one of the following command depending on your system:

```bash
$ curl -sS https://getcomposer.org/installer | php
# Alternatively
$ wget http://getcomposer.org/composer.phar
```

Then touch a `composer.json` file with the following content

```json
{
    "require": {
        "propel/propel": ">= 2.0"
    }
}
```

Then simply run `php composer.phar install` to fetch Propel and its dependencies.

## Git ##

Clone it:

```bash
$ git clone git://github.com/propelorm/Propel2.git
```

Or add it as a submodule:

```bash
$ git submodule add git://github.com/propelorm/Propel2.git /path/to/propel
```

## Subversion Checkout / Externals ##

```bash
$ svn co http://svn.github.com/propelorm/Propel2.git
```

>**Warning**<br />SVN is no more the default Source Code Management since 2011.

## PEAR Installer ##

Propel is available through its own PEAR channel [pear.propelorm.org](pear.propelorm.org), in two separate packages for generator and runtime:

```bash
$ pear channel-discover pear.propelorm.org
$ sudo pear install -a propel/Propel2
```

>**Tip**<br />If you would like to use a beta or RC version of Propel, you may need to change your preferred_state PEAR environment variable.

## Full Propel Package ##

Please download one of the packages below if you would like to install the traditional Propel package, which includes both runtime and generator components.

* [Last version of Propel as ZIP file](https://github.com/propelorm/Propel2/zipball/master)
* [Last version of Propel as TAR.GZ file](https://github.com/propelorm/Propel2/tarball/master)

Other releases are available for download at [files.propelorm.org](http://files.propelorm.org).

## License ##

Copyright (c) 2005-2011 Hans Lellelid, David Zuelke, Francois Zaninotto, William
Durand

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
