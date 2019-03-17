@api

Feature: Search keywords
  In order to ensure users can search content with keywords
  As a site editor
  I need to be able to add the appropriate search keywords.

  Background:
    Given I am logged in as a user with the "Content editor" role

    @editing @search @pages
    Scenario: Search keywords field exists on pages
      When I am on "node/add/page"
      Then I see the text "Search keywords"
