<?php
/*
Plugin Name: ACF to CSV Exporter
Description: Export ACF data to CSV.
Version: 1.0
Author: ICIMOD's Communications Web Team
*/

// Hook to add admin menu
add_action('admin_menu', 'acf_to_csv_exporter_menu');

// Check for export request early (before headers are sent)
add_action('admin_init', 'acf_to_csv_exporter_handle_export');

function acf_to_csv_exporter_menu()
{
    add_menu_page(
        'ACF to CSV Exporter',
        'ACF to CSV Exporter',
        'manage_options',
        'acf-to-csv-exporter',
        'acf_to_csv_exporter_page'
    );
}

function acf_to_csv_exporter_handle_export()
{
    if (isset($_POST['export_csv']) && check_admin_referer('acf_to_csv_export', 'acf_to_csv_nonce') && current_user_can('manage_options')) {
        acf_to_csv_exporter_export();
    }
}

function acf_to_csv_exporter_page()
{
?>
    <div class="wrap">
        <h1>ACF to CSV Exporter</h1>
        <form method="post" action="">
            <?php wp_nonce_field('acf_to_csv_export', 'acf_to_csv_nonce'); ?>
            <input type="submit" name="export_csv" class="button button-primary" value="Export CSV">
        </form>
    </div>
<?php
}

function acf_to_csv_exporter_export()
{
    $args = array(
        'post_type' => 'team', // Change to your post type
        'posts_per_page' => -1,
    );

    $posts = get_posts($args);

    if ($posts) {
        $csv_data = array();

        foreach ($posts as $post) {
            // Get WordPress post title (Full name)
            $full_name = sanitize_text_field($post->post_title);

            // Get Department (first category assigned to post)
            $departments = get_the_terms($post->ID, 'category'); // Default 'category', change if using a custom taxonomy
            $department = !empty($departments) ? sanitize_text_field($departments[0]->name) : '';

            // Get ACF designation (if exists)
            $designation = sanitize_text_field(get_field('designation', $post->ID) ?? '');

            // Get WordPress post slug
            $slug = sanitize_title($post->post_name);

            // Prepare CSV row
            $csv_row = array(
                'Full name' => $full_name,
                'Department' => $department,
                'Designation' => $designation,
                'Slug' => $slug,
            );

            $csv_data[] = $csv_row;
        }

        if (!empty($csv_data)) {
            $filename = 'team-export-' . date('Y-m-d') . '.csv';

            // Clear buffers and send headers
            if (ob_get_contents()) ob_end_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            // Open output stream
            $output = fopen('php://output', 'w');

            // Insert CSV headers (column names)
            fputcsv($output, array_keys($csv_data[0]));

            // Insert data rows
            foreach ($csv_data as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit;
        } else {
            wp_die('No data found for export.');
        }
    } else {
        wp_die('No posts found.');
    }
}
?>