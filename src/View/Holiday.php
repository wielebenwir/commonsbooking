<?php

namespace CommonsBooking\View;

class Holiday extends View {


	public static function getHoliday(){

		\CommonsBooking\Service\Holiday::getHoliday();

	}
}