<?php

/**
 * The main theme class
 */
class Avada {

	private static $framework_dir;

	public static $instance = null;

	public static $version = '3.9.4';

	public $settings;

	public $init;
	public $admin;
	public $social_icons;
	public $sidebars;
	public $portfolio;
	public $template;
	public $blog;
	public $updater;
	public $mfi;
	public $fonts;
	public $images;
	public $scripts;
	public $head;
	public $layout;
	public $dynamic_css;
	public $upgrade;
	public $layout_bbpress;
	public $google_map;

	public $c_pageID = false;

	/**
	 * Access the single instance of this class
	 * @return Avada
	 */
	public static function get_instance() {
		if ( self::$instance==null ) {
			self::$instance = new Avada();
		}
		return self::$instance;
	}

	/**
	 * Shortcut method to get the settings
	 */
	public static function settings() {
		return self::get_instance()->settings->get_all();
	}

	/**
	 * The class constructor
	 */
	private function __construct() {
		
		// Set Variables
		self::$framework_dir = get_template_directory_uri() . '/framework';

		// Instantiate secondary classes
		$this->init         	= new Avada_Init();
		$this->admin			= new Avada_Admin();
		$this->social_icons 	= new Avada_Social_Icons();
		$this->sidebars     	= new Avada_Sidebars();
		$this->portfolio    	= new Avada_Portfolio();
		$this->template     	= new Avada_Template();
		$this->blog         	= new Avada_Blog();
		$this->fonts        	= new Avada_Fonts();
		$this->image        	= new Avada_Images();
		$this->scripts      	= new Avada_Scripts();
		$this->head         	= new Avada_Head();
		$this->dynamic_css  	= new Avada_Dynamic_CSS();
		$this->updater      	= new Avada_Updater();
		$this->upgrade      	= new Avada_Upgrade();
		$this->layout_bbpress	= new Avada_Layout_bbPress();
		$this->layout    		= new Avada_Layout();
		$this->events_calendar	= new Avada_EventsCalendar();
		$this->google_map		= new Avada_GoogleMap();

		add_action( 'wp', array( $this, 'set_page_id' ) );

	}
	
	public static function get_theme_verion() {
		return self::$version;
	}
	
	public function set_page_id() {
		$this->c_pageID = self::c_pageID();
	}
	
	public static function get_framework_dir() {
		return self::$framework_dir;
	}

	public static function c_pageID() {
		$object_id = get_queried_object_id();

		if ( get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) && is_home() ) {
			$c_pageID = get_option( 'page_for_posts' );
		} else {
			if ( isset( $object_id ) ) {
				$c_pageID = $object_id;
			}
			if ( ! is_singular() ) {
				$c_pageID = false;
			}
			
			// Front page is the posts page
			if ( isset( $object_id ) && get_option( 'show_on_front' ) == 'posts' && is_home() ) {
				$c_pageID = $object_id;
			}
			
			if ( class_exists( 'WooCommerce' ) && ( is_shop() || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) ) {
				$c_pageID = get_option( 'woocommerce_shop_page_id' );
			}

		}

		return $c_pageID;

	}

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
