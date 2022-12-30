<?php

namespace CommonsBooking\Service;

use PHPUnit\Framework\TestCase;

class BookingRuleAppliedTest extends BookingRuleTest
{
	private BookingRuleApplied $appliedTrue;
	private BookingRuleApplied $appliedFalse;
	private \CommonsBooking\Model\Booking $bookingModel;

    public function testFromBookingRule()
    {
		$appliedRule = BookingRuleApplied::fromBookingRule($this->alwaysallow,true);
		self::assertNotNull($appliedRule);
    }

    public function testCheckBooking()
    {
		self::assertTrue($this->appliedTrue->checkBooking( $this->bookingModel));
		self::assertFalse($this->appliedFalse->checkBooking($this->bookingModel));

	}

	protected function setUp() {
		parent::setUp();
		$this->appliedTrue = new BookingRuleApplied(
			$this->alwaysallow->getName(),
			$this->alwaysallow->getTitle(),
			$this->alwaysallow->getDescription(),
			$this->alwaysallow->getErrorMessage(),
			$this->alwaysallow->getValidationFunction(),
			true
		);
		$this->appliedFalse = new BookingRuleApplied(
			$this->alwaysdeny->getName(),
			$this->alwaysdeny->getTitle(),
			$this->alwaysdeny->getDescription(),
			$this->alwaysdeny->getErrorMessage(),
			$this->alwaysdeny->getValidationFunction(),
			true
		);
		$this->bookingModel = new \CommonsBooking\Model\Booking($this->testBooking);
	}

	protected function tearDown() {
		parent::tearDown();
	}
}
