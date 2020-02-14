<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class pgo extends pts_module_interface
{
    const module_name = 'Benchmarking Compiler PGO Impact';
    const module_version = '1.0.0';
    const module_description = 'This module makes it easy to test a compiler PGO (Profile Guided Optimization) performance impact by running a test without PGO optimizations, capturing the PGO profile, rebuilding the tests with the PGO profile generated, and then repeat the benchmarks.';
    const module_author = 'Michael Larabel';

    protected static $phase = '';
    protected static $pgo_storage_dir = '';
    protected static $stock_cflags = '';
    protected static $stock_cxxflags = '';

    public static function user_commands()
    {
        return array('benchmark' => 'pgo_benchmark');
    }

    public static function pgo_benchmark($to_run)
    {
        self::$pgo_storage_dir = pts_client::create_temporary_directory('pgo', true);
        echo 'PGO directory is: ' . self::$pgo_storage_dir . PHP_EOL;
        self::$stock_cflags = getenv('CFLAGS');
        self::$stock_cxxflags = getenv('CXXFLAGS');

        // make the initial run manager, collect the result file data we'll need, and run the tests pre-PGO...
        $run_manager = new pts_test_run_manager();
        $run_manager->set_batch_mode(true);
        $save_name = $run_manager->prompt_save_name();
        $result_identifier = $run_manager->prompt_results_identifier();
        $run_manager->do_skip_post_execution_options();

        // Also force a fresh install before doing any of the PGO-related args...
        self::$phase = 'PRE_PGO';
        pts_test_installer::standard_install($to_run, true);

        // Get all tests we could run
        $run_manager->initial_checks($to_run);
        $run_manager->load_tests_to_run($to_run);
        $tests = $run_manager->get_tests_to_run();

        // Save results?
        $run_manager->save_results_prompt();

        // run the tests saving PRE-PGO results
        echo "Running test without PGO to generate baseline.\n";
//        $run_manager->pre_execution_process();
//        $run_manager->call_test_runs();
//        $run_manager->post_execution_process();

        // Split into cross validation sets

        if (count($tests) >= 3) {
            echo "Performing cross validation with a pool test size of " . count($tests);
            $num_cross_validation_sets = 3;
            $cross_validation_sets = [];

            // Add tests to validation sets
            for ($i = 0; $i < $num_cross_validation_sets; $i++) {
                array_push($cross_validation_sets, []);
                for ($j = 0; $j < count($tests); $j++) {
                    if ($j % $num_cross_validation_sets == $i) {
                        array_push($cross_validation_sets[$i], $tests[$j]);
                    }
                }
            }

            // Perform cross validation
            for ($i = 0; $i < $num_cross_validation_sets; $i++) {

                // force install of tests with PGO generation bits...
                self::$phase = 'GENERATE_PGO';

                // at least some say serial make ends up being better for PGO generation to not confuse the PGO process, the below override ensures -j 1
                pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
                pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);

                pts_test_installer::standard_install($to_run, true);

                // restore env vars about CPU core/jobs count
                pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
                pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');

                // run the tests in the training validation set, not saving the results, in order to generate the PGO profiles...
                putenv('FORCE_TIMES_TO_RUN=1');
                echo "Training PGO on validation set " . $i . "\n";
                $run_manager = new pts_test_run_manager(array('SaveResults' => false, 'RunAllTestCombinations' => false), true);
                $run_manager->set_tests_to_run($cross_validation_sets[$i]);
                $run_manager->pre_execution_process();
                $run_manager->call_test_runs();
                $run_manager->post_execution_process();
                putenv('FORCE_TIMES_TO_RUN'); // unset

                // force re-install of tests, in process set PGO using bits -fprofile-dir=/data/pgo -fprofile-use=/data/pgo -fprofile-correction
                self::$phase = 'USE_PGO';
                pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
                pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);
                pts_test_installer::standard_install($to_run, true);
                pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
                pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');

                // run the tests saving results with " - PGO Trained on Validation Set X" postfix
                $run_manager = new pts_test_run_manager(array('UploadResults' => false, 'SaveResults' => true, 'PromptForTestDescription' => false, 'RunAllTestCombinations' => false, 'PromptSaveName' => true, 'PromptForTestIdentifier' => true, 'OpenBrowser' => true), true);
                $run_manager->set_save_name($save_name, false);
                $run_manager->set_results_identifier($result_identifier . ' - PGO Trained on Validation Set ' . $i);

                // Merge remaining sets into one testing set
                $testing_tests = [];
                for ($j = 0; $j < $num_cross_validation_sets; $j++) {
                    if ($i == $j) {
                        continue;
                    }
                    $testing_tests = array_merge($testing_tests, $cross_validation_sets[$j]);
                }

                echo "Benchmarking PGO on remaining validation sets \n";
                $run_manager->set_tests_to_run($testing_tests);
                $run_manager->pre_execution_process();
                $run_manager->call_test_runs();
                $run_manager->post_execution_process();

                // remove PGO files
                pts_file_io::delete(self::$pgo_storage_dir);
            }

        } else {
            echo "Not enough tests to run cross validation, pool size: " . count($tests) . "\n";


            // force install of tests with PGO generation bits...
            self::$phase = 'GENERATE_PGO';

            // at least some say serial make ends up being better for PGO generation to not confuse the PGO process, the below override ensures -j 1
            pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
            pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);

            pts_test_installer::standard_install($to_run, true);

            // restore env vars about CPU core/jobs count
            pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
            pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');

            // run the tests one time each, not saving the results, in order to generate the PGO profiles...
            putenv('FORCE_TIMES_TO_RUN=1');
            $run_manager = new pts_test_run_manager(array('SaveResults' => false, 'RunAllTestCombinations' => false), true);
            $run_manager->standard_run($to_run);
            putenv('FORCE_TIMES_TO_RUN'); // unset


            // force re-install of tests, in process set PGO using bits -fprofile-dir=/data/pgo -fprofile-use=/data/pgo -fprofile-correction
            self::$phase = 'USE_PGO';
            pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
            pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);
            pts_test_installer::standard_install($to_run, true);
            pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
            pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');


            // run the tests saving results with " - PGO" postfix
            $run_manager = new pts_test_run_manager(array('UploadResults' => false, 'SaveResults' => true, 'PromptForTestDescription' => false, 'RunAllTestCombinations' => false, 'PromptSaveName' => true, 'PromptForTestIdentifier' => true, 'OpenBrowser' => true), true);
            $run_manager->set_save_name($save_name, false);
            $run_manager->set_results_identifier($result_identifier . ' - PGO');
            $run_manager->standard_run($to_run);

            // remove PGO files
            pts_file_io::delete(self::$pgo_storage_dir);
        }
    }


	public static function __pre_test_install($test_install_request)
	{
		$pgo_dir = self::$pgo_storage_dir . $test_install_request->test_profile->get_identifier() . '/';
		pts_file_io::mkdir($pgo_dir);

        // Add custom clang path in
//        $clang_pass_folder_path = getenv("LLVM_PASS");
        $clang_pass_folder_path = '/home/chris/programming/llvm-pass-skeleton/build/skeleton/libSkeletonPass.so';
        $pass_string = ' -Xclang load -Xclang ' . $clang_pass_folder_path;
        switch (self::$phase) {
            case 'PRE_PGO':
                break;
            case 'GENERATE_PGO':
                putenv('CFLAGS=' . self::$stock_cflags . '-fprofile-generate=' . $pgo_dir . $pass_string);
                putenv('CXXFLAGS=' . self::$stock_cxxflags . '-fprofile-generate=' . $pgo_dir . $pass_string);
                break;
            case 'USE_PGO':
                // TODO Make only the pass be used for PGO, no other PGO based optimizations
                shell_exec('llvm-profdata-9 merge -output=' . $pgo_dir . 'code.profdata ' . $pgo_dir);
                putenv('CFLAGS=' . self::$stock_cflags . '-fprofile-use=' . $pgo_dir . 'code.profdata ' . $pass_string);
                putenv('CXXFLAGS=' . self::$stock_cxxflags . '-fprofile-use=' . $pgo_dir . 'code.profdata ' . $pass_string);
                break;
        }
    }
}

?>
