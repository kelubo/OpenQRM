# this is the openQRM image-shelf-plugin Makefile
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

install:
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/etc
	. $(OPENQRM_SERVER_CONF) && cp etc/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/etc/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/etc/init.d
	. $(OPENQRM_SERVER_CONF) && cp etc/init.d/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/etc/init.d/ && chmod 700 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/etc/init.d/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/include
	. $(OPENQRM_SERVER_CONF) && cp include/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/include/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web
	. $(OPENQRM_SERVER_CONF) && cp web/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/
	. $(OPENQRM_SERVER_CONF) && cp web/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/
	. $(OPENQRM_SERVER_CONF) && cp web/*.conf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/class
	. $(OPENQRM_SERVER_CONF) && cp web/class/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/class/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/tpl
	. $(OPENQRM_SERVER_CONF) && cp web/tpl/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/tpl/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/img
	. $(OPENQRM_SERVER_CONF) && cp web/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/img/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/css
	. $(OPENQRM_SERVER_CONF) && cp web/css/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/css/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/lang
	. $(OPENQRM_SERVER_CONF) && cp web/lang/*.ini $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web/lang/
	. $(OPENQRM_SERVER_CONF) && chmod 777 $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/web
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/bin
	. $(OPENQRM_SERVER_CONF) && cp bin/openqrm* $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/bin/
	. $(OPENQRM_SERVER_CONF) && chmod +x $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/bin/openqrm*
	# menu icons
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/image-shelf
	. $(OPENQRM_SERVER_CONF) && cp web/img/plugin.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/image-shelf/

uninstall:
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf/*
	. $(OPENQRM_SERVER_CONF) && rmdir $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/image-shelf

clean:

realclean:	clean

all:	configure compile
	
.PHONY: all configure compile install uninstall clean realclean
