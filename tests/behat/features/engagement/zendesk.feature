@api

Feature: Zendesk form existence
  In order to ask for help on the website
  As a user
  I need to be able to use the Zendesk submission form.

  Background:
    Given "page" content:
    | title       | field_introduction       | field_body       | field_summary  | status | moderation_state | nid  |
    | test page 1 | test page 1 introduction | test page 1 body | test summary 1 | 1      | published        | 9999 |

    @zendesk @anonymous
    Scenario:
      Check the existence of the Zendesk button for anonymous users.
      Given I am an anonymous user
      When I visit "test-page-1"
      Then I should see a "div.modal.modal-controls__open" element

    @zendesk
    Scenario:
      Check access to the Zendesk form settings.
      Given I am logged in as a user with the "administrator" role
      When I visit "/admin/config/services/zendesk_forms"
      Then the response status code should be 200

    @zendesk @anonymous
    Scenario:
      Check existence of the form and form fields.
      Given I am an anonymous user
      When I visit "test-page-1"
      Then I should see the "Ask for help" heading in the "zendesk_form" region
      And I should see "Your name" in the "zendesk_form" region
      And I should see "Your e-mail address" in the "zendesk_form" region
      And I should see "Subject" in the "zendesk_form" region
      And I should see "Message" in the "zendesk_form" region
      And I should see 3 "form#zendesk-support-form input[type='text']" elements
      And I should see 1 "form#zendesk-support-form textarea" element
      And I should see 1 "form#zendesk-support-form input[type='submit']" element
