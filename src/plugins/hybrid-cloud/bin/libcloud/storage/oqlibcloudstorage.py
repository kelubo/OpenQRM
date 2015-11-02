#!/usr/bin/python
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
import sys, getopt
from libcloud.storage.providers import get_driver
from libcloud.storage.types import Provider, ContainerDoesNotExistError


def openqrm_lc_parse_cmdline(argv):
	ACCESS_ID = ''
	SECRET_KEY = ''
	REGION = ''
	FILTER = ''
	IDENTIFIER = ''
	INSTANCE_TYPE = ''
	PROVIDER = ''
	USERNAME = ''
	PASSWORD = ''
	TENANT = ''
	HOST = ''
	PORT = ''
	ENDPOINT = ''
	AMI = ''
	SIZE = ''
	KEYPAIR = ''
	GROUP = ''
	USERDATA = ''

	try:
		opts, args = getopt.getopt(argv,"hO:W:",["region=","provider=", "filter=", "instance-type=", "username=", "password=", "tenant=", "host=", "port=", "endpoint=", "identifier=", "ami=", "size=", "keypair=", "identifier=", "group=", "userdata="])
		#print opts

	except getopt.GetoptError:
		print 'lc-command -O <aws-access-key> -W <aws-secret-key> --region <region> --filter <filter> --provider <provider> --instance-type <type> --username <username> --password <password> --tenant <tenant-name> --host <ip-address/hostname> --port <portnumber> --endpoint <service-url-endpoint> --identifier <identifier> --ami <ami> --size <size> --keypair <keypair> --group <group> --userdata <userdata-file>'
		sys.exit(2)
	for opt, arg in opts:
		#print opt
		#print arg
		if opt == '-h':
			print 'lc-command -O <aws-access-key> -W <aws-secret-key> --region <region> --filter <filter> --provider <provider> --instance-type <type> --username <username> --password <password> --tenant <tenant-name> --host <ip-address/hostname> --port <portnumber> --endpoint <service-url-endpoint> --identifier <identifier> --ami <ami> --size <size> --keypair <keypair> --group <group> --userdata <userdata-file>'
			sys.exit()
		elif opt in ("-O", "--aws-access-key"):
			ACCESS_ID = arg
		elif opt in ("-W", "--aws-secret-key"):
			SECRET_KEY = arg
		elif opt in ("--region"):
			REGION = arg
		elif opt in ("--filter"):
			FILTER = arg
		elif opt in ("--instance-type"):
			INSTANCE_TYPE = arg
		elif opt in ("--provider"):
			PROVIDER = arg
		elif opt in ("--username"):
			USERNAME = arg
		elif opt in ("--password"):
			PASSWORD = arg
		elif opt in ("--tenant"):
			TENANT = arg
		elif opt in ("--host"):
			HOST = arg
		elif opt in ("--endpoint"):
			ENDPOINT = arg
		elif opt in ("--port"):
			PORT = arg
		elif opt in ("--identifier"):
			IDENTIFIER = arg
		elif opt in ("--ami"):
			AMI = arg
		elif opt in ("--size"):
			SIZE = arg
		elif opt in ("--keypair"):
			KEYPAIR = arg
		elif opt in ("--group"):
			GROUP = arg
		elif opt in ("--userdata"):
			USERDATA = arg
	return dict([('ACCESS_ID', ACCESS_ID), ('SECRET_KEY', SECRET_KEY), ('REGION', REGION), ('FILTER', FILTER), ('IDENTIFIER', IDENTIFIER), ('INSTANCE_TYPE', INSTANCE_TYPE), ('PROVIDER', PROVIDER), ('USERNAME', USERNAME), ('PASSWORD', PASSWORD), ('TENANT', TENANT), ('HOST', HOST), ('PORT', PORT), ('ENDPOINT', ENDPOINT), ('IDENTIFIER', IDENTIFIER), ('AMI', AMI), ('SIZE', SIZE), ('KEYPAIR', KEYPAIR), ('GROUP', GROUP), ('USERDATA', USERDATA)])


def openqrm_lc_get_connection(params):

	if params['PROVIDER'] == 'EC2_EU_WEST':
		Driver = get_driver(Provider.EC2_EU_WEST)
		conn = Driver(params['ACCESS_ID'], params['SECRET_KEY'])
		return conn
	elif params['PROVIDER'] == 'EC2_US_EAST':
		Driver = get_driver(Provider.EC2_US_EAST)
		conn = Driver(params['ACCESS_ID'], params['SECRET_KEY'])
		return conn
	elif params['PROVIDER'] == 'OPENSTACK':
		OpenStack = get_driver(Provider.OPENSTACK_SWIFT)
		Driver = OpenStack(params['USERNAME'], params['PASSWORD'],
			ex_force_auth_url='http://192.168.0.1:5000/v2.0',
			ex_force_service_name='swift',
			eex_force_service_type='object-store')
		return Driver


