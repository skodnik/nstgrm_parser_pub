<?php

namespace App\Controllers;

use App\Models;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Console
 * @package App\Controllers
 */
class Console extends Controller
{
    /**
     * @throws GuzzleException
     */
    public function __invoke()
    {
        $options = $this->options;
        $service = $this->service;
        $view = $this->view;

        if (!$options) {
            $view('Welcome');
            exit();
        }


        if (isset($options['s'])) {
            $init = $options['s'];
            $report = $service->prepareDataBase();
            $view('Initial', $report);
        }

        if ($service->tableExists($service->config['db']['tablesprefix'] . 'tracked')) {

            if (isset($options['u'])) {
                $service->updateService();
            }

            if (isset($options['i'])) {
                $user = new Models\User($options['i']);
                $service->getUserDataFromInstagram($user);

                $view('Info_user', $user);
            }

            if (isset($options['b'])) {
                $user = new Models\User($options['b']);
                $service->getUserDataFromService($user);

                $view('Info_user', $user);
            }

            if (isset($options['d'])) {
                $user = new Models\User($options['d']);
                $meta = $service->updateUserIntoService($user, ['is_delete' => 1]);

                $view('Settings_user', $meta);
            }

            if (isset($options['a'])) {
                $user = new Models\User($options['a']);
                $meta = $service->insertUserIntoService($user);

                $view('Settings_user', $meta);
            }

            if (isset($options['r'])) {
                echo 'Adding initial instagram users:' . PHP_EOL;
                $initUsers = file(__DIR__ . '/../users_tracked_init.csv',
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $i = 1;
                foreach ($initUsers as $userName) {
                    $user = new Models\User($userName);
                    $service->insertUserIntoService($user);

                    echo $i . '. ';
                    $view('Settings_user', $user);

                    $i++;
                }
            }

            if (isset($options['c'])) {
                echo 'Deleting initial instagram users:' . PHP_EOL;
                $initUsers = file(__DIR__ . '/../users_tracked_init.csv',
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $i = 1;
                foreach ($initUsers as $userName) {
                    $user = new Models\User($userName);
                    $service->updateUserIntoService($user, ['is_delete' => 1]);

                    echo $i . '. ';
                    $view('Settings_user', $user);

                    $i++;
                }
            }
        } else {
            echo 'Initial table "' . $service->config['db']['tablesprefix'] . 'tracked" not found!' . PHP_EOL . PHP_EOL;
            $report = $service->prepareDataBase();
            $view('Initial', $report);
        }
    }
}
