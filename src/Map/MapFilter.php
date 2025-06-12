<?php

namespace CommonsBooking\Map;

class MapFilter {

	protected static function check_item_terms_against_categories( $item_terms, $category_groups ): bool {
		$valid_groups_count = 0;

		foreach ( $category_groups as $group ) {
			foreach ( $item_terms as $term ) {
				if ( in_array( $term->term_id, $group ) ) {
					++$valid_groups_count;
					break;
				}
			}
		}

		return $valid_groups_count == count( $category_groups );
	}
}
