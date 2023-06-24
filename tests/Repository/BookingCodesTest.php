<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class BookingCodesTest extends CustomPostTypeTest
{
	private Timeframe $timeframeWithEndDate;
	private Timeframe $timeframeWithoutEndDate;

    public function testGenerate()
    {
		ClockMock::freeze(new \DateTime( self::CURRENT_DATE ) );
		BookingCodes::generate($this->timeframeWithEndDate);
		$todayDate = date('Y-m-d',strtotime(self::CURRENT_DATE));
		$code = BookingCodes::getCode($this->timeframeWithEndDate->ID,$this->itemId,$this->locationId,$todayDate);
		$this->assertNotNull($code);
		$this->assertEquals($todayDate,$code->getDate());
		$this->assertEquals($this->itemId,$code->getItem());
		$this->assertEquals($this->locationId,$code->getLocation());
		$this->assertEquals($this->timeframeWithEndDate->ID,$code->getTimeframe());

		BookingCodes::generate($this->timeframeWithoutEndDate);
		$code = BookingCodes::getCode($this->timeframeWithoutEndDate->ID,$this->itemId,$this->locationId,$todayDate);
		$this->assertNotNull($code);
		$this->assertEquals($todayDate,$code->getDate());
		$this->assertEquals($this->itemId,$code->getItem());
		$this->assertEquals($this->locationId,$code->getLocation());
		$this->assertEquals($this->timeframeWithoutEndDate->ID,$code->getTimeframe());

	    $advanceDays = BookingCodes::ADVANCE_GENERATION_DAYS - 1;
		$lastCodeDay = date('Y-m-d',strtotime(" + $advanceDays days",strtotime(self::CURRENT_DATE)));
		$code = BookingCodes::getCode($this->timeframeWithoutEndDate->ID,$this->itemId,$this->locationId,$lastCodeDay);
		$this->assertNotNull($code);
		$this->assertEquals($lastCodeDay,$code->getDate());
		$this->assertEquals($this->itemId,$code->getItem());
		$this->assertEquals($this->locationId,$code->getLocation());
		$this->assertEquals($this->timeframeWithoutEndDate->ID,$code->getTimeframe());
    }

	public function testGetCode() {
		//make sure, that non-existing codes are not returned
		$code = BookingCodes::getCode( $this->timeframeWithEndDate->ID,
			$this->itemId,
			$this->locationId,
			strtotime('+31 days', strtotime( self::CURRENT_DATE) )
		);
		$this->assertNull( $code );

		BookingCodes::generate( $this->timeframeWithoutEndDate );
		//test infinite booking days timeframes
		$advanceDays = BookingCodes::ADVANCE_GENERATION_DAYS + 1; //advance one day beyond the max generation days
		$dayInFuture = date( 'Y-m-d',
			strtotime( " + $advanceDays days",
				strtotime( self::CURRENT_DATE ) ) );
		//this should trigger the generation of a new code past the max generation days
		$code = BookingCodes::getCode( $this->timeframeWithoutEndDate->ID,
			$this->itemId,
			$this->locationId,
			$dayInFuture );
		$this->assertNotNull( $code );
		$this->assertEquals( $this->timeframeWithoutEndDate->ID, $code->getTimeframe() );
		$this->assertEquals( $this->itemId, $code->getItem() );
		$this->assertEquals( $this->locationId, $code->getLocation() );
		$this->assertEquals( $dayInFuture, $code->getDate() );

		//test that the code is persisted (i.e. it's not generated again)
		$otherCode = BookingCodes::getCode( $this->timeframeWithoutEndDate->ID,
			$this->itemId,
			$this->locationId,
			$dayInFuture );
		$this->assertNotNull( $otherCode );
		$this->assertEquals( $code->getCode(), $otherCode->getCode() );

		//go even further in the future (* 2 max generation days)
		$advanceDays = BookingCodes::ADVANCE_GENERATION_DAYS * 2;
		$dayInFutureTwo = date( 'Y-m-d',
			strtotime( " + $advanceDays days",
				strtotime( self::CURRENT_DATE ) ) );
		//this should trigger the generation of a new code past the max generation days
		$futureTwoCode = BookingCodes::getCode( $this->timeframeWithoutEndDate->ID,
			$this->itemId,
			$this->locationId,
			$dayInFutureTwo );
		$this->assertNotNull( $futureTwoCode );
		$this->assertEquals( $this->timeframeWithoutEndDate->ID, $futureTwoCode->getTimeframe() );
		$this->assertEquals( $this->itemId, $futureTwoCode->getItem() );
		$this->assertEquals( $this->locationId, $futureTwoCode->getLocation() );
		$this->assertEquals( $dayInFutureTwo, $futureTwoCode->getDate() );
		//now check, that the old code is still persisted
		$stillSameCode = BookingCodes::getCode( $this->timeframeWithoutEndDate->ID,
			$this->itemId,
			$this->locationId,
			$dayInFuture );
		$this->assertNotNull( $stillSameCode );
		$this->assertEquals( $code->getCode(), $stillSameCode->getCode() );
	}

	public function testGetCodes() {
		//make sure that we get no codes before generation
		$codes = BookingCodes::getCodes( $this->timeframeWithEndDate->ID);
		$this->assertEmpty( $codes );

		BookingCodes::generate( $this->timeframeWithEndDate );
		//now we should get all codes
		$codes = BookingCodes::getCodes( $this->timeframeWithEndDate->ID);
		$this->assertNotEmpty( $codes );
		$this->assertCount( 32, $codes );
		//check that the codes are in the correct order
		$lastCode = null;
		foreach ( $codes as $code ) {
			if ( $lastCode ) {
				$this->assertGreaterThan( $lastCode->getDate(), $code->getDate() );
			}
			$lastCode = $code;
		}

		//test infinite booking days timeframes
		BookingCodes::generate( $this->timeframeWithoutEndDate );
		//now we should get all codes
		$codeAmount = BookingCodes::ADVANCE_GENERATION_DAYS + 1;
		$codes = BookingCodes::getCodes( $this->timeframeWithoutEndDate->ID);
		$this->assertNotEmpty( $codes );
		$this->assertCount( $codeAmount, $codes );
		//check that the codes are in the correct order
		$lastCode = null;
		foreach ( $codes as $code ) {
			if ( $lastCode ) {
				$this->assertGreaterThan( $lastCode->getDate(), $code->getDate() );
			}
			$lastCode = $code;
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->timeframeWithEndDate = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+30 day', strtotime( self::CURRENT_DATE ) )
		));
		$this->timeframeWithoutEndDate = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			null,
		));

		Settings::updateOption('commonsbooking_options_bookingcodes','bookingcodes','Turn,and,face,the,strange,Ch-ch-changes');
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
