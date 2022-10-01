<?php
class EN_P_T_Private_Posts_Controller extends WP_REST_Posts_Controller {

	/**
	* The namespace.
	*
	* @var string
	*/
	protected $namespace;

	/**
	* The post type for the current object.
	*
	* @var string
	*/
	protected $post_type;

	/**
	* Rest base for the current object.
	*
	* @var string
	*/
	protected $rest_base;

	/**
	* Register the routes for the objects of the controller.
	* Nearly the same as WP_REST_Posts_Controller::register_routes(), but with a 
	* custom permission callback.
	*/
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
				'show_in_index'       => true,
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				'show_in_index'       => true,
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
				'show_in_index'       => true,
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				'show_in_index'       => true,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.' ),
					),
				),
				'show_in_index'       => false,
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	* Check if a given request has access to get items
	*
	* @param WP_REST_Request $request Full data about the request.
	* @return WP_Error|bool
	*/
	public function get_items_permissions_check( $request ) {

		return current_user_can( 'edit_posts' );

	}

}
