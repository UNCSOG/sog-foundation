<?php
/**
 * Plugin Name: SOG User Import
 * Plugin URI:
 * Description: This plugin will create/update users via the WordPress REST API.
 * Version: 2.0
 * Author: Lindsay Hoyt
 * Author URI:
 *
 * @package SOG User Import
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Display errors while in development.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Defining plugin version, core required version and the plugin directory path.
define('SOG_USER_IMPORT_VERSION', '1.0');
define('SOG_USER_IMPORT_MINIMUM_WP_VERSION', '5.0');
define('SOG_USER_IMPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Add admin menu
add_action('admin_menu', 'sog_user_import_menu');


function sog_user_import_menu() {
    add_menu_page(
        'SOG User Import',
        'SOG User Import',
        'manage_options',
        'sog-user-import',
        'sog_user_import_page',
        'dashicons-groups',
        6
    );
}

function sog_user_import_page() {
    ?>
    <div class="wrap">
        <h1>SOG User Import</h1>
        <br/>
        <p>Click this button to actually update users in mass.</p>
        <br/>
        <form method="post" action="">
            <?php wp_nonce_field('sog_user_import_action', 'sog_user_import_nonce'); ?>
            <input type="submit" name="sog_user_import_submit" class="button button-primary" value="Import Users">
        </form>
        <?php

        // echo "<h3>Debug Before: get_users_to_import()</h3><br/>";
        // echo "<pre>";
        //     echo "Users file path: " . SOG_USER_IMPORT_PLUGIN_DIR . 'users-to-import.php' . "\n";
        //     echo "File exists: " . (file_exists(SOG_USER_IMPORT_PLUGIN_DIR . 'users-to-import.php') ? 'Yes' : 'No') . "\n";
        // echo "</pre>";

        // Always call get_users_to_import() for debugging
        $users = get_users_to_import();

        // echo "<h3>Debug After: get_users_to_import()</h3><br/>";
        // echo "<pre>";
        //     echo "Number of users retrieved: " . count($users) . "\n";
        // echo "</pre>";

        if (isset($_POST['sog_user_import_submit']) && check_admin_referer('sog_user_import_action', 'sog_user_import_nonce')) {
            if (!empty($users)) {
                $result = update_users_from_array($users);
                echo $result;
            } else {
                echo "<p>No users found to import. Please check your users-to-import.php file.</p>";
            }
        }
        ?>
    </div>
    <?php
}


function get_users_to_import() {
    $users_file = SOG_USER_IMPORT_PLUGIN_DIR . 'users-to-import.php';

    // echo "<h3>Debug: get_users_to_import()</h3><br/>";
    echo "<pre>";
        // echo "File path: $users_file\n";
        // echo "File exists: " . (file_exists($users_file) ? 'Yes' : 'No') . "\n";

        if (file_exists($users_file)) {
            // Uncomment the 3 lines below if you need to debug that output.
            // echo "File contents:\n";
            // $file_contents = file_get_contents($users_file);
            // echo htmlspecialchars($file_contents) . "\n\n";

            $users = include $users_file;

            // echo "Type of \$users: " . gettype($users) . "\n";
            // echo "Is \$users an array: " . (is_array($users) ? 'Yes' : 'No') . "\n";
            // echo "Count of \$users: " . (is_array($users) ? count($users) : 'N/A') . "\n";

            if (!is_array($users)) {
                echo "Error: users-to-import.php did not return an array.\n";
                return [];
            }

            return $users;
        } else {
            echo "Error: users-to-import.php file not found.\n";
            return [];
        }
    echo "</pre>";
}

function update_users_from_array($users) {
    $total_users = count($users);
    $updated_count = 0;
    $error_count = 0;
    $skipped_count = 0;

    ob_start(); // Start output buffering

    echo "<pre>";
    echo "Starting user update process. Total users to process: $total_users\n";

    foreach ($users as $index => $user) {
        $current = $index + 1;
        echo "Processing user $current of $total_users: {$user['username']}\n";

        // If no ID is provided, try to get the user by username
        if (empty($user['id'])) {
            $existing_user = get_user_by('login', $user['username']);
            if ($existing_user) {
                $user['id'] = $existing_user->ID;
                echo "  Found existing user ID: {$user['id']}\n";
            } else {
                echo "  No existing user found for username: {$user['username']}. Skipping.\n";
                $skipped_count++;
                continue;
            }
        }

        // Prepare user data for update
        $userdata = array(
            'ID' => $user['id'],
            'user_login' => $user['username'],
            'user_email' => $user['email'],
            'display_name' => $user['name'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'] === 'none' ? '' : $user['role']
        );

        // Update user
        $user_id = wp_update_user($userdata);

        if (is_wp_error($user_id)) {
            echo "  Error updating user {$user['username']}: " . $user_id->get_error_message() . "\n";
            $error_count++;
        } else {
            echo "  User {$user['username']} updated successfully.\n";
            $updated_count++;

            // Update custom fields
            update_user_meta($user_id, 'author_bio_url', $user['author_bio_url']);
            update_user_meta($user_id, 'author_photo', $user['author_photo']);
            echo "  Custom fields updated for user {$user['username']}\n";
        }

        echo "\n"; // Add a blank line for readability between users
    }

    echo "User update process completed.\n";
    echo "Total users processed: $total_users\n";
    echo "Users updated successfully: $updated_count\n";
    echo "Users with errors: $error_count\n";
    echo "Users skipped: $skipped_count\n";

    $output = ob_get_clean(); // Get the buffered output

    return $output; // Return the output as a string
}
