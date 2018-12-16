@api

Feature: Toolbar checks
  In order to ensure smooth running of the site
  As a site administrator
  I need to be able to access the toolbar.

  Background:
    Given I am logged in as a user with the "Use the administration toolbar" permission
    When I am on the homepage

    Scenario: Reports page is available
      Then I should see "Manage" in the "toolbar" region
