<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class debug_run implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = "This option is intended for use by test profile writers and is identical to the <em>run</em> option but will yield more information during the run process that can be used to debug issues with a test profile or to verify the test profile is functioning correctly.";

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null, "Nothing was found to run.")
		);
	}
	public static function run($r)
	{
		pts_test_run_manager::standard_run($r, pts_c::debug_mode);
	}
}

?>
