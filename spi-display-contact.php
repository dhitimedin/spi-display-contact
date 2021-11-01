<?php
/*
* Plugin Name: SPI Display Contact
* Description: Display contact request stored in wp_contact table
* Author: SPI
* Version: 1.0
*/


if(is_admin())
{
    new Cntctrqst_Wp_List_Table();
}

/**
 * Cntctrqst_Wp_List_Table class will create the page to load the table
 */
class Cntctrqst_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'spi_contact_request_display_page' ) );
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function spi_contact_request_display_page()
    {
        add_menu_page( 'Contact Requests', 'Contact Requests', 'manage_options', 'spi-display-contact/spi-display-contact.php', array($this, 'list_table_page'), 'dashicons-database-view' );
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function list_table_page()
    {
        $example_list_table = new Contact_Request_Display();
        $example_list_table->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Contact Requests</h2>
                <form method="post" name="frm_search_contact" action=<?php $_SERVER['PHP_SELF'] . '?page=spi-display-contact' ?> >
                    <?php $example_list_table->search_box("Search Contact(s)", "search_post_id"); ?>
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                    <?php $example_list_table->display(); ?>
                </form>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Contact_Request_Display extends WP_List_Table {

	/**
	 * Contact_Request_Display constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'contact',     // Singular name of the listed records.
			'plural'   => 'contacts',    // Plural name of the listed records.
			'ajax'     => true,       // Does this table support ajax?
		) );

        $this->set_order();
        $this->set_orderby();

	}

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $search_term = isset($_POST[ 's' ] ) ? $_POST[ 's' ] : "";
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $data = $this->table_data( $search_term );
        usort( $data, array( $this, 'sort_data' ) );


        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id'        => 'ID',
            'name'      => 'Name',
            'email'     => 'Email',
            'phone'     => 'Phone',
            'subject'   => 'Subject',
            'msg'       => 'Message'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        $sortable = array(
            'id' => array('id', true),
            'name' => array('name', true)
        );
        return $sortable;
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data( $search_term = '' )
    {
        $data = array();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact';
        //$args = array('id', 'name', 'email', 'phone', 'subject', 'msg');
        //$sql_select = implode(', ', $args);
        if( !empty($search_term) ) {
            $data = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE (CONCAT(name, subject, msg, email, phone) LIKE '%$search_term%')",
                 ARRAY_A );
        }
        else {
            $data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'name':
            case 'email':
            case 'phone':
            case 'subject':
            case 'msg':
                return $item[ $column_name ];
                break;
            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {

        $orderby = (!empty($_REQUEST['orderby'])) ?
                        $_REQUEST['orderby'] : 'id'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ?
                        $_REQUEST['order'] : 'asc'; //If no order, default to asc
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] ); //Determine sort order

        return ( ( $order === 'asc') ? $result : -$result ); //Send final sort direction to usort
    }

   /* public function ajax_user_can()
    {
        return current_user_can('edit_posts');
    }*/

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
            return sprintf(
                '<input type="checkbox" name="id[]" value="%s" />',
                esc_attr( $item[ 'id' ] )
            );
        }

    public function process_bulk_action() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact'; // do not forget about tables prefix

        if ( 'delete' === $this->current_action() ) {
            $ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
            if ( is_array( $ids ) ) {
                $ids = implode( ',', $ids );
            }

            if ( ! empty( $ids ) ) {
                $wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
            }
        }
    }

    function column_name($item){
        $item_json = json_decode( json_encode( $item ), true );
        $actions = array(
           // 'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item_json['id']),
            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item_json['id']),
        );
        return '<em>' . sprintf( '%s %s', $item_json['name'], $this->row_actions( $actions ) ) . '</em>';
    }

    public function set_order()
    {
        $order = 'ASC';
        if (isset($_GET['order']) AND $_GET['order'])
            $order = $_GET['order'];
        $this->order = esc_sql($order);
    }

    public function set_orderby()
    {
        $orderby = 'id';
        if (isset($_GET['orderby']) AND $_GET['orderby'])
            $orderby = $_GET['orderby'];
        $this->orderby = esc_sql($orderby);
    }

    /**
     * Disables the views for 'side' context as there's not enough free space in the UI
     * Only displays them on screen/browser refresh. Else we'd have to do this via an AJAX DB update.
     *
     * @see WP_List_Table::extra_tablenav()
     */
   /* public function extra_tablenav($which)
    {
        global $wp_meta_boxes;
        $views = $this->get_views();
        if (empty($views)) return;

        $this->views();
    }*/

}