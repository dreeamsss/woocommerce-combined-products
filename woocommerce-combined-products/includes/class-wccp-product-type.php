<?php

if(!defined('ABSPATH')) {
  exit;
}


if ( !class_exists( 'WCCP_Product_Type' ) && class_exists( 'WC_Product_Simple' ) ) {
  class WCCP_Product_Type {
    public static $instance;

    public static function instance(){
      if(is_null(self::$instance)) {
        self::$instance = new self();
      }

      return self::$instance;
    }

    public function __construct() {
      add_filter('product_type_selector', [$this, 'add_custom_product_type']); // Добавляем класс для нового типа товара
      add_filter('woocommerce_product_class', [$this, 'add_custom_product_class'], 10, 2); // Регистрируем обработку данных нового типа товара
    }  

    public function add_custom_product_type($types) {  
      $types['combined_product'] = __('Combined Product', 'woocommerce-combined-products');
      return $types;
    }

    public function add_custom_product_class($classname, $product_type) {
      if ($product_type === 'combined_product') {
        $classname = 'WCCP_Product_Combined';
      }
      return $classname;
    }
  }

  WCCP_Product_Type::instance();


  class WCCP_Product_Combined extends WC_Product_Simple {     
    public function __construct($product) {
      $this->product_type = 'combined_product';
      parent::__construct($product);
    }

    public function get_type() {
      return 'combined_product';
    }

    public function get_combined_products($args = array()) {
      global $post;
      
      if (!$post) return;

      $combined_product_ids = get_post_meta($post->ID, '_combined_products', true);

      $base_args = array(
        'post_type' => 'product',
        'post__in' => $combined_product_ids,
      );

      $query_args = array_merge($args, $base_args);

      $query = new WP_Query($query_args);
      
      $products = array();

      foreach($query->posts as $query_post) {
        $products[] = wc_get_product($query_post->ID);
      }

      return $products;
    }
  }
}


  