# this is the openQRM hybrid-cloud-plugin Makefile
#
# openQRM Enterprise developed by openQRM Enterprise GmbH.
#
# All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.
#
# This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
# The latest version of this license can be found here: src/doc/LICENSE.txt
#
# By using this software, you acknowledge having read this license and agree to be bound thereby.
#
#           http://openqrm-enterprise.com
#
# Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
#

export OPENQRM_SERVER_CONF=$(shell pwd)/../../etc/openqrm-server.conf

configure:

compile:
	../../make-assistant openqrm_cache_or_download amazon-s3-php-class etc/build.conf/amazon-s3-php-class.conf

install:
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc
	. $(OPENQRM_SERVER_CONF) && cp etc/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/init.d
	. $(OPENQRM_SERVER_CONF) && cp etc/init.d/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/init.d/ && chmod 700 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/init.d/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/templates
	. $(OPENQRM_SERVER_CONF) && cp etc/templates/configure* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/templates/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/commands
	. $(OPENQRM_SERVER_CONF) && cp etc/commands/*.conf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/etc/commands/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/include
	. $(OPENQRM_SERVER_CONF) && cp include/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/include/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web
	. $(OPENQRM_SERVER_CONF) && cp web/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/
	. $(OPENQRM_SERVER_CONF) && cp web/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/class
	. $(OPENQRM_SERVER_CONF) && cp web/class/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/class/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/lang
	. $(OPENQRM_SERVER_CONF) && cp web/lang/*.ini $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/lang/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/css
	. $(OPENQRM_SERVER_CONF) && cp web/css/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/css/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/tpl
	. $(OPENQRM_SERVER_CONF) && cp web/tpl/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/tpl/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/img
	. $(OPENQRM_SERVER_CONF) && cp web/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/img/
	. $(OPENQRM_SERVER_CONF) && chmod 777 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin
	. $(OPENQRM_SERVER_CONF) && cp bin/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/openqrm*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/compute
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/compute/lc-* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/compute/
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/compute/oq*.py $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/compute/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/compute/lc-*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/storage
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/storage/lc-* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/storage/
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/storage/oq*.py $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/storage/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/storage/lc-*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/loadbalancer
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/loadbalancer/lc-* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/loadbalancer/
	. $(OPENQRM_SERVER_CONF) && cp bin/libcloud/loadbalancer/oq*.py $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/loadbalancer/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/bin/libcloud/loadbalancer/lc-*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/hybrid-cloud-stat
	. $(OPENQRM_SERVER_CONF) && chmod 777 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/hybrid-cloud-stat

	# s3 php class
	. $(OPENQRM_SERVER_CONF) && if ! tar -C $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/class/ -xzvf $$OPENQRM_BUILD_TMP_DIR/openqrm-build/amazon-s3-php-class/source/amazon-s3-php-class-02072013.tgz 2>/dev/null; then tar -C $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/web/class/ -xzf ../../$$OPENQRM_BUILD_TMP_DIR/openqrm-build/amazon-s3-php-class/source/amazon-s3-php-class-02072013.tgz; fi
	# menu icons
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/hybrid-cloud
	. $(OPENQRM_SERVER_CONF) && cp web/img/plugin.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/hybrid-cloud/

uninstall:
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud/*
	. $(OPENQRM_SERVER_CONF) && rmdir $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/hybrid-cloud

clean:

realclean:	clean

all:	configure compile
	
.PHONY: all configure compile install uninstall clean realclean
