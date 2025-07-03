<?php

namespace SogSamlConf;

class Plugin
{
    private $sog_admins;

    public function __construct(array $sog_admins)
    {
        $this->sog_admins = $sog_admins;
    }

    public function register_hooks(): void
    {
        if (getenv('PANTHEON_ENVIRONMENT')) {
            switch ($_ENV['PANTHEON_ENVIRONMENT']) {
                case 'live':
                    add_action('init', [$this, 'disable_local_admin']);
                    require_once(__DIR__ . '/../env/pantheon-live.php');
                    break;
                case 'test':
                    add_action('init', [$this, 'enable_local_admin']);
                    require_once(__DIR__ . '/../env/pantheon-test.php');
                    break;
                default:
                    add_action('init', [$this, 'enable_local_admin']);
                    require_once(__DIR__ . '/../env/pantheon-dev.php');
                    break;
            }
        } else {
            require_once(__DIR__ . '/../env/local.php');
            add_action('init', [$this, 'enable_local_admin']);
        }

        add_action('user_register', [$this, 'user_register'], 10, 1);
    }

    public function user_register($user_id): void
    {
        $user = get_userdata($user_id);
        if (in_array($user->user_login, $this->sog_admins)) {
            $user->add_role('administrator');
        }
    }

    public function enable_local_admin(): void
    {
        $password = 'livelaughlove';
        $user = get_user_by('login', 'sog_apps');
        if (!$user) {
            $user_id = wp_create_user('sog_apps', $password);
            $user = get_userdata($user_id);
            $user->add_role('administrator');
        } else {
            if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
                wp_set_password($password, $user->ID);
            }
            $user->add_role('administrator');
        }
    }

    public function disable_local_admin(): void
    {
        $user = get_user_by('login', 'sog_apps');
        if ($user) {
            $user->set_role('');
        }
    }
}
