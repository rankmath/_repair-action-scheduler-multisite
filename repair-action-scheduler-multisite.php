<?php // @codingStandardsIgnoreLine
/**
 * Rank Math SEO - Action Scheduler Repair Plugin.
 *
 * @package      RANK_MATH
 * @copyright    Copyright (C) 2019, Rank Math - support@rankmath.com
 * @link         https://rankmath.com
 * @since        0.9.0
 *
 * @wordpress-plugin
 * Plugin Name:       Repair Action Scheduler - Multisite version
 * Version:           1.1
 * Plugin URI:        https://s.rankmath.com/home
 * Description:       Fix database errors related to the Action Scheduler library on a multisite WP install. The plugin checks and creates the tables necessary for Action Scheduler version 3.3.0 when a new site is created. This plugin needs to stay activated on the site to avoid the error related to the Action Scheduler.
 * Author:            Rank Math
 * Author URI:        https://s.rankmath.com/home
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       repair-action-scheduler
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Updater class.
 */
class Repair_Action_Scheduler_Mu {

	const ACTIONS_TABLE = 'actionscheduler_actions';
	const CLAIMS_TABLE  = 'actionscheduler_claims';
	const GROUPS_TABLE  = 'actionscheduler_groups';
	const LOG_TABLE     = 'actionscheduler_logs';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'wp_insert_site', array( $this, 'create_tables' ), -10, 1 );
	}

	/**
	 * Create tables.
	 *
	 * @param \WP_Site $site Site object.
	 */
	public function create_tables( $wp_site ) {
		$this->tables = array(
			self::ACTIONS_TABLE => 'action_id',
			self::CLAIMS_TABLE  => 'claim_id',
			self::GROUPS_TABLE  => 'group_id',
			self::LOG_TABLE     => 'log_id',
		);

		foreach ( $this->tables as $table => $primary_column ) {
			if ( ! $this->table_exists( $table, $wp_site->id ) ) {
				$this->create_table( $table, $wp_site->id );
			}
		}
	}

	/**
	 * Check if table exists.
	 *
	 * @param string $table Table name.
	 * @param int    $blog_id Site ID.
	 *
	 * @return bool
	 */
	private function table_exists( $table, $blog_id ) {
		global $wpdb;
		$prefix = $wpdb->get_blog_prefix( $blog_id );
		$tables = $wpdb->query( "SHOW TABLES LIKE '{$prefix}{$table}'" ); // phpcs:ignore
		return (bool) $tables;
	}

	/**
	 * Create table.
	 *
	 * @param string $table Table name.
	 * @param int    $blog_id Site ID.
	 */
	private function create_table( $table, $blog_id ) {
		global $wpdb;
		$wpdb->query( $this->get_table_schema( $table, $blog_id ) ); // phpcs:ignore
	}

	/**
	 * Get table schema.
	 *
	 * @param string $table Table name.
	 * @param int    $blog_id Site ID.
	 *
	 * @return string
	 */
	private function get_table_schema( $table, $blog_id ) {
		global $wpdb;
		$prefix = $wpdb->get_blog_prefix( $blog_id );

		$table_name       = $prefix . $table;
		$charset_collate  = $wpdb->get_charset_collate();
		$max_index_length = 191; // @see wp_get_db_schema()
		$default_date     = '0000-00-00 00:00:00';
		switch ( $table ) {

			case self::ACTIONS_TABLE:
				return "CREATE TABLE {$table_name} (
					action_id bigint(20) unsigned NOT NULL auto_increment,
					hook varchar(191) NOT NULL,
					status varchar(20) NOT NULL,
					scheduled_date_gmt datetime NULL default '${default_date}',
					scheduled_date_local datetime NULL default '${default_date}',
					args varchar($max_index_length),
					schedule longtext,
					group_id bigint(20) unsigned NOT NULL default '0',
					attempts int(11) NOT NULL default '0',
					last_attempt_gmt datetime NULL default '${default_date}',
					last_attempt_local datetime NULL default '${default_date}',
					claim_id bigint(20) unsigned NOT NULL default '0',
					extended_args varchar(8000) DEFAULT NULL,
					PRIMARY KEY  (action_id),
					KEY hook (hook($max_index_length)),
					KEY status (status),
					KEY scheduled_date_gmt (scheduled_date_gmt),
					KEY args (args($max_index_length)),
					KEY group_id (group_id),
					KEY last_attempt_gmt (last_attempt_gmt),
					KEY `claim_id_status_scheduled_date_gmt` (`claim_id`, `status`, `scheduled_date_gmt`)
					) $charset_collate";

			case self::CLAIMS_TABLE:
				return "CREATE TABLE {$table_name} (
						claim_id bigint(20) unsigned NOT NULL auto_increment,
						date_created_gmt datetime NULL default '${default_date}',
						PRIMARY KEY  (claim_id),
						KEY date_created_gmt (date_created_gmt)
						) $charset_collate";

			case self::GROUPS_TABLE:
				return "CREATE TABLE {$table_name} (
						group_id bigint(20) unsigned NOT NULL auto_increment,
						slug varchar(255) NOT NULL,
						PRIMARY KEY  (group_id),
						KEY slug (slug($max_index_length))
						) $charset_collate";

			case self::LOG_TABLE:
				return "CREATE TABLE {$table_name} (
						log_id bigint(20) unsigned NOT NULL auto_increment,
						action_id bigint(20) unsigned NOT NULL,
						message text NOT NULL,
						log_date_gmt datetime NULL default '${default_date}',
						log_date_local datetime NULL default '${default_date}',
						PRIMARY KEY  (log_id),
						KEY action_id (action_id),
						KEY log_date_gmt (log_date_gmt)
						) $charset_collate";

			default:
				return '';
		}
	}
}

/**
 * Initialize the plugin.
 */
$repair_action_scheduler = new Repair_Action_Scheduler_Mu();
