<?php

if(!defined('ABSPATH')) {
  exit;
}


if ( !class_exists( 'WCCP_Product_Fields' ) && class_exists( 'WC_Product_Simple' ) ) {

  class WCCP_Product_Fields {
    public static $instance;

    public static function instance(){
      if(is_null(self::$instance)) {
        self::$instance = new self();
      }

      return self::$instance;
    }

    public function __construct()
    {
      add_action('woocommerce_product_data_panels', [$this, 'display_custom_product_data_panel']);
      add_filter('woocommerce_product_data_tabs', [$this, 'add_custom_product_data_tab']);
      add_action('woocommerce_process_product_meta_combined_product', [$this, 'save_custom_product_fields']);
      
      add_action('admin_footer', [$this, 'search_combined_product_ajax']);
      add_action('admin_footer', [$this, 'enable_product_options_js']);
      add_action('admin_footer', [$this, 'set_combined_products_select2_content']);

      add_action('wp_ajax_search_combined_products', [$this, 'search_combined_products']);
      add_action('wp_ajax_nopriv_search_combined_products', [$this, 'search_combined_products']);
    }

    function add_custom_product_data_tab($tabs) {
      $tabs['combined_products_tab'] = array(
        'label' => __('Combined Products', 'woocommerce-combined-products'), // Название вкладки
        'target' => 'combined_products_data', // ID контейнера с содержимым вкладки
        'class' => array('show_if_combined_product'), // Класс, который определяет, когда вкладка будет отображаться
      );
      
      return $tabs;
    }

    public function display_custom_product_data_panel() {
      global $post;

      ?>
      <div id="combined_products_data" class="panel woocommerce_options_panel hidden">
        <?php
          $combined_products = get_post_meta($post->ID, '_combined_products', true);
          $selected_combined_products = array();

         if($combined_products) {
            foreach($combined_products as $value) {
              $product = wc_get_product($value);
              $cpost = get_post($value);

              if($product) {
                // чтобы в select в value каждого option отображался id
                $selected_combined_products[strval($product->get_id())] = esc_html($product->get_name()) . '(' . esc_html($product->get_sku()) . ')';
              }
            }
          }

          woocommerce_wp_select(
            array(
              'id' => '_combined_products',
              'label' => __('Services', 'woocommerce-combined-products'),
              'desc_tip' => 'true',
              'description' => __('Select the services that are associated with this product', 'woocommerce-combined-products'),
              'options' => $selected_combined_products,
              'name' => '_combined_products[]',
              'custom_attributes' => array(
                'data-placeholder' => __('Select services', 'woocommerce-combined-products'),
                'multiple' => 'multiple',
              ),
            )
          );
        ?>
      </div>
      <?php
      
    }

    public function save_custom_product_fields($post_id) {
      $combined_products = isset($_POST['_combined_products']) ? array_map('intval', $_POST['_combined_products']) : array();
      update_post_meta($post_id, '_combined_products', $combined_products);
    }

    public function set_combined_products_select2_content() {
      ?>
      <script>
        const values = jQuery("#_combined_products option")
          .map(function() {
          return jQuery(this).val();
          })
          .get();

        var $select2 = jQuery('#_combined_products');

        $select2.val(values);
        $select2.trigger('change');
      </script>
      <?php
    }

    public function enable_product_options_js() {
      global $post, $product_object;

      if(!$post) return;

      if($post->post_type != 'product') return;

      $is_combined_product = $product_object && $product_object->get_type() === 'combined_product' ? true : false;
      ?>
      <script>
        jQuery(document).ready(function() {
          jQuery("#general_product_data .pricing").addClass('show_if_combined_product');

          jQuery(".inventory_options").addClass("show_if_combined_product");
          jQuery("#inventory_product_data .inventory_sold_individually")
            .addClass("show_if_combined_product")
            .find("._sold_individually_field")
            .addClass("show_if_combined_product");

          <?php
            if($is_combined_product) { ?>
              jQuery(".general_options ").show();
              jQuery("#general_product_data .pricing").show();
              jQuery(".inventory_options").show();
              jQuery("#inventory_product_data .inventory_sold_individually")
                .show()
                .find("._sold_individually_field")
                .show();
            <?php }
          ?>
        });
      </script>
      <?php
    }

    public function search_combined_product_ajax() {
      ?>
      <script>
        jQuery(document).ready(function ($) {
          $('#_combined_products').select2({
            ajax: {
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              dataType: 'json',
              delay: 250,
              data: function (params) {
                return {
                  q: params.term, // Передаем введенный текст для поиска
                  action: 'search_combined_products', // Используемая для Ajax функция
                  security: '<?php echo wp_create_nonce('search-combined-products'); ?>', // Добавляем nonce для безопасности
                };
              },
              processResults: function (data) {
                return {
                  results: data,
                };
              },
              cache: true,
            },
            minimumInputLength: 3, // Минимальное количество символов для начала поиска
          });
        });
      </script>
      <?php
    }

    public function search_combined_products()
    {
      check_ajax_referer('search-combined-products', 'security');

      $search_term = sanitize_text_field($_GET['q']);

      // Выполняем поиск товаров
      $args = array(
        'post_type' => 'product',
        's' => $search_term,
        'posts_per_page' => -1,
      );

      $products = new WP_Query($args);

      $results = array();
      if ($products) {
        foreach ($products->posts as $product) {
          $wc_product = wc_get_product($product->ID);
          $results[] = array(
            'id' => $product->ID,
            'text' => $wc_product->get_name() . '(' . $wc_product->get_sku() . ')',
          );
        }
      }
      
      wp_send_json($results);
    }
  }
  
  WCCP_Product_Fields::instance();
}
