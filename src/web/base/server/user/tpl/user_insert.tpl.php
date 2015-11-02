<!--
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/
//-->
<h2>{label} "{name}"</h2>
<div id="form">
	<form action="{thisfile}" method="POST">
		{form}
		{user}
		
		<div class="row">
			<fieldset class="span5">
				<legend>Account</legend>
				{lang}
				{role}
				{pass1}
				{pass2}
			</fieldset>
		
			<fieldset class="span5">
				<legend>Personal information</legend>
				{gender}
				{forename}
				{lastname}
			</fieldset>
		</div>

		
		<fieldset>
			<legend>Company information</legend>
			{office}
			{department}
			{state}
			{description}
			{capabilities}
		</fieldset>
		{submit}{cancel}
			
	</form>
</div>
