#!/bin/sh

# Install Chef Solo prerequisites.
apt-get install ruby ruby-dev libopenssl-ruby rdoc ri irb build-essential ssl-cert

# Install Chef Solo.
# Chef Solo 11.4.4 is broken, so install a previous version.
# The bug is planned to be fixed in 11.4.5 which haven't been released yet.
gem install --version '<11.4.4' chef --no-rdoc --no-ri

# Make sure the target directory for cookbooks exists.
mkdir -p /tmp/chef-solo

# Travis CI Worker machines don't support IPv4 (https://github.com/travis-ci/travis-ci/issues/1116).
# The `hostname` of machines point to an IPv6. Since CUBRID doesn't support IPv6,
# we need to point the `hostname` to `127.0.0.1` local IPv4 address for CUBRID to work properly.
hostname | sed 's/^/127.0.0.1 /g' | cat - /etc/hosts > /tmp/etchoststemp && mv /tmp/etchoststemp /etc/hosts

# Install CUBRID via Chef Solo. Download all cookbooks from a remote URL.
chef-solo -c tests/solo.rb -j tests/cubrid_chef.json -r http://sourceforge.net/projects/cubrid/files/CUBRID-Demo-Virtual-Machines/Vagrant/chef-cookbooks.tar.gz/download
