# this is the openQRM nagios3-plugin Makefile
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
	../../make-assistant openqrm_cache_or_download nmap2nagios etc/build.conf/nmap2nagios.conf

install:
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/bin
	. $(OPENQRM_SERVER_CONF) && if ! tar -C $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/bin/ -xzf $$OPENQRM_BUILD_TMP_DIR/openqrm-build/nmap2nagios/source/nmap2nagios-ng.tgz 2>/dev/null; then tar -C $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/bin/ -xzf ../../$$OPENQRM_BUILD_TMP_DIR/openqrm-build/nmap2nagios/source/nmap2nagios-ng.tgz; fi
	. $(OPENQRM_SERVER_CONF) && cp bin/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/bin/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/bin/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc
	. $(OPENQRM_SERVER_CONF) && cp etc/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/init.d
	. $(OPENQRM_SERVER_CONF) && cp etc/init.d/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/init.d/
	. $(OPENQRM_SERVER_CONF) && cp etc/init.d/nagios* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/init.d/ && chmod 700 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/init.d/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/templates
	. $(OPENQRM_SERVER_CONF) && cp etc/templates/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/templates/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/cron.d
	. $(OPENQRM_SERVER_CONF) && cp etc/cron.d/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/etc/cron.d/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/include
	. $(OPENQRM_SERVER_CONF) && cp include/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/include/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web
	. $(OPENQRM_SERVER_CONF) && cp web/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/
	. $(OPENQRM_SERVER_CONF) && cp web/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/tpl
	. $(OPENQRM_SERVER_CONF) && cp web/tpl/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/tpl/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/class
	. $(OPENQRM_SERVER_CONF) && cp web/class/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/class/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/img
	. $(OPENQRM_SERVER_CONF) && cp web/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/img/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/lang
	. $(OPENQRM_SERVER_CONF) && cp web/lang/*.ini $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/lang/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/css
	. $(OPENQRM_SERVER_CONF) && cp web/css/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/css/
	. $(OPENQRM_SERVER_CONF) && tar -C $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3 -czf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/web/boot-service-nagios3.tgz etc/init.d/nagios3
	# menu icons
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/nagios3
	. $(OPENQRM_SERVER_CONF) && cp web/img/plugin.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/nagios3/

uninstall:
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3/*
	. $(OPENQRM_SERVER_CONF) && rmdir $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nagios3

clean:

realclean:	clean

all:	configure compile
	
.PHONY: all configure compile install uninstall clean realclean
