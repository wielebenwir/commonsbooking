/**
 * E2E tests for the booking confirmation pending banner.
 *
 * Covers the pulsing dot indicator, countdown timer, expired state,
 * and absence of the banner on confirmed/cancelled bookings.
 * See templates/booking-single.php for the implementation.
 */
describe('Booking confirmation pending banner', () => {

    // Mirrors the helper in 06-booking-process.cy.js: returns a date that is
    // never the last day of the month, so the calendar always has a next-day
    // cell adjacent to .is-start-date.
    function getTestDate() {
        const dt = new Date();
        const test = new Date(dt.getTime());
        const month = test.getMonth();
        test.setDate(test.getDate() + 1);
        if (test.getMonth() !== month) {
            dt.setDate(dt.getDate() + 1);
        }
        return dt.getTime();
    }

    beforeEach(() => {
        cy.clock(getTestDate());
        cy.loginAs('subscriber');
        cy.visit('/?cb_item=basictest-noadmin&cb-location=32');
    });

    function createUnconfirmedBooking() {
        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').click();
        cy.get('.cb-notice').should('contain', 'Please check your booking and click confirm booking');
    }

    afterEach(() => {
        // Cancel any leftover unconfirmed booking so subsequent tests start clean.
        cy.get('body').then(($body) => {
            if ($body.find('.cb-action-delete_unconfirmed').length) {
                cy.get('.cb-action-delete_unconfirmed').click();
            }
        });
    });

    it('shows the pending banner for an unconfirmed booking', () => {
        createUnconfirmedBooking();
        cy.get('#cb-pending-banner').should('be.visible');
        cy.screenshot('pending-banner_visible');
    });

    it('shows the pulsing dot inside the banner', () => {
        createUnconfirmedBooking();
        cy.get('#cb-pending-banner .cb-pulse-dot').should('be.visible');
    });

    it('shows a countdown timer in MM:SS format', () => {
        createUnconfirmedBooking();
        // On month-boundary days getTestDate() is 24 h ahead of the PHP server
        // time, so the banner is already in expired state when the page loads.
        cy.get('#cb-pending-banner').then(($banner) => {
            if ($banner.hasClass('cb-expired')) {
                // Acceptable edge case: verify the expired message is present.
                cy.wrap($banner).should('contain', 'expired');
            } else {
                cy.get('#cb-countdown-timer')
                    .should('be.visible')
                    .invoke('text')
                    .should('match', /^\d{2}:\d{2}$/);
            }
        });
    });

    it('countdown timer decrements when the clock ticks', () => {
        createUnconfirmedBooking();
        cy.get('#cb-pending-banner').then(($banner) => {
            // Skip decrement check when already expired (month-boundary edge case).
            if ($banner.hasClass('cb-expired')) return;

            cy.get('#cb-countdown-timer').invoke('text').as('timeBefore');
            cy.tick(1000);
            cy.get('#cb-countdown-timer').invoke('text').then((timeAfter) => {
                cy.get('@timeBefore').should('not.equal', timeAfter);
            });
        });
    });

    it('switches to expired state after the 10-minute window elapses', () => {
        createUnconfirmedBooking();
        // Advance the fake browser clock past the 10-minute expiry (601 s).
        // Works on month-boundary days too: the banner is already expired so
        // ticking past it simply keeps the expired state.
        cy.tick(601 * 1000);
        cy.get('#cb-pending-banner')
            .should('have.class', 'cb-expired')
            .should('contain', 'expired');
        cy.screenshot('pending-banner_expired');
    });

    it('does not show the banner after the booking is confirmed', () => {
        createUnconfirmedBooking();
        cy.get('.cb-action-confirmed').click();
        cy.get('.cb-notice').should('contain', 'Your booking is confirmed');
        cy.get('#cb-pending-banner').should('not.exist');
        cy.screenshot('pending-banner_after-confirm');
        // Clean up the confirmed booking so the slot is free for other tests.
        cy.get('.cb-action-canceled').click();
    });

    it('does not show the banner after the booking process is cancelled', () => {
        createUnconfirmedBooking();
        cy.get('.cb-action-delete_unconfirmed').click();
        cy.get('#cb-pending-banner').should('not.exist');
        cy.screenshot('pending-banner_after-cancel');
    });
});
