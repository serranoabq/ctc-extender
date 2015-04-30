<?php 
/*
		Custom recurrence class for CTC. Extend the CT_Recurrence class included in CTC.
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( class_exists( 'CT_Recurrence' ) ) {
	
	class CTCEX_Recurrence extends CT_Recurrence {
		public function __construct() {
			// Version
			$this->version = '0.9.1';
		}

		// This is a rewrite of the original class function to add 'daily' 
		// as an acceptable frequency argument. Would've been nice to have a filter.
		public function prepare_args( $args ) {

			// Is it a non-empty array?
			if ( empty( $args ) || ! is_array( $args ) ) { // could be empty array; set bool
				$args = false;
			}

			// Acceptable arguments
			$acceptable_args = array(
				'start_date',
				'until_date',
				'frequency',
				'interval',
				'monthly_type',
				'monthly_week',
				'limit',
			);

			// Loop arguments
			// Sanitize and set all keys
			$new_args = array();
			foreach( $acceptable_args as $arg ) {
				// If no key, set it
				if ( ! empty( $args[$arg] ) ) {
					$new_args[$arg] = $args[$arg];
				} else {
					$new_args[$arg] = '';
				}

				// Trim value
				$args[$arg] = trim( $new_args[$arg] );
			}
			$args = $new_args;

			// Start Date
			if ( $args ) {
				if ( empty( $args['start_date'] ) || ! $this->validate_date( $args['start_date'] ) ) {
					$args = false;
				}
			}

			// Until Date (optional)
			if ( $args ) {
				// Value is provided
				if ( ! empty( $args['until_date'] ) ) {
					// Date is invalid
					if ( ! $this->validate_date( $args['until_date'] ) ) {
						$args = false;
					}
				}
			}

			// Frequency
			if ( $args ) {
				// Value is invalid 
				// CHANGE: Add 'daily'
				if ( empty( $args['frequency'] ) || ! in_array( $args['frequency'], array( 'daily', 'weekly', 'monthly', 'yearly' ) ) ) {
					$args = false;
				}
			}

			// Interval
			// Every X days / weeks / months / years
			if ( $args ) {
				// Default is 1 if nothing given
				if ( empty( $args['interval'] ) ) {
					$args['interval'] = 1;
				}
				// Invalid if not numeric or is negative
				if ( ! is_numeric( $args['interval'] ) || $args['interval'] < 1 ) {
					$args = false;
				}
			}

			// Monthly Type (required when frequency is monthly)
			if ( $args ) {
				// Value is required
				if ( 'monthly' == $args['frequency'] ) {
					// Default to day if none
					if ( empty( $args['monthly_type'] ) ) {
						$args['monthly_type'] = 'day';
					}
					// Value is invalid
					if ( ! in_array( $args['monthly_type'], array( 'day', 'week' ) ) ) {
						$args = false; // value is invalid
					}
				}
				// Not required in this case
				else {
					$args['monthly_type'] = '';
				}
			}

			// Monthly Week (required when frequency is monthly and monthly_type is week)
			if ( $args ) {
				// Value is required
				if ( 'monthly' == $args['frequency'] && 'week' == $args['monthly_type'] ) {
					// Is value valid?
					if ( empty( $args['monthly_week'] ) || ! in_array( $args['monthly_week'], array( '1', '2', '3', '4', 'last' ) ) ) {
						$args = false; // value is invalid
					}
				}
				// Not required in this case
				else {
					$args['monthly_week'] = '';
				}
			}

			// Limit (optional)
			if ( $args ) {
				// Set default if no until date to prevent infinite loop
				if ( empty( $args['limit'] ) && empty( $args['until_date'] ) ) {
					$args['limit'] = 100;
				}
				// Limit is not numeric or is negative
				if ( ! empty( $args['limit'] ) && ( ! is_numeric( $args['limit'] ) || $args['limit'] < 1 ) ) {
					$args['limit'] = false;
				}
			}
			
			return $args;
		}
		
		public function calc_next_future_date( $args ) {

			// Get next date
			// This may or may not be future
			$date = $this->calc_next_date( $args ); // returns false if invalid args

			// Have valid date
			if ( $date ) {

				// Convert dates to timestamp for comparison
				$today_ts = strtotime( date_i18n( 'Y-m-d' ) ); // localized
				$date_ts = strtotime( $date );

				// Continue getting next date until it is not in past
				// This provides automatic correction in case wp-cron misses a beat
				while ( $date_ts < $today_ts ) {

					// Get next date
					$next_args = $args;
					$next_args['start_date'] = $date;
					$date = $this->calc_next_date( $next_args );
var_dump( $date );
					// If for some reason no next date can be calculated, stop
					// This is a safeguard to prevent an infinite loop
					if ( empty( $date ) ) {
						break;
					}

					// Convert new date to timestamp
					$date_ts = strtotime( $date );

				}

			}

			return $date;

		}

		// This is the same as the original function, except that it
		// incorporates a daily recurrence
		public function calc_next_date( $args ) {
			$date = false;
			
			// Validate and set default arguments
			$args = $this->prepare_args( $args );

			// Get next recurring date
			// This may or may not be future
			if ( $args ) { // valid args
				// Get month, day and year
				list( $start_date_y, $start_date_m, $start_date_d ) = explode( '-', $args['start_date'] );

				// Calculate next recurrence
				switch ( $args['frequency'] ) {

					// CHANGE: Add Daily - New
					case 'daily' :
						// Add day(s)--Update 0.9.1
						$DateTime = new DateTime( $args['start_date'] );
						$DateTime->modify( '+' . $args['interval'] . ' days' );
						list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
						break;

					// Weekly
					case 'weekly' :
						// Add week(s)
						$DateTime = new DateTime( $args['start_date'] );
						$DateTime->modify( '+' . $args['interval'] . ' weeks' );
						list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
						break;

					// Monthly
					case 'monthly' :
						// On same day of the month
						$DateTime = new DateTime( $args['start_date'] );
						$DateTime->modify( '+' . $args['interval'] . ' months' );
						list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );

							// Notes removed: see CT_Recurrence
							if ( $d < $start_date_d ) {
								// Move back to last day of last month
								$m--;
								if ( 0 == $m ) {
									$m = 12;
									$y--;
								}
								// Get days in the prior month
								$d = date( 't', mktime( 0, 0, 0, $m, $d, $y) );
							}

						// On a specific week of month's day
						// 1st - 4th or Last day of week in the month
						if ( 'week' == $args['monthly_type'] && ! empty( $args['monthly_week'] ) ) {
							// What is start_date's day of the week
							// 0 - 6 represents Sunday through Saturday
							$start_date_day_of_week = date( 'w', strtotime( $args['start_date'] ) );

							// Loop the days of this month
							$week_of_month = 1;
							$times_day_of_week_found = 0;
							$days_in_month = date( 't', mktime( 0, 0, 0, $m, 1, $y ) );

							for ( $i = 1; $i <= $days_in_month; $i++ ) {

								// Get this day's day of week (0 - 6)
								$day_of_week = date( 'w', mktime( 0, 0, 0, $m, $i, $y ) );

								// This day's day of week matches start date's day of week
								if ( $day_of_week == $start_date_day_of_week ) {
									$last_day_of_week_found = $i;
									$times_day_of_week_found++;

									// Is this the 1st - 4th day of week we're looking for?
									if ( $args['monthly_week'] == $times_day_of_week_found ) {
										$d = $i;
										break;
									}
								}
							}

							// Are we looking for 'last' day of week in a month?
							if ( 'last' == $args['monthly_week'] && ! empty( $last_day_of_week_found ) ) {
								$d = $last_day_of_week_found;
							}
						}

						break;

					// Yearly
					case 'yearly' :
						// Move forward X year(s)
						$DateTime = new DateTime( $args['start_date'] );
						$DateTime->modify( '+' . $args['interval'] . ' years' );
						list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );

							// Note removed
							if ( $d < $start_date_d ) {
								// Move back to last day of last month
								$m--;
								if ( 0 == $m ) {
									$m = 12;
									$y--;
								}

								// Get days in the prior month
								$d = date( 't', mktime( 0, 0, 0, $m, $d, $y) );
							}
						break;
				}

				// Form the date string
				$date = date( 'Y-m-d', mktime( 0, 0, 0, $m, $d, $y ) ); // pad day, month with 0
			}
			return $date;
		}
	}
}