<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Restriction as RestrictionPostType;

class RestrictionListColumnsTest extends CustomPostTypeTest {

	public function testListColumnsDoNotBuildAssignmentOptions(): void {
		$restrictionId = $this->createRestriction(
			Restriction::TYPE_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
		$postType      = new class() extends RestrictionPostType {
			protected function getCustomFields(): array {
				throw new \RuntimeException( 'List columns must not build assignment options.' );
			}
		};

		ob_start();
		$postType->setCustomColumnsData( Restriction::META_TYPE, $restrictionId );
		$typeOutput = ob_get_clean();

		ob_start();
		$postType->setCustomColumnsData( Restriction::META_STATE, $restrictionId );
		$stateOutput = ob_get_clean();

		$this->assertSame( RestrictionPostType::getTypes()[ Restriction::TYPE_HINT ], $typeOutput );
		$this->assertSame( RestrictionPostType::getStates()[ Restriction::STATE_ACTIVE ], $stateOutput );
	}
}
