<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Holiday;
use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;

class HolidayTest extends TestCase
{

    public function testGetYearsOption()
    {
		ClockMock::freeze(new \DateTime('01.01.2022'));
	    $selectOptions = Holiday::getYearsOption();
	    $selectOptions = explode('</option>',$selectOptions);
	    //filter out the empty string after the last </option>
	    $selectOptions = array_filter($selectOptions,fn($option) => !empty($option));
	    //2022 + 2 years
	    $this->assertCount(3,$selectOptions);
		$this->assertStringContainsString('2022',$selectOptions[0]);
		$this->assertStringContainsString('2023',$selectOptions[1]);
		$this->assertStringContainsString('2024',$selectOptions[2]);
    }

    public function testGetStatesOption()
    {
	    $selectOptions = Holiday::getStatesOption();
        $selectOptions = explode('</option>',$selectOptions);
		//filter out the empty string after the last </option>
		$selectOptions = array_filter($selectOptions,fn($option) => !empty($option));
	    //16 states + bund
	    $this->assertCount(17,$selectOptions);
    }
}
