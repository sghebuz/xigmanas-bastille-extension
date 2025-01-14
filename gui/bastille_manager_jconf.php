<?php
/*
	bastille_manager_jconf.inc

	Copyright (c) 2020 José Rivera (joserprg@gmail.com).
    All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions
	are met:
	1. Redistributions of source code must retain the above copyright
	    notice, this list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright
	    notice, this list of conditions and the following disclaimer in the
	    documentation and/or other materials provided with the distribution.
	3. Neither the name of the developer nor the names of contributors
	    may be u/usr/bin/sed to endorse or promote products derived from this software
	    without specific prior written permission.

	THIS SOFTWARE IS PROVIDED BY THE DEVELOPER ``AS IS'' AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	ARE DISCLAIMED.  IN NO EVENT SHALL THE DEVELOPER OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
	OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
	HOWEVER CAU/usr/bin/sed AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
	OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVI/usr/bin/sed OF THE POSSIBILITY OF
	SUCH DAMAGE.
*/

require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("bastille_manager-lib.inc");

if (isset($_GET['uuid']))
	$uuid = $_GET['uuid'];
if (isset($_POST['uuid']))
	$uuid = $_POST['uuid'];

if(isset($_GET['jailname'])):
	$container = $_GET['jailname'];
endif;
if(isset($_POST['jailname'])):
	$container = $_POST['jailname'];
endif;

$cnid = FALSE;
if(isset($container) && !empty($container)):
	$pconfig['uuid'] = uuid();
	$pconfig['jailname'] = $container;
	if(preg_match('/^([^\/\@]+)(\/([^\@]+))?\@(.*)$/', $pconfig['jailname'], $m)):
		$pconfig['name'] = $m[''];
	else:
		$pconfig['name'] = 'unknown';
	endif;
else:
	// not supported
	$pconfig = [];
endif;

$pgtitle = [gtext('Extensions'),gtext('Bastille'),gtext('Configuration'), $container];
$jail_config = "$jail_dir/$container/jail.conf";

// Get some jail system settings.
$is_vnet = exec("/usr/bin/grep '.*vnet;' $jail_config");
$pconfig['autostart'] =  exec("/usr/bin/grep -w '{$container}_AUTO_START=\"YES\"' $bastille_config");

// Get some jail config parameters.
// This could be done with a nice php preg loop in the future.
$pconfig['jname'] = "$container";
$pconfig['hostname'] = exec("/usr/bin/grep '.*host.hostname.*=' $jail_config | /usr/bin/sed 's/.*host.hostname.*= //;s/;//'");
$pconfig['ipv4'] = exec("/usr/bin/grep '.*ip4.addr.*=' $jail_config | /usr/bin/sed 's/.*ip4.addr.*= //;s/;//'");
$pconfig['ipv6'] = exec("/usr/bin/grep '.*ip6.addr.*=' $jail_config | /usr/bin/sed 's/.*ip6.addr.*= //;s/;//'");
$pconfig['interface'] = exec("/usr/bin/grep '.*interface.*=' $jail_config | /usr/bin/sed 's/.*interface.*= //;s/;//'");
$pconfig['securelevel'] = exec("/usr/bin/grep '.*securelevel.*=' $jail_config | /usr/bin/sed 's/.*securelevel.*= //;s/;//'");
$pconfig['devfs_ruleset'] = exec("/usr/bin/grep '.*devfs_ruleset.*=' $jail_config | /usr/bin/sed 's/.*devfs_ruleset.*= //;s/;//'");
$pconfig['enforce_statfs'] = exec("/usr/bin/grep '.*enforce_statfs.*=' $jail_config | /usr/bin/sed 's/.*enforce_statfs.*= //;s/;//'");
$pconfig['vnet_interface'] = exec("/usr/bin/grep '.*vnet.interface.*=' $jail_config | /usr/bin/sed 's/.*vnet.interface.*= //;s/;//'");

// Set the jail config default parameters.
$jail_name_def = $pconfig['jname'];
$jail_hostname_def = $pconfig['hostname'];
$jail_ipv4_def = $pconfig['ipv4'];
$jail_ipv6_def = $pconfig['ipv6'];
$jail_interface_def = $pconfig['interface'];
$jail_securelevel_def = $pconfig['securelevel'];
$jail_devfs_ruleset_def = $pconfig['devfs_ruleset'];
$jail_enforce_statfs_def = $pconfig['enforce_statfs'];
$jail_vnet_interface_def = $pconfig['vnet_interface'];

// Check if is a Linux jail.
$is_linux_jail = exec("/usr/bin/grep linsysfs {$jail_dir}/{$jail_name_def}/fstab");

if ($_POST):
	global $configfile;
	unset($savemsg);
	unset($input_errors);
	$pconfig = $_POST;

	// Return to index.
	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header("Location: bastille_manager_gui.php");
		exit;
	endif;

	// Input validation.
	// Perform some simple validations for now.
	if(isset($_POST['jname']) && ($pconfig['jname'])):
		if(!preg_match('/^[A-Za-z0-9-_]+$/D', $pconfig['jname'])):
			$input_errors[] = gtext("A valid jail name must be specified.");
		endif;
	else:
		$input_errors[] = gtext("A valid jail name must be specified, it can't be left blank.");
	endif;

	if(isset($_POST['hostname']) && ($pconfig['hostname'])):
		if(preg_match('/\s/', $pconfig['hostname'])):
			$input_errors[] = gtext("A valid hostname must be specified.");
		endif;
	else:
		$input_errors[] = gtext("A valid hostname must be specified, it can't be left blank.");
	endif;

	if(isset($_POST['ipv4'])):
		if(!preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))?$/', $pconfig['ipv4'])):
			$input_errors[] = gtext("A valid IPv4 address must be specified.");
		endif;
	endif;

	if(isset($_POST['ipv6'])):
		if(!preg_match('/^(([a-fA-F0-9:]+$)|([a-fA-F0-9:]+\/[0-9]{1,3}$))/', $pconfig['ipv6'])):
			$input_errors[] = gtext("A valid IPv6 address must be specified.");
		endif;
	endif;

	if(isset($_POST['securelevel'])):
		if(!preg_match('/^[0-3]$/', $pconfig['securelevel'])):
			$input_errors[] = gtext("A valid number must be specified for securelevel, between 0-3.");
		endif;
	endif;

	if(isset($_POST['devfs_ruleset'])):
		if(!preg_match('/^([0-9]{1,3})$/', $pconfig['devfs_ruleset'])):
			$input_errors[] = gtext("A valid number must be specified for devfs_ruleset.");
		endif;
	endif;

	if(isset($_POST['enforce_statfs'])):
		if(!preg_match('/^[0-2]$/', $pconfig['enforce_statfs'])):
			$input_errors[] = gtext("A valid number must be specified for enforce_statfs, between 0-2.");
		endif;
	endif;

	// Try to edit the jail config.
	// This could be done with preg in the future.
	if($input_errors):
		$input_errors[] = gtext("Aborting config changes.");
	else:
		if(isset($_POST['Submit']) && $_POST['Submit']):

			// Set current config values.
			$jail_name = $pconfig['jname'];
			$jail_hostname = $pconfig['hostname'];
			$jail_ipv4 = $pconfig['ipv4'];
			$jail_ipv6 = $pconfig['ipv6'];
			$jail_interface = $pconfig['interface'];
			$jail_securelevel = $pconfig['securelevel'];
			$jail_devfs_ruleset = $pconfig['devfs_ruleset'];
			$jail_enforce_statfs = $pconfig['enforce_statfs'];
			$jail_vnet_interface = $pconfig['vnet_interface'];

			// Check if the config has changed.
			// This could be done with a nice foreach loop in the future.
			if($jail_name_def !== $jail_name):
				$is_changed = "1";
			elseif($jail_hostname_def !== $jail_hostname):
				$is_changed = "1";
			elseif(isset($_POST['ipv4']) && ($jail_ipv4_def !== $jail_ipv4)):
				$is_changed = "1";
			elseif(isset($_POST['ipv6']) && ($jail_ipv6_def !== $jail_ipv6)):
				$is_changed = "1";
			elseif(isset($_POST['interface']) && ($jail_interface_def !== $jail_interface)):
				$is_changed = "1";
			elseif($jail_securelevel_def !== $jail_securelevel):
				$is_changed = "1";
			elseif($jail_devfs_ruleset_def !== $jail_devfs_ruleset):
				$is_changed = "1";
			elseif($jail_enforce_statfs_def !== $jail_enforce_statfs):
				$is_changed = "1";
			elseif(isset($_POST['vnet_interface']) && ($jail_vnet_interface_def !== $jail_vnet_interface)):
				$is_changed = "1";
			endif;

			if($is_changed):
				// Check if the jail is running.
				$cmd = "/usr/sbin/jls -j $container >/dev/null 2>&1";
				unset($output,$retval);mwexec2($cmd,$output,$retval);
			else:
				// Skip jail running check.
				$retval = "1";
			endif;
			if($retval == 0):
				$input_errors[] = gtext("This jail is running, please stop it before making jail.conf changes.");
			else:
				if (isset($_POST['hostname']) && $_POST['hostname']):
					if($jail_hostname_def !== $jail_hostname):
						$cmd = "/usr/bin/sed -i '' 's/.*host.hostname.*=.*;/  host.hostname = $jail_hostname;/' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Hostname changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save hostname.");
						endif;
					endif;
				endif;

				if (isset($_POST['ipv4']) && $_POST['ipv4']):
					if($jail_ipv4_def !== $jail_ipv4):
						$cmd = "/usr/bin/sed -i '' 's|.*ip4.addr.*=.*;|  ip4.addr = $jail_ipv4;|' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("IPv4 changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save IPv4.");
						endif;
					endif;
				endif;

				if (isset($_POST['ipv6']) && $_POST['ipv6']):
					if($jail_ipv6_def !== $jail_ipv6):
						$cmd = "/usr/bin/sed -i '' 's|.*ip6.addr.*=.*;|  ip6.addr = $jail_ipv6;|' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("IPv6 changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save IPv6.");
						endif;
					endif;
				endif;

				if (isset($_POST['interface']) && $_POST['interface']):
					if($jail_interface_def !==  $jail_interface):
						if ($_POST['interface'] !== 'Config'):
							$cmd = "/usr/bin/sed -i '' 's|.*interface.*=.*;|  interface = $jail_interface;|' $jail_config";
							unset($output,$retval);mwexec2($cmd,$output,$retval);
							if($retval == 0):
								//$savemsg .= gtext("Interface changed successfully.");
							else:
								$input_errors[] = gtext("Failed to save interface.");
							endif;
						endif;
					endif;
				endif;

				if (isset($_POST['vnet_interface']) && $_POST['vnet_interface']):
					if($jail_vnet_interface_def !==  $jail_vnet_interface):
						if ($_POST['vnet_interface'] !== 'Config'):
							$cmd = "/usr/bin/sed -i '' 's|.*vnet.interface.*=.*;|  vnet.interface = $jail_vnet_interface;|' $jail_config";
							unset($output,$retval);mwexec2($cmd,$output,$retval);
							if($retval == 0):
								//$savemsg .= gtext("VNET Interface changed successfully.");
							else:
								$input_errors[] = gtext("Failed to save VNET Interface.");
							endif;
						endif;
					endif;
				endif;

				if (isset($_POST['securelevel']) || $_POST['securelevel']):
					if($jail_securelevel_def !== $jail_securelevel):
						$cmd = "/usr/bin/sed -i '' 's/.*securelevel.*=.*;/  securelevel = $jail_securelevel;/' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Securelevel changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save securelevel.");
						endif;
					endif;
				endif;

				if (isset($_POST['devfs_ruleset']) || $_POST['devfs_ruleset']):
					if($jail_devfs_ruleset_def !== $jail_devfs_ruleset):
						$cmd = "/usr/bin/sed -i '' 's/.*devfs_ruleset.*=.*;/  devfs_ruleset = $jail_devfs_ruleset;/' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Devfs_ruleset changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save devfs_ruleset.");
						endif;
					endif;
				endif;

				if (isset($_POST['enforce_statfs']) || $_POST['enforce_statfs']):
					if($jail_enforce_statfs_def !== $jail_enforce_statfs):
						$cmd = "/usr/bin/sed -i '' 's/.*enforce_statfs.*=.*;/  enforce_statfs = $jail_enforce_statfs;/' $jail_config";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Enforce_statfs changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save enforce_statfs.");
						endif;
					endif;
				endif;

				if (isset($_POST['autostart']) && $_POST['autostart']):
					if($jail_name_def !== $jail_name):
						// Remove obsolete variable.
						exec("/usr/sbin/sysrc -f $configfile -x {$jail_name_def}_AUTO_START");
					endif;
					$cmd = ("/usr/sbin/sysrc -f $configfile {$jail_name}_AUTO_START=\"YES\"");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($retval == 0):
						//$savemsg .= gtext("Autostart changed successfully.");
					else:
						$input_errors[] = gtext("Failed to enable autostart.");
					endif;
				else:
					if($jail_name_def !== $jail_name):
						// Remove obsolete variable.
						exec("/usr/sbin/sysrc -f $configfile -x {$jail_name_def}_AUTO_START");
					endif;
					if(exec("/usr/sbin/sysrc -f $configfile -qn {$jail_name}_AUTO_START")):
						$cmd = ("/usr/sbin/sysrc -f $configfile -x {$jail_name}_AUTO_START");
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Autostart changed successfully.");
						else:
							$input_errors[] = gtext("Failed to disable autostart.");
						endif;
					endif;
				endif;

				if (isset($_POST['jname']) && $_POST['jname']):
					if($jail_name_def !== $jail_name):
						$cmd = "/usr/local/bin/bastille rename $jail_name_def $jail_name";
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							//$savemsg .= gtext("Jail name changed successfully.");
						else:
							$input_errors[] = gtext("Failed to save jail name.");
						endif;
					endif;
				endif;
				//header("Location: bastille_manager_gui.php");
				$savemsg .= gtext("Configuration has been saved successfully.");
			endif;
		endif;
	endif;
endif;

?>
<?php include 'fbegin.inc';?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<tr><td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="bastille_manager_gui.php"><span><?=gtext("Containers");?></span></a></li>
				<li class="tabact"><a href="bastille_manager_maintenance.php"><span><?=gtext("Maintenance");?></span></a></li>
			</ul>
		</td></tr>
		<td class="tabcont">
			<form action="bastille_manager_jconf.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php if(!empty($savemsg)) print_info_box($savemsg); ?>
				<?php if(!empty($input_errors)) print_input_errors($input_errors); ?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php
					$a_action = $l_interfaces;
					html_titleline2(gtext("Jail Configuration"));
					html_inputbox("jname", gtext("Name"), $pconfig['jname'], gtext("Set the desired jail name, for example: jail_1. Warning: renaming a jail will also rename the directory/dataset."), true, 40);

					html_inputbox("hostname", gtext("Hostname"), $pconfig['hostname'], gtext("Set the desired jail hostname, for example: jail.com, not to be confused with the jail name."), true, 40);
					if ($jail_ipv4_def):
						html_inputbox("ipv4", gtext("IPv4"), $pconfig['ipv4'], gtext("Set the desired jail IPv4 address, for example: 192.168.1.100, or 192.168.1.100/24."), true, 40);
					endif;
					if ($jail_ipv6_def):
						html_inputbox("ipv6", gtext("IPv6"), $pconfig['ipv6'], gtext("Set the desired jail IPv4 address, for example: 2001:cdba::3257:9652, or 2001:cdba::3257:9652/64."), true, 40);
					endif;
					if (!$is_vnet):
						html_combobox('interface', gtext('Interface'),$pconfig['interface'], $a_action, gtext("Set the network interface available from the dropdown menu, usually should not be changed unless replacing/renaming interface or moving jail from host."), true, false, 'action_change()');
					endif;

					if(!$is_linux_jail):
						html_inputbox("securelevel", gtext("securelevel"), $pconfig['securelevel'], gtext("The value of the jail's kern.securelevel. A jail never has a lower securelevel than its parent system, but by setting this parameter it may have a higher one, default is 2."), false, 20);
					endif;
					html_inputbox("devfs_ruleset", gtext("devfs_ruleset"), $pconfig['devfs_ruleset'], gtext("The number of the devfs ruleset that is enforced for mounting devfs in this jail. A value of zero means no ruleset is enforced. default is 4, on VNET jails default is 13."), false, 20);
					if(!$is_linux_jail):
						html_inputbox("enforce_statfs", gtext("enforce_statfs"), $pconfig['enforce_statfs'], gtext("This determines what information processes in a jail are able to get about mount points. Affects the behaviour of the following syscalls: statfs, fstatfs, getfsstat and fhstatfs, default is 2."), false, 20);
					endif;
					if ($is_vnet):
						html_inputbox("vnet_interface", gtext("VNET Interface"), $pconfig['vnet_interface'], gtext("Set the VNET interface manually, usually should not be changed unless renaming the interface or moving jail from host."), false, 20);
					endif;
					?>
					<?php
					html_separator2();
					html_titleline2(gtext("Misc Configuration"));
					html_checkbox2('autostart',gtext('Autoboot'),!empty($pconfig['autostart']) ? true : false,gtext('Autoboot this jail after system reboot.'),'',false);
					//html_checkbox2('force_edit',gtext('Force edit'),!empty($pconfig['force_edit']) ? true : false,gtext('Automatically stop and start this jail if is already running.'),'',false);
					?>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Save");?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
					<input name="jailname" type="hidden" value="<?=$pconfig['jailname'];?>" />
					<input name="name" type="hidden" value="<?=$pconfig['name'];?>" />
				</div>
				<div id="remarks">
					<?php
					$helpinghand = '<a href="' . 'https://www.freebsd.org/cgi/man.cgi?query=jail.conf&sektion=5&n=1' . '" target="_blank">'
						. gtext('For additional information about the jail configuration file, check the FreeBSD documentation')
						. '</a>.';
					html_remark("note", gtext('Note'), $helpinghand);
					?>
				</div>
				<?php include 'formend.inc';?>
			</form>
		</td>
		<tr>
	</tr>
<table>
</table>
<?php include 'fend.inc';?>
