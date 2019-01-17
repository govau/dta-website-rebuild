@api

Feature: Report checks
  In order to ensure smooth running of the site
  As a site administrator
  I need to be able to access the reporting functions.

  Background:
    Given I am logged in as a user with the "Administrator" role
    When I am on "admin/reports/status"

    @reports
    Scenario: Reports page is available
      Then I see the heading "Status report"

    @version
    Scenario: Check Drupal version
      Then I see the text "Drupal Version 8.6.7"
