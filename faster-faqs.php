<?php
/*
Plugin Name: Faster FAQs
Description: Simple, flexible and SEO friendly FAQs shortcode.
Version: 1.0
Author: Alex Ruco
*/

// Register Custom Post Type for FAQs
function faq_custom_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Faster FAQs',
        'supports' => array('title', 'editor', 'page-attributes'), // Supports Title, Description, and Order
        'hierarchical' => false,
        'show_in_rest' => true,
        'taxonomies' => array('category'), // Enables Category taxonomy
    );
    register_post_type('faq', $args);
}
add_action('init', 'faq_custom_post_type');

// Enqueue JavaScript and a blank CSS file for accordion functionality
function enqueue_faster_faqs_assets() {
    // Enqueue JavaScript
    $js_file_url = plugins_url('js/faster-faqs.js', __FILE__);
    wp_enqueue_script('faster-faqs-script', $js_file_url, array('jquery'), '1.0', true);

    // Enqueue blank CSS file
    $css_file_url = plugins_url('css/faster-faqs-custom.css', __FILE__);
    wp_enqueue_style('faster-faqs-custom-style', $css_file_url);
}
add_action('wp_enqueue_scripts', 'enqueue_faster_faqs_assets');

// Shortcode to display FAQs in an accordion format
function faq_shortcode($atts) {
    $atts = shortcode_atts(array('category' => ''), $atts);
    $args = array(
        'post_type' => 'faq',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'category_name' => $atts['category']
    );
    $faqs = new WP_Query($args);
    
    $output = '<div class="faq-accordion">';
    while($faqs->have_posts()) : $faqs->the_post();
        $output .= '<div class="faq-item">';
        $output .= '<div class="faq-title">' . get_the_title() . '</div>';
        $output .= '<div class="faq-content">' . get_the_content() . '</div>';
        $output .= '</div>';
    endwhile;
    wp_reset_postdata();
    $output .= '</div>';

    // Add JSON structured data for SEO
    $output .= faq_structured_data($faqs);

    return $output;
}
add_shortcode('faster_faq', 'faq_shortcode');

// Function to generate structured data for SEO
function faq_structured_data($faqs) {
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array()
    );
    while($faqs->have_posts()) : $faqs->the_post();
        $structured_data['mainEntity'][] = array(
            '@type' => 'Question',
            'name' => get_the_title(),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => get_the_content()
            )
        );
    endwhile;

    return '<script type="application/ld+json">' . json_encode($structured_data) . '</script>';
}

// Add menu page for plugin settings
function faq_settings_page() {
    add_menu_page('Faster FAQ Settings', 'Faster FAQ Settings', 'manage_options', 'faq-settings', 'faq_settings_page_html', 'dashicons-admin-generic');
}
add_action('admin_menu', 'faq_settings_page');

// HTML for the plugin settings page
function faq_settings_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>

        <div style="margin-top: 20px;">
            <h2>How to Use the Shortcode</h2>
            <p>To display your FAQs, use the shortcode <code>[faster_faq]</code> in any post, page, or text widget. Here are some ways you can use it:</p>
            <ul>
                <li><strong>Basic Usage:</strong> Simply insert <code>[faster_faq]</code> where you want your FAQs to appear.</li>
                <li><strong>With Category Filter:</strong> Use <code>[faster_faq category="category-slug"]</code> to display FAQs from a specific category. Replace "category-slug" with the actual slug of your category.</li>
            </ul>
            <p>For developers, you can also insert the shortcode directly into PHP files using <code>&lt;?php echo do_shortcode('[faster_faq]'); ?&gt;</code>.</p>
        </div>

        <form action="options.php" method="post">
            <?php
            settings_fields('faq_settings');
            do_settings_sections('faq_settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register a new setting for "faq_settings" page
function faq_register_settings() {
    register_setting('faq_settings', 'faq_custom_css');

    add_settings_section('faq_settings_section', 'Custom CSS', 'faq_settings_section_cb', 'faq_settings');

    add_settings_field('faq_custom_css_field', 'Custom CSS for FAQ Accordion', 'faq_custom_css_field_cb', 'faq_settings', 'faq_settings_section');
}
add_action('admin_init', 'faq_register_settings');

function faq_settings_section_cb() {
    echo '<p>Enter your custom CSS for the FAQ accordion here.</p>';
}

function faq_custom_css_field_cb() {
    $css = get_option('faq_custom_css');
    if (empty($css)) {
        // Set default CSS if not set
        $css = get_default_faq_css();
        update_option('faq_custom_css', $css);
    }
    echo '<textarea name="faq_custom_css" style="width:100%;height:200px;">' . esc_textarea($css) . '</textarea>';
}

// Enqueue custom CSS from plugin settings
function faq_enqueue_custom_css() {
    $custom_css = get_option('faq_custom_css');
    if (!empty($custom_css)) {
        wp_add_inline_style('faster-faqs-custom-style', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'faq_enqueue_custom_css');

function get_default_faq_css() {
    return "
    /* Default Style for FAQ Accordion */
    .faq-accordion {
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .faq-item {
        border-bottom: 1px solid #ddd;
        padding: 10px;
    }
    .faq-title {
        font-weight: bold;
        cursor: pointer;
    }
    .faq-content {
        display: none;
        padding: 10px;
        background-color: #f9f9f9;
    }
    ";
}
