@api

Feature: Breadcrumbs
  In order to navigate through the site
  As a user
  I need to be able to see my breadcrumb trail.

  Background:
    Given I am an anonymous user
    And I am viewing a page with the title "Test page"

    @anonymous @breadcrumbs
    Scenario: viewing breadcrumbs
    Then I should see 1 "nav.au-breadcrumbs" element
